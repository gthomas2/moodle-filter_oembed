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
define(['jquery', 'core/notification', 'core/ajax', 'core/templates', 'core/fragment', 'core/str',
    'filter_oembed/modal_confirm', 'filter_oembed/list'],
    function($, notification, ajax, templates, fragment, str, modalConfirm, List) {
        return {

            prevEditId: null,

            /**
             * Reload provider row.
             * @param {int} pid
             * @param {jQuery} row
             * @param {string|null} action
             * @param {function|null} callback
             */
            reloadRow: function(pid, row, action, callback) {
                action = !action ? 'reload' : action;
                ajax.call([
                    {
                        methodname: 'filter_oembed_provider_manage',
                        args: {
                            pid: pid,
                            action: action
                        },
                        done: function(response) {
                            // Update row.
                            templates.render('filter_oembed/managementpagerow', response.providermodel)
                                .done(function(result) {
                                    $(row).replaceWith(result);
                                    row = $('#oembed-display-providers_' + pid);
                                    if (typeof(callback) === 'function') {
                                        callback(row);
                                    }
                                });
                        },
                        fail: function(response) {
                            notification.exception(response);
                        }
                    }
                ], true, true);
            },

            /**
             * Reload all providers.
             */
            reloadProviders: function() {
                ajax.call([
                    {
                        methodname: 'filter_oembed_providers',
                        args: {
                            scope: 'all'
                        },
                        done: function(response) {
                            // Update table.
                            templates.render('filter_oembed/managementpage', response)
                                .done(function(result) {
                                    var resultHtml = $($.parseHTML(result)).html();
                                    $('#providermanagement').html(resultHtml);
                                });
                        },
                        fail: function(response) {
                            notification.exception(response);
                        }
                    }
                ], true, true);
            },

            /**
             * Listen for enable / disable action.
             */
            listenEnableDisable: function() {
                var self = this;
                $('#providermanagement').on('click', '.oembed-provider-actions .filter-oembed-visibility', function(e) {
                    e.preventDefault();

                    var row = $(this).parents('tr')[0];
                    var pid = $(row).data('pid');
                    var enabled = !$(row).hasClass('dimmed_text');
                    var action = enabled ? 'disable' : 'enable';

                    self.reloadRow(pid, row, action);
                });
            },

            /**
             * Listen for delete action.
             */
            listenDelete: function() {
                var onConfirm = function(dialog, row) {
                    // Hide dialog.
                    dialog.hide();
                    dialog.destroy();

                    var pid = $(row).data('pid');

                    ajax.call([
                        {
                            methodname: 'filter_oembed_provider_manage',
                            args: {
                                pid: pid,
                                action: 'delete'
                            },
                            done: function(response) {
                                // Remove row.
                                $(row).remove();
                            },
                            fail: function(response) {
                                notification.exception(response);
                            }
                        }
                    ], true, true);
                };

                $('#providermanagement').on('click', '.oembed-provider-actions .filter-oembed-delete', function(e) {
                    e.preventDefault();

                    var row = $(this).parents('tr')[0];
                    var providerName = $($(this).parents('td').find('.list-providername')[0]).text();

                    str.get_strings([
                        {key: 'deleteprovidertitle', component: 'filter_oembed'},
                        {key: 'deleteproviderconfirm', component: 'filter_oembed', param: providerName},
                    ]).done(function(strings) {
                        var delTitle = strings[0];
                        var delConf = strings[1];
                        modalConfirm.create(
                            delTitle,
                            delConf,
                            function(dialog) {
                                onConfirm(dialog, row);
                            }
                        );
                    });
                });
            },

            /**
             * Listen for edit action.
             */
            listenEdit: function() {
                var self = this;

                /**
                 * Turn editing off for a row by id
                 * @param {string} providerId
                 */
                var turnEditingOff = function(provderId) {
                    var sel = '#oembed-display-providers_' + provderId;
                    $(sel).removeClass('oembed-provider-editing');
                    $(sel + ' form').remove();
                    $(sel + ' td div.alert').remove();
                };

                /**
                 * Update the provider form with data.
                 * @param string data - serialized form data.
                 */
                var updateProviderForm = function(pid, data, callback) {

                    var rx = new RegExp('(?:course-)(\\S)');
                    var result = rx.exec($('body').attr('class'));
                    var contextid = parseInt(result[1]);
                    var params;
                    if (data) {
                        params = {formdata: data, pid: pid};
                    } else {
                        params = {pid: pid};
                    }

                    fragment.loadFragment('filter_oembed', 'provider', contextid, params).done(
                        function(html, js) {
                            $('#oembed-display-providers_' + pid).addClass('oembed-provider-editing');
                            templates.replaceNodeContents(
                                $('#oembed-display-providers_' + pid + ' .oembed-provider-details'),
                                html,
                                js
                            );
                            if (typeof(callback) === 'function') {
                                callback();
                            }
                        }
                    );
                };

                 // Listen for click cancel.
                $('#providermanagement').on('click', '.oembed-provider-actions .filter-oembed-edit', function(e) {
                    e.preventDefault();

                    var row = $(this).parents('tr')[0];
                    var pid = $(row).data('pid');

                    // Remove editing class from current row / previous row and delete form.
                    if (self.prevEditId !== null) {
                        turnEditingOff(self.prevEditId);
                        turnEditingOff(pid);
                    }

                    self.prevEditId = pid;

                    updateProviderForm(pid);
                });

                 // Listen for form click submit.
                $('#providermanagement').on('click', '.oembed-provider-details form #id_submitbutton', function(e) {
                    e.preventDefault();
                    var row = $(this).parents('tr')[0];
                    var pid = $(row).data('pid');
                    var form = $(this).parents('form')[0];
                    var source = $(form).find('input[name="source"]').val();

                    $(form).trigger('save-form-state');
                    var data = $(form).serialize();
                    updateProviderForm(pid, data, function() {
                        var successSel = '#oembed-display-providers_' + pid + ' .oembed-provider-details div.alert-success';
                        if ($(successSel).length) {
                            var successHTML = $(successSel)[0].outerHTML;
                            turnEditingOff(pid);

                            if (source.indexOf('download::') > -1) {
                                // When a downloaded provider is saved, a new one is created as a local provider, so we
                                // need to reload the full list.
                                self.reloadProviders();
                            } else {
                                self.reloadRow(pid, row, 'reload', function() {
                                    var rowcell = $('#oembed-display-providers_' + pid + ' td');
                                    $(rowcell).append(successHTML);
                                });
                            }
                        }
                    });
                });

                 // Listen for form click cancel.
                $('#providermanagement').on('click', '.oembed-provider-details form #id_cancel', function(e) {
                    e.preventDefault();
                    var row = $(this).parents('tr')[0];
                    turnEditingOff($(row).data('pid'));
                });
            },

            /**
             * Initialise.
             */
            init: function() {
                var options = {
                    valueNames: [ 'list-providername']
                };

                new List('providermanagement', options);

                this.listenEnableDisable();
                this.listenDelete();
                this.listenEdit();
            }
        };
    }
);
