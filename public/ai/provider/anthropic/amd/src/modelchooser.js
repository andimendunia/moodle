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
 * Model chooser for Anthropic provider.
 *
 * Handles dynamic switching between models and triggers form submission to reload with model-specific settings.
 *
 * @module     aiprovider_anthropic/modelchooser
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        /**
         * Initialize the model chooser.
         */
        init: function() {
            const Selectors = {
                fields: {
                    selector: 'select[name="modeltemplate"]',
                    updateButton: '[data-modelchooser-field="updateButton"]',
                },
            };

            const modelSelector = document.querySelector(Selectors.fields.selector);

            if (!modelSelector) {
                return;
            }

            // Handle model selection change - trigger form submission to reload with new model settings.
            modelSelector.addEventListener('change', e => {
                const form = e.target.closest('form');
                const updateButton = form.querySelector(Selectors.fields.updateButton);

                if (updateButton) {
                    // Trigger form submission to reload with selected model's settings.
                    updateButton.click();
                }
            });
        }
    };
});
