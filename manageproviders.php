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

echo $OUTPUT->header();

$table = new flexible_table('oembed-display-providers');

$table->define_columns(array('provider', 'actions'));
$table->define_headers(array(get_string('provider', 'filter_oembed'), get_string('actions', 'moodle')));
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'oembedproviders');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_class('provider', 'provider');
$table->column_class('actions', 'actions');

$table->setup();

foreach($oembed->providers as $pid => $provider) {
    $class = '';
    $providertitle = s($provider->provider_name);

    $viewlink = html_writer::link($provider->provider_url, $providertitle);

/*    $feedinfo = '<div class="title">' . $viewlink . '</div>' .
        '<div class="url">' . html_writer::link($feed->url, $feed->url) .'</div>' .
        '<div class="description">' . $feed->description . '</div>'; */

    $editurl = new moodle_url('/filter/oembed/manageproviders.php?action=edit&pid=' . $pid . '&sesskey=' . sesskey());
    $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));

    if ($oembed->enabled[$pid]) {
        $hideurl = new moodle_url('/filter/oembed/manageproviders.php?action=disable&pid=' . $pid . '&sesskey=' . sesskey());
        $enableaction = $OUTPUT->action_icon($hideurl, new pix_icon('t/hide', get_string('hide')));
    } else {
        $showurl = new moodle_url('/filter/oembed/manageproviders.php?action=enable&pid=' . $pid . '&sesskey=' . sesskey());
        $enableaction = $OUTPUT->action_icon($showurl, new pix_icon('t/show', get_string('show')));
        $class = 'dimmed_text';
    }

    $deleteurl = new moodle_url('/filter/oembed/manageproviders.php?action=delete&pid=' . $pid . '&sesskey=' . sesskey());
    $deleteicon = new pix_icon('t/delete', get_string('delete'));
    $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('deleteproviderconfirm', 'filter_oembed')));

    $providericons = $enableaction . ' ' . $editaction . ' ' . $deleteaction;

    $table->add_data(array($viewlink, $providericons), $class);
}

$table->print_html();

echo $OUTPUT->footer();
