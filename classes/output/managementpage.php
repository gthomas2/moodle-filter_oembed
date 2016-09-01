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

namespace filter_oembed\output;

defined('MOODLE_INTERNAL') || die();

class managementpage implements \renderable, \templatable {

    /**
     * An array of headings
     *
     * @var array
     */
    protected $headings;

    /**
     * An array of rows
     *
     * @var array
     */
    protected $rows;

    /**
     * Construct the renderable.
     * @param array $titles The array of column headings.
     * @param array $content The array of rows.
     */
    public function __construct(array $titles = array(), array $content = array()) {
        $this->headings = array();
        $colnum = 1;
        foreach ($titles as $key => $title) {
            $this->headings['title'.$colnum++] = $title;
        }
        foreach ($content as $key => $row) {
            $this->rows[] = $row;
        }
    }

    /**
     * Export the data for template.
     * @param
     */
    public function export_for_template(\renderer_base $output) {
        $data = [
            'headings' => ['title1' => $this->headings['title1'],
                           'title2' => $this->headings['title2']
                          ],
            'rows' => []
        ];

        foreach ($this->rows as $row) {
            $tmplrow = [];

            $tmplrow['pid'] = $row['pid'];
            $tmplrow['provider_name'] = $row['provider_name'];
            $tmplrow['provider_url'] = $row['provider_url'];

            // Display logic for hide/show.
            if ($row['enabled']) {
                $tmplrow['extraclass'] = '';
                $tmplrow['enableaction'] = $output->action_icon($row['enableaction'],
                    new \pix_icon('t/hide', get_string('hide')));
            } else {
                $tmplrow['extraclass'] = 'dimmed_text';
                $tmplrow['enableaction'] = $output->action_icon($row['enableaction'],
                    new \pix_icon('t/show', get_string('show')));
            }

            $tmplrow['editaction'] = $output->action_icon($row['editaction'],
                new \pix_icon('t/edit', get_string('edit')));
            $tmplrow['deleteaction'] = $output->action_icon($row['deleteaction'],
                new \pix_icon('t/delete', get_string('delete')),
                new \confirm_action(get_string('deleteproviderconfirm', 'filter_oembed')));

            // If edit requested, provide full provider data to the template.
            if (isset($row['editing']) && $row['editing']) {
                $tmplrow['editing'] = 1;
                $tmplrow['source'] = $row['source'];
                $tmplrow['schemes'] = (isset($row['schemes']) ? $row['schemes'] : '');
                $tmplrow['url'] = (isset($row['url']) ? $row['url'] : '');
                $tmplrow['discovery'] = (isset($row['discovery']) ? $row['discovery'] : '');
                $tmplrow['formats'] = (isset($row['formats']) ? $row['formats'] : '');
            } else {
                $tmplrow['editing'] = 0;
            }

            $data['rows'][] = $tmplrow;
        }
        return $data;
    }
}