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

use filter_oembed\db\providerrow;

defined('MOODLE_INTERNAL') || die();

class managementpage implements \renderable, \templatable {

    /**
     * An array of rows
     *
     * @var array
     */
    protected $rows;

    /**
     * Construct the renderable.
     * @param array $content The array of rows.
     */
    public function __construct(array $content = array()) {
        if (!empty($content)) {
            foreach ($content as $key => $row) {
                $this->rows[] = $row;
            }
        }
    }

    /**
     * Export the data for template.
     * @param \renderer_base $output
     */
    public function export_for_template(\renderer_base $output) {
        $data = [
            'rows' => []
        ];

        if (count($this->rows) < 1) {
            return $data;
        }

        foreach ($this->rows as $row) {
            $data['rows'][] = new providermodel($row);
        }
        return $data;
    }

}
