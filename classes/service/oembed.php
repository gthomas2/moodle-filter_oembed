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
        static $instance = [];
        if (isset($instance[$providers])) {
            return $instance[$providers];
        } else {
            $instance[$providers] = new oembed($providers);
            return $instance;
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

        // Loop through each provider asking for a match.
        foreach ($this->providers as $provider) {
            if (($completeoutput = $provider->filter($text)) !== false) {
                // Plugins may provide everything required. If so, just return it.
                $output = $completeoutput;
                break;
            } else if ($requesturl = $provider->get_oembed_request($text)) {
                // If we have a consumer request, we're done searching. Try for a response.
                $jsonret = $provider->oembed_response($requesturl);
                if (!$jsonret) {
                    $output = '';
                } else if ($lazyload) {
                    $output = $this->oembed_getpreloadhtml($jsonret);
                } else {
                    $output = $this->oembed_gethtml($jsonret);
                }
                break; // Done, break out of all loops.
            }
        }
        return $output;
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
        /** @var \filter_oembed\output\renderer $renderer */
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
        global $DB;

        $warnings = [];
        // Is there any data currently at all?
        if ($DB->count_records('filter_oembed') <= 0) {
            // Initial load.
            try {
                self::create_initial_provider_data();
            } catch (Exception $e) {
                // Handle no initial data situation.
                $warnings[] = $e->getMessage();
            }
        } else {
            // Update all existing provider data.
            try {
                $providers = self::download_providers();
                $source = 'download::http://oembed.com/providers.json';
            } catch (Exception $e) {
                $warnings[] = $e->getMessage();
                $providers = [];
            }
            mtrace('    Checking for updated downloads...');
            self::update_downloaded_providers($providers);
            mtrace('    Checking for updated subplugins...');
            self::update_plugin_providers(self::get_plugin_providers());
        }

        // If no providers were retrieved, log the issue.
        // self::log_issues($warnings);
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

        $ret = file_get_contents($CFG->dirroot.'/filter/oembed/provider/providers.json');
        return json_decode($ret, true);
    }

    /**
     * Function to return a list of providers provided by the current sub plugins.
     * Since Moodle doesn't currently support subplugins for filters, do this in this plugin.
     *
     * @return space array
     */
    protected static function get_plugin_providers() {
        global $CFG;

        $pluginproviders = [];
        $path = $CFG->dirroot.'/filter/oembed/provider/';
        $thisdir = new \DirectoryIterator($path);
        foreach ($thisdir as $dir) {
            if ($dir->isDir()) {
                $name = $dir->getFilename();
                if (($name != '.') && ($name != '..')) {
                    require_once($CFG->dirroot.'/filter/oembed/provider/'.$name.'/'.$name.'.php');
                    $classname = "\\filter_oembed\\provider\\{$name}";
                    $newplugin = new $classname();
                    $pluginproviders[] = array_merge($newplugin->implementation(), ['plugin' => $name]);
                }
            }
        }
        return $pluginproviders;
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
            $source = 'local::'.$CFG->dirroot.'/filter/oembed/provider/providers.json';
        }

        // Load each downloaded provider into the database.
        self::update_downloaded_providers($providers, $source);

        // Next, add the plugin providers that exist.
        $providers = self::get_plugin_providers();
        $source = 'plugin::';
        foreach ($providers as $provider) {
            $record = new \stdClass();
            $record->providername = $provider['provider_name'];
            $record->providerurl = $provider['provider_url'];
            $record->endpoints = json_encode($provider['endpoints']);
            $record->source = $source.$provider['plugin'];
            $record->enabled = 1;   // Enable everything by default.
            $record->timecreated = time();
            $record->timemodified = time();
            if ($DB->record_exists('filter_oembed', ['providername' => $record->providername])) {
                // If a provider already exists with this name, append the plugin name.
                $record->providername .= ' (' . $record->source . ')';
            }
            $DB->insert_record('filter_oembed', $record);
        }

        return $warnings;
    }

    /**
     * Update the database with the downloaded provider data.
     *
     * @param array $providers The JSON decoded provider data.
     * @param string $source The source name for the provided providers.
     */
    private static function update_downloaded_providers(array $providers, $source = null) {
        global $DB;

        if ($source === null) {
            $source = 'download::http://oembed.com/providers.json';
        }

        // Get current providers as array indexed by name.
        $cols = 'providername,id,providerurl,endpoints,source,enabled,timecreated,timemodified';
        $currentproviders = self::get_all_provider_data($cols);

        foreach ($providers as $provider) {
            if (isset($currentproviders[$provider['provider_name']])) {
                // Existing provider exists; check for update.
                $currprovider = $currentproviders[$provider['provider_name']];
                $change = false;

                if ($currprovider->providerurl != $provider['provider_url']) {
                    // Perform change URL actions.
                    $currprovider->providerurl = $provider['provider_url'];
                    $change = true;
                }

                $endpoints = json_encode($provider['endpoints']);
                if ($currprovider->endpoints != $endpoints) {
                    // Perform change endpoints actions.
                    $currprovider->endpoints = $endpoints;
                    $change = true;
                }

                if ($change) {
                    mtrace('      updating '.$currprovider->providername);
                    $currprovider->timemodified = time();
                    $DB->update_record('filter_oembed', $currprovider);
                }
                unset($currentproviders[$provider['provider_name']]);
            } else {
                // New provider.
                $record = new \stdClass();
                $record->providername = $provider['provider_name'];
                $record->providerurl = $provider['provider_url'];
                $record->endpoints = json_encode($provider['endpoints']);
                $record->source = $source;
                $record->enabled = 1;   // Enable everything by default.
                $record->timecreated = time();
                $record->timemodified = time();
                mtrace('      creating '.$record->providername);
                $DB->insert_record('filter_oembed', $record);
            }
        }

        // Any current providers left must have been deleted if they have the same source.
        foreach ($currentproviders as $providername => $providerdata) {
            if ($providerdata->source == $source) {
                // Perform delete provider actions.
                mtrace('      deleting '.$providerdata->providername);
                $DB->delete_records('filter_oembed', ['id' => $providerdata->id]);
            }
        }
    }

    /**
     * Update the database with the plugin provider data.
     *
     * @param array $providers The JSON decoded provider data.
     */
    private static function update_plugin_providers(array $providers) {
        global $DB;

        $source = 'plugin::';

        // Get current providers as array indexed by name.
        $cols = 'providername,id,providerurl,endpoints,source,enabled,timecreated,timemodified';
        $currentproviders = self::get_all_provider_data($cols);

        foreach ($providers as $provider) {
            if (isset($currentproviders[$provider['provider_name']])) {
                // Existing provider exists, remove for delete check.
                unset($currentproviders[$provider['provider_name']]);
            } else {
                // New provider.
                $record = new \stdClass();
                $record->providername = $provider['provider_name'];
                $record->providerurl = $provider['provider_url'];
                $record->endpoints = json_encode($provider['endpoints']);
                $record->source = $source.$provider['plugin'];
                $record->enabled = 1;   // Enable everything by default.
                $record->timecreated = time();
                $record->timemodified = time();
                mtrace('      creating '.$record->providername);
                $DB->insert_record('filter_oembed', $record);
            }
        }

        // Any current plugin providers left must have been deleted if they have the same source.
        foreach ($currentproviders as $providername => $providerdata) {
            if (strpos($providerdata->source, $source) === 0) {
                // Perform delete provider actions.
                mtrace('      deleting '.$providerdata->providername);
                $DB->delete_records('filter_oembed', ['id' => $providerdata->id]);
            }
        }
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
            $lookup = ['providername' => $provider->providername];
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
     * Get enabled provder data from the filter table and return as array of data records.
     * Provider data is set when the plugin is installed, by scheduled tasks, by admin tools and
     * by subplugins.
     * @return array data records.
     */
    protected static function get_enabled_provider_data() {
        global $DB;

        // Get providers from database. This includes sub-plugins.
        return $DB->get_records('filter_oembed', array('enabled' => 1));
    }

    /**
     * Get disabled provder data from the filter table and return as array of data records.
     * Provider data is set when the plugin is installed, by scheduled tasks, by admin tools and
     * by subplugins.
     * @return array data records.
     */
    protected static function get_disabled_provider_data() {
        global $DB;

        // Get providers from database. This includes sub-plugins.
        return $DB->get_records('filter_oembed', array('enabled' => 0));
    }

    /**
     * Get all provder data from the filter table and return as array of data records.
     * Provider data is set when the plugin is installed, by scheduled tasks, by admin tools and
     * by subplugins.
     *
     * @param string $fields Comma separated list of fields, the first of which is the index of the returned array.
     * @return array data records.
     */
    protected static function get_all_provider_data($fields = '*') {
        global $DB;

        // Get providers from database. This includes sub-plugins.
        return $DB->get_records('filter_oembed', null, '', $fields);
    }
}