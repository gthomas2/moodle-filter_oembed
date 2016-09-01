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
 * @package filter_oembed
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 The POET Group
 */

namespace filter_oembed\provider;

/**
 * Base class for oembed providers.
 */
class provider {
    /**
     * @var string
     */
    protected $provider_name = '';

    /**
     * @var string
     */
    protected $provider_url = '';

    /**
     * @var endpoints
     */
    protected $endpoints = [];

    /**
     * Constructor.
     * @param $data JSON decoded array or a data object containing all provider data.
     */
    public function __construct($data = null) {
        if (is_object($data)) {
            $data = (array)$data;
        }
        $this->provider_name = $data['provider_name'];
        $this->provider_url = $data['provider_url'];

        // If the endpoint data is a string, assume its a json encoded string.
        if (is_string($data['endpoints'])) {
            $data['endpoints'] = json_decode($data['endpoints'], true);
        }
        if (is_array($data['endpoints'])) {
            foreach ($data['endpoints'] as $endpoint) {
                $this->endpoints[] = new endpoint($endpoint);
            }
        } else {
            throw new \coding_exception('"endpoint" data must be an array for '.get_class($this));
        }
    }

    /**
     * If a matching endpoint scheme is found in the passed text, return a consumer request URL.
     * and return the resulting JSON.
     *
     * @param string $text The text to look for a scheme match.
     * @return array JSON response.
     */
    public function get_oembed_request($text) {
        $requesturl = '';
        // For each endpoint, look for a matching scheme.
        foreach ($this->endpoints as $endpoint) {
            // Get the regex arrauy to look for matching schemes.
            $regexarr = $this->endpoints_regex($endpoint);

            foreach ($regexarr as $regex) {
                if (preg_match($regex, $text)) {
                    // If {format} is in the URL, replace it with the actual format.

                    //$url2 = '&format='.$endpoint->formats[0];
                    //$url = str_replace('{format}', $endpoint->formats[0], $endpoint->url) .
                    //       '?url='.$text.$url2;

                    // At the moment, we're only supporting JSON, so this must be JSON.
                    $requesturl = str_replace('{format}', 'json', $endpoint->url) .
                           '?url=' . $text . '&format=json';
                    break 2; // Done, break out of all loops.
                }
            }
        }

        return $requesturl;
    }

    /**
     * Return the JSON provider response.
     *
     * @param string $url The consumer request URL.
     * @return array JSON response.
     */
    public function oembed_response($url) {
        $ret = download_file_content($url, null, null, true, 300, 20, false, null, false);
        return json_decode($ret->results, true);
    }

    /**
     * Return a regular expression that can be used to search text for an endpoint's schemes.
     *
     * @param endpoint $endpoint
     * @return array Array of regular expressions matching all enpoints and schemes.
     */
    protected function endpoints_regex(endpoint $endpoint) {
        $schemes = $endpoint->schemes;
        if (empty($schemes)) {
            $schemes = [$this->provider_url];
        }

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
     * Magic method for getting properties.
     * @param string $name
     * @return mixed
     * @throws \coding_exception
     */
    public function __get($name) {
        $allowed = ['provider_name', 'provider_url', 'endpoints'];
        if (in_array($name, $allowed)) {
            return $this->$name;
        } else {
            throw new \coding_exception($name.' is not a publicly accessible property of '.get_class($this));
        }
    }

    /**
     * Function to turn an endpoint into JSON, since json_encode doesn't work on objects.
     * @return JSON encoded array.
     */
    public function endpoints_to_json() {
        $endpointsarr = [];
        foreach ($this->endpoints as $endpoint) {
            $endpointsarr[] = [
                'schemes' => $endpoint->schemes,
                'url' => $endpoint->url,
                'discovery' => $endpoint->discovery,
                'formats' => $endpoint->formats,
            ];
        }
        return json_encode($endpointsarr);
    }
}