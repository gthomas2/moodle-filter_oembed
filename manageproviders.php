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
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$action = optional_param('action', '', PARAM_ALPHA);
$pid = optional_param('pid', 0, PARAM_INT);

if (!empty($action)) {
    require_sesskey();
}

$oembed = \filter_oembed\service\oembed::get_instance('all');

// Process actions.
switch ($action) {
    case 'edit':
        break;

    case 'disable':
        $oembed->disable_provider($pid);
        break;

    case 'enable':
        $oembed->enable_provider($pid);
        break;
}

$PAGE->set_context($systemcontext);
$baseurl = new moodle_url('/filter/oembed/manageproviders.php');
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('standard');
$strmanage = get_string('manageproviders', 'filter_oembed');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

$PAGE->navbar->add(get_string('filter'));
$PAGE->navbar->add(get_string('filtername', 'filter_oembed'));
$PAGE->navbar->add(get_string('manageproviders', 'filter_oembed'), $baseurl);

$output = $PAGE->get_renderer('filter_oembed');
echo $output->header();

$headings = [get_string('provider', 'filter_oembed'), get_string('actions', 'moodle')];
$rows = [];

foreach($oembed->providers as $prid => $provider) {
    $row = [];
    $row['pid'] = $prid;
    $row['provider_name'] = s($provider->provider_name);
    $row['provider_url'] = s($provider->provider_url);
    $row['editaction'] = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=edit&pid=' . $prid . '&sesskey=' . sesskey();

    if ($oembed->enabled[$prid]) {
        $row['enableaction'] = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=disable&pid=' . $prid . '&sesskey=' . sesskey();
        $row['enabled'] = true;
    } else {
        $row['enableaction'] = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=enable&pid=' . $prid . '&sesskey=' . sesskey();
        $row['enabled'] = false;
    }
    $row['deleteaction'] = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=delete&pid=' . $prid . '&sesskey=' . sesskey();

    // If edit requested, provide full provider data to the template.
    if (($action = 'edit') && ($prid == $pid)) {
        $row['editing'] = true;
        $row['source'] = $DB->get_field('filter_oembed', 'source', ['id' => $pid]);
        $endpoints = $provider->endpoints;
        foreach ($endpoints as $endpoint) {
            $row['schemes'] = $endpoint->schemes;
            $row['url'] = isset($endpoint->url) ? $endpoint->url : '';
            $row['discovery'] = isset($endpoint->discovery) ? $endpoint->discovery : '';
            $row['formats'] = isset($endpoint->formats) ? (array)$endpoint->formats : [];
        }
    }
    $rows[] = $row;
}

//echo $output->render_index($headings, $align, $content);
$managepage = new \filter_oembed\output\managementpage($headings, $rows);
echo $output->render($managepage);

// Finish the page.
echo $output->footer();