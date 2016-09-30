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
 * General lib file
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2016 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_oembed\forms\provider;

/**
 * Serve the edit form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function filter_oembed_output_fragment_provider($args) {
    global $OUTPUT, $CFG;

    $oembed = \filter_oembed\service\oembed::get_instance('all');

    $data = null;
    $ajaxdata = null;
    if ($args['formdata']) {
        $data = [];
        parse_str($args['formdata'], $data);
        if ($data) {
            $ajaxdata = $data;
        }
    } else {
        if (!isset($args['pid'])) {
            throw new coding_exception('missing "pid" param');
        } else {
            $data = $oembed->get_provider_row($args['pid']);
            if (!$data) {
                throw new coding_exception('Invalid "pid" param', $args['pid']);
            }
            $data = (array) $data;
        }
    }

    if (!isset($ajaxdata['enabled'])) {
        $ajaxdata['enabled'] = 0;
    }
    $actionurl = $CFG->wwwroot.'/filter/oembed/manageproviders.php';
    $form = new provider($actionurl, null, 'post', '', null, true, $ajaxdata);
    $form->validate_defined_fields(true);
    $form->set_data($data);

    $msg = '';
    if (!empty($ajaxdata)) {
        if ($form->is_validated()) {
            $success = $oembed->update_provider_row($ajaxdata);
            // TODO - localize.
            if ($success) {
                // TODO - WHY IS OUTPUT FAILING TO RETURN ANYTHING FROM THE NOTIFICATION FUNCTION!!!!!??????
                //$msg = $OUTPUT->notification('Successfully updated provider row', 'notifysuccess');
                $msg = '<div class="alert alert-success">Successfully updated provider row !</div>';
            } else {
                //$msg = $OUTPUT->notification('Failed to update provider row', 'notifyproblem');
                $msg = '<div class="alert alert-danger">Failed to update provider row !</div>';
            }
        }
    }

    return $form->render().$msg;
    
}
