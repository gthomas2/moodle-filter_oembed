/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   filter_oembed
 * @copyright Guy Thomas / moodlerooms.com 2016
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Oembed provider management module.
 */
define(['jquery', 'core/notification', 'core/ajax', 'core/templates', 'filter_oembed/list'],
    function($, notification, ajax, templates, List) {
        return {

            listenEnableDisable: function() {
                $('#oembedproviders').on('click', '.oembed-provider-actions .action-icon.visibility', function(e) {
                    e.preventDefault();

                    var row = $(this).parents('tr')[0];
                    var pid = $(row).data('pid');
                    var enabled = !$(row).hasClass('dimmed_text');
                    var action = enabled ? 'disable' : 'enable';

                    ajax.call([
                        {
                            methodname: 'filter_oembed_provider_manage_visibility',
                            args: {
                                pid: pid,
                                action: action
                            },
                            done: function(response) {
                                // Update row.
                                templates.render('filter_oembed/managementpagerow', response.providermodel)
                                    .done(function(result) {
                                        $(row).replaceWith(result);
                                    });
                            },
                            fail: function(response) {
                                notification.exception(response);
                            }
                        }
                    ], true, true);
                });
            },

            init: function() {
                var options = {
                    valueNames: [ 'list-providername']
                };

                new List('providermanagement', options);

                this.listenEnableDisable();
            }
        };
    }
);
