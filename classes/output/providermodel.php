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
 * @author Guy Thomas <gthomas@moodlerooms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 The POET Group
 */

namespace filter_oembed\output;

use filter_oembed\service\oembed;

defined('MOODLE_INTERNAL') || die();

/**
 * Class providermodel
 * @package filter_oembed\output
 */
class providermodel implements \renderable {

    /**
     * @var int provider row id
     */
    public $pid;

    /**
     * @var string provider name
     */
    public $providername;

    /**
     * @var string provider url
     */
    public $providerurl;

    /**
     * @var bool is this provider enabled or not
     */
    public $enabled;

    /**
     * @var string current action - enable or disable
     */
    public $enableaction;

    /**
     * @var string additional row class
     */
    public $extraclass;

    /**
     * @var string html for edit action
     */
    public $editaction;

    /**
     * @var string html for delete action
     */
    public $deleteaction;

    /**
     * @var int 1 if editing, else 0
     */
    public $editing;

    /**
     * @var string provider source
     */
    public $source;

    /**
     * @var string provider scehmes
     */
    public $schemes;

    /**
     * @var bool allow for discovery
     */
    public $discovery;

    /**
     * @var string TODO add description
     */
    public $formats;

    public function __construct($providerrow) {
        global $PAGE, $CFG, $DB;
        $PAGE->set_context(\context_system::instance());
        $output = $PAGE->get_renderer('filter_oembed', null, RENDERER_TARGET_GENERAL);

        $providerrow = (object) $providerrow;

        $oembed = \filter_oembed\service\oembed::get_instance('all');

        $this->pid = $providerrow->id;
        $this->providername = $providerrow->providername;
        $this->providerurl = $providerrow->providerurl;
        if ($providerrow->enabled) {

            // Disable action.
            $this->enabled = true;
            $this->extraclass = '';
            $action = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=disable&pid=' .
                    $providerrow->id . '&sesskey=' . sesskey();
            $this->enableaction = $output->action_icon($action,
                new \pix_icon('t/hide', get_string('hide')), null, ['class' => 'action-icon visibility']);
        } else {

            // Enable action.
            $action = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=enable&pid=' .
                    $providerrow->id . '&sesskey=' . sesskey();
            $this->extraclass = 'dimmed_text';
            $this->enableaction = $output->action_icon($action,
                new \pix_icon('t/show', get_string('show')), null, ['class' => 'action-icon visibility']);
        }

        // Edit action.
        $action = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=edit&pid=' . $providerrow->id . '&sesskey=' . sesskey();
        $this->editaction = $output->action_icon($action,
            new \pix_icon('t/edit', get_string('edit')));

        // Delete action.
        $action = $CFG->wwwroot . '/filter/oembed/manageproviders.php?action=delete&pid=' .
            $providerrow->id . '&sesskey=' . sesskey();
        $this->deleteaction = $output->action_icon($action,
            new \pix_icon('t/delete', get_string('delete')),
            new \confirm_action(get_string('deleteproviderconfirm', 'filter_oembed')));

        // If edit requested, provide full provider data to the template.
        if (!empty($providerrow->editing)) {
            /*$this->editing = 1;
            $this->source = $providerrow->source;
            $optionalprops = ['schemes', 'url', 'discovery', 'formats'];
            foreach ($optionalprops as $prop) {
                if (isset($providerrow->$prop)) {
                    $this->$prop = $providerrow->$prop;
                }
            }*/
        } else {
            $this->editing = 0;
        }
    }
}
