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
 * Confirm / cancel modal.
 */
define(['jquery', 'core/modal_factory', 'core/templates'], function($, modalFactory, templates) {
    var modalConfirm = {

        create: function(dialogTitle, dialogBody, cbOK, cbCancel) {
            var dialog = null;

            templates.render('filter_oembed/buttons_confirm', {}).done(function(dialogFooter) {
                modalFactory.create({
                    title: dialogTitle,
                    body: dialogBody,
                    footer: dialogFooter,
                    large: false
                }).done(function(modal) {
                    dialog = modal;
                    // Apply callbacks to buttons.
                    if (typeof(cbOK) === 'function') {
                        var el = dialog.getModal();
                        el.find('.modal-footer button[data-action="confirm"]').click(function() {
                            cbOK(dialog);
                        });
                    }
                    if (typeof(cbCancel) !== 'function') {
                        cbCancel = function(dialog) {
                            dialog.hide();
                            dialog.destroy();
                        };
                    }
                    var el = dialog.getModal();
                    el.find('.modal-footer button[data-action="cancel"]').click(function() {
                        cbCancel(dialog);
                    });
                    // Display the dialogue.
                    dialog.show();
                });
            });

            return dialog;
        }
    };

    return modalConfirm;
});
