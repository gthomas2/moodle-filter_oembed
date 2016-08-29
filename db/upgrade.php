<?php
// This file is part of Moodle-oembed-Filter
//
// Moodle-oembed-Filter is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle-oembed-Filter is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle-oembed-Filter.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Filter for component 'filter_oembed'
 *
 * @package   filter_oembed
 * @copyright 2012 Matthew Cannings; modified 2015 by Microsoft, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * code based on the following filters...
 * Screencast (Mark Schall)
 * Soundcloud (Troy Williams)
 */

/**
 * Upgrades the OEmbed filter.
 *
 * @param $oldversion Version to be upgraded from.
 * @return bool Success.
 */
function xmldb_filter_oembed_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016070501) {


        // Define table filter_oembed to be created.
        $table = new xmldb_table('filter_oembed');

        // Adding fields to table filter_oembed.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('provider_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('provider_url', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('endpoints', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Adding keys to table filter_oembed.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table filter_oembed.
        $table->add_index('providernameix', XMLDB_INDEX_UNIQUE, array('provider_name'));

        // Conditionally launch create table for filter_oembed.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Insert the initial data elements from the instance's providers.
        $instance = oembed::get_instance();
        foreach($instance->providers as $provider) {
            $record = new stdClass();
            $record->provider_name = $provider->provider_name;
            $record->provider_url = $provider->provider_url;
            $record->endpoints = $provider->endpoints_to_json();
            $record->source = 'oembed.com/providers.json';
            $record->enabled = 1;
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('filter_oembed', $record);
        }

        // Oembed savepoint reached.
        upgrade_plugin_savepoint(true, 2016070501, 'filter', 'oembed');
    }

    return true;
}