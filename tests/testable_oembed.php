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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_oembed\service\oembed;

/**
 * Class testable_oembed.
 *
 * @package   filter_oembed
 * @copyright Erich M. Wappis / Guy Thomas 2016
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_oembed extends oembed {

    /**
     * Singleton.
     *
     * @return oembed
     */
    public static function get_instance($providerstate = 'enabled') {
        /** @var $instance oembed */
        static $instance;
        if ($instance) {
            return $instance;
        } else {
            return new testable_oembed();
        }
    }

    /**
     * Calls the protected download_providers function.
     */
    public static function protected_download_providers() {
        return self::download_providers();
    }

    /**
     * Calls the protected get_local_providers function.
     */
    public static function protected_get_local_providers() {
        return self::get_local_providers();
    }
}
