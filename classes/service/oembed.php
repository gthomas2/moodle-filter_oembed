<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Filter for component 'filter_oembed'
 *
 * @package   filter_oembed
 * @copyright Erich M. Wappis / Guy Thomas 2016
 * @author Erich M. Wappis <erich.wappis@uni-graz.at>
 * @author Guy Thomas <brudinie@googlemail.com>
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_oembed\service;
use filter_oembed\provider\provider;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

/**
 * Class oembed
 * @package filter_oembed\service
 * @copyright Erich M. Wappis / Guy Thomas 2016
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Singleton class providing function for filtering embedded content links in text.
 */
class oembed {

    /**
     * @var array
     */
    protected $warnings = [];

    /**
     * @var array|mixed
     */
    protected $providers = [];

    /**
     * @var array Boolean array of provider id's enabled status.
     */
    protected $enabled = [];

    /**
     * Constructor - protected singeton.
     *
     * @param string $providers Either 'enabled', 'disabled', or 'all'.
     */
    protected function __construct($providers = 'enabled') {
        $this->set_providers($providers);
    }

    /**
     * Singleton
     *
     * @param string $providers Either 'enabled', 'disabled', or 'all'.
     * @return oembed
     */
    public static function get_instance($providers = 'enabled') {
        /** @var $instance oembed */
        static $instance;
        if ($instance) {
            return $instance;
        } else {
            return new oembed($providers);
        }
    }

    /**
     * Set providers property.
     *
     * @param string $providers Either 'enabled', 'disabled', or 'all'.
     */
    protected function set_providers($providers = 'enabled') {
        switch ($providers) {
            case 'enabled':
                $providers = self::get_enabled_provider_data();
                break;

            case 'disabled':
                $providers = self::get_disabled_provider_data();
                break;

            case 'all':
            default:
                $providers = self::get_all_provider_data();
                break;
        }
        foreach ($providers as $provider) {
            $this->providers[$provider->id] = new provider($provider);
            $this->enabled[$provider->id] = ($provider->enabled == 1);
        }
    }

    /**
     * Filter text - convert oembed divs and links into oembed code.
     *
     * @param string $text
     * @return string
     */
    public function html_output($text) {
        $lazyload = get_config('filter_oembed', 'lazyload');
        $lazyload = $lazyload == 1 || $lazyload === false;
        $output = '';

        // Loop through each provider, endpoint, and scheme. Exit when there is a match.
        foreach ($this->providers as $provider) {

            foreach ($provider->endpoints as $endpoint) {
                // Check if schemes are definded for this provider.
                // If not use the provider url for creating a regex.
                if (!empty($endpoint->schemes)) {
                    $regexarr = $this->create_regex_from_scheme($endpoint->schemes);
                } else {
                    $regexarr = $this->create_regex_from_scheme(array($provider->provider_url));
                }

                foreach ($regexarr as $regex) {
                    if (preg_match($regex, $text)) {
                        // If {format} is in the URL, replace it with the actual format.
                        $url2 = '&format='.$endpoint->formats[0];
                        $url = str_replace('{format}', $endpoint->formats[0], $endpoint->url) .
                               '?url='.$text.$url2;
                        $jsonret = $this->oembed_curlcall($url);
                        if (!$jsonret) {
                            $output = '';
                        } else if ($lazyload) {
                            $output = $this->oembed_getpreloadhtml($jsonret);
                        } else {
                            $output = $this->oembed_gethtml($jsonret);
                        }
                        break 3; // Done, break out of all loops.
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Create regular expressions from the providers list to check
     * for supported providers
     *
     * @param array $schemes
     */
    protected function create_regex_from_scheme(array $schemes) {

        foreach ($schemes as $scheme) {

            $url1 = preg_split('/(https?:\/\/)/', $scheme);
            $url2 = preg_split('/\//', $url1[1]);

            $regexarr = [];

            foreach ($url2 as $url) {
                $find = ['.', '*'];
                $replace = ['\.', '.*?'];
                $url = str_replace($find, $replace, $url);
                $regexarr[] = '('.$url.')';
            }

            $regex[] = '/(https?:\/\/)'.implode('\/', $regexarr).'/';
        }
        return $regex;
    }

    /**
     * Get the actual json from content provider
     *
     * @param string $www
     * @return array
     */
    protected function oembed_curlcall($www) {
        $ret = download_file_content($www, null, null, true, 300, 20, false, null, false);
        return json_decode($ret->results, true);
    }

    /**
     * Get oembed html.
     *
     * @param array $jsonarr
     * @param string $params
     * @return string
     * @throws \coding_exception
     */
    protected function oembed_gethtml($jsonarr, $params = '') {
        if ($jsonarr === null) {
            $this->warnings[] = get_string('connection_error', 'filter_oembed');
            return '';
        }

        $embed = $jsonarr['html'];

        if ($params != '') {
            $embed = str_replace('?feature=oembed', '?feature=oembed'.htmlspecialchars($params), $embed);
        }

        $output = '<div class="oembed-content">' . $embed . '</div>'; // Wrapper for responsive processing.
        return $output;
    }

    /**
     * Generate preloader html.
     * @param array $jsonarr
     * @param string $params
     * @return string
     */
    protected function oembed_getpreloadhtml(array $jsonarr, $params = '') {
        global $PAGE;
        /** @var \filter_oembed_renderer $renderer */
        $renderer = $PAGE->get_renderer('filter_oembed');

        // To surpress the loadHTML Warnings.
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($jsonarr['html']);
        libxml_use_internal_errors(false);

        // Get aspect ratio of iframe or use width in json.
        if ($dom->getElementsByTagName('iframe')->length > 0) {
            $width = $dom->getElementsByTagName('iframe')->item(0)->getAttribute('width');
            $height = $dom->getElementsByTagName('iframe')->item(0)->getAttribute('height');
            $aspectratio = self::get_aspect_ratio($width, $height);
            if ($aspectratio === 0) {
                if (isset($jsonarr['width']) && isset($jsonarr['height'])) {
                    $width = $jsonarr['width'];
                    $height = $jsonarr['height'];
                    $aspectratio = self::get_aspect_ratio($width, $height);
                    if ($aspectratio === 0) {
                        // Couldn't get a decent aspect ratio, let's go with 0.5625 (16:9).
                        $aspectratio = 0.5625;
                    }
                }
            }

            // This html is intentionally hardcoded and excluded from the mustache template as javascript relies on it.
            $jsonarr['jshtml'] = ' data-aspect-ratio = "'.$aspectratio.'" ';
        }

        return $renderer->preload($this->oembed_gethtml($jsonarr, $params), $jsonarr);
    }

    // ---- PROVIDER DATA MANAGEMENT SECTION ----

    /**
     * Function to update provider data in database with current provider sources.
     *
     * @return string Any notification messages.
     */
    public static function update_provider_data() {
        global $CFG, $DB;

        $warnings = [];
        // Is there any data currently at all?
        if ($DB->count_records('filter_oembed') <= 0) {
            // Initial load.
            try {
                self::create_initial_provider_data();
            } catch (Exception $e) {
                // Handle no initial data situation.
                $warnings[] = $e->getMessage();
                continue;
            }
        }
        return $warnings;
    }

    /**
     * Get the latest provider list from http://oembed.com/providers.json
     *
     * @return space array
     */
    protected static function download_providers() {
        // Wondering if there is any reason to make this configurable?
        $www = 'http://oembed.com/providers.json';

        $timeout = 15;

        $ret = download_file_content($www, null, null, true, $timeout, 20, false, null, false);

        if ($ret->status == '200') {
            $ret = $ret->results;
        } else {
            $ret = '';
        }

        $providers = json_decode($ret, true);

        if (!is_array($providers)) {
            $providers = false;
        }

        if (empty($providers)) {
            throw new \moodle_exception('error:noproviders', 'filter_oembed', '');
        }

        return $providers;
    }

    /**
     * Function to get providers from a local, static JSON file, for last resort action.
     *
     * @return space array
     */
    protected static function get_local_providers() {
        global $CFG;

        $ret = file_get_contents($CFG->dirroot.'/filter/oembed/providers.json');
        return json_decode($ret, true);
    }

    /**
     * Function to return a list of providers provided by the current sub plugins.
     *
     * @return space array
     */
    protected static function get_plugin_providers() {
        // TODO - complete this function.
        return [];
    }

    /**
     * Create initial provider data from known provider sources.
     *
     */
    protected static function create_initial_provider_data() {
        global $CFG, $DB;

        $warnings = [];
        try {
            $providers = self::download_providers();
            $source = 'download::http://oembed.com/providers.json';
        } catch (Exception $e) {
            $warnings[] = $e->getMessage();
            // If no providers were retrieved, get the local, static ones.
            $providers = self::get_local_providers();
            if (empty($providers)) {
                throw new \moodle_exception('No initial provider data available. Oembed filter will not function properly.');
            }
            $source = 'local::'.$CFG->dirroot.'/filter/oembed/providers.json';
            continue;
        }

        // Next, add the plugin providers that exist.
        $providers = array_merge($providers, self::get_plugin_providers());

        // Load each provider into the database.
        foreach ($providers as $provider) {
            $record = new \stdClass();
            $record->provider_name = $provider['provider_name'];
            $record->provider_url = $provider['provider_url'];
            $record->endpoints = json_encode($provider['endpoints']);
            $record->source = $source;
            $record->enabled = 1;   // Enable everything by default.
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('filter_oembed', $record);
        }

        return $warnings;
    }

    // ---- OTHER HELPER FUNCTIONS ----

    /**
     * Magic method for getting properties.
     * @param string $name
     * @return mixed
     * @throws \coding_exception
     */
    public function __get($name) {
        $allowed = ['providers', 'warnings', 'enabled'];
        if (in_array($name, $allowed)) {
            return $this->$name;
        } else {
            throw new \coding_exception($name.' is not a publicly accessible property of '.get_class($this));
        }
    }

    /**
     * Set the provider to "enabled".
     *
     * @param int | provider The provider to enable.
     */
    public function enable_provider($provider) {
        $this->set_provider_enable_value($provider, 1);
    }

    /**
     * Set the provider to "disabled".
     *
     * @param int | provider The provider to disable.
     */
    public function disable_provider($provider) {
        $this->set_provider_enable_value($provider, 0);
    }

    /**
     * Set the provider enabled field to the specified value.
     *
     * @param int | object $provider The provider to modify.
     * @param int $value Value to set.
     */
    private function set_provider_enable_value($provider, $value) {
        global $DB;

        if (is_object($provider)) {
            $lookup = ['provider_name' => $provider->provider_name];
            $pid = $DB->get_field('filter_oembed', 'id', $lookup);
        } else if (is_int($provider)) {
            $lookup = ['id' => $provider];
            $pid = $provider;
        } else {
            throw new \coding_exception('oembed::enable_provider requires either a provider object or a data id integer.');
        }

        $DB->set_field('filter_oembed', 'enabled', $value, ['id' => $pid]);
        $this->enabled[$pid] = ($value == 1);
    }

    /**
     * Attempt to get aspect ratio from strings.
     * @param string $width
     * @param string $height
     * @return float|int
     */
    protected static function get_aspect_ratio($width, $height) {
        $bothperc = strpos($height, '%') !== false && strpos($width, '%') !== false;
        $neitherperc = strpos($height, '%') === false && strpos($width, '%') === false;
        // If both height and width use percentages or both don't then we can calculate an aspect ratio.
        if ($bothperc || $neitherperc) {
            // Calculate aspect ratio.
            $aspectratio = intval($height) / intval($width);
        } else {
            $aspectratio = 0;
        }
        return $aspectratio;
    }

    /**
     * Get enabled provder data from the filter table and return in decode JSON format.
     * Provider data is set when the plugin is installed, by scheduled tasks, by admin tools and
     * by subplugins.
     * @return array JSON decoded data.
     */
    protected static function get_enabled_provider_data() {
        global $DB;

        // Get providers from database. This includes sub-plugins.
        return $DB->get_records('filter_oembed', array('enabled' => 1));
    }

    /**
     * Get disabled provder data from the filter table and return in decode JSON format.
     * Provider data is set when the plugin is installed, by scheduled tasks, by admin tools and
     * by subplugins.
     * @return array JSON decoded data.
     */
    protected static function get_disabled_provider_data() {
        global $DB;

        // Get providers from database. This includes sub-plugins.
        return $DB->get_records('filter_oembed', array('enabled' => 0));
    }

    /**
     * Get all provder data from the filter table and return in decode JSON format.
     * Provider data is set when the plugin is installed, by scheduled tasks, by admin tools and
     * by subplugins.
     * @return array JSON decoded data.
     */
    protected static function get_all_provider_data() {
        global $DB;

        // Get providers from database. This includes sub-plugins.
        return $DB->get_records('filter_oembed');
    }
}
