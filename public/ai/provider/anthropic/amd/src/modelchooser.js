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
 * AI provider model selection handler.
 *
 * Unlike aiprovider_gemini/openai, this provider has a single shared API endpoint and no
 * custom-model text input, so this module only needs to resubmit the form when the model
 * changes so the server-side per-model settings (e.g. temperature) can be re-evaluated.
 *
 * @module     aiprovider_anthropic/modelchooser
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const Selectors = {
    fields: {
        selector: '[data-modelchooser-field="selector"]',
        updateButton: '[data-modelchooser-field="updateButton"]',
    },
};

/**
 * Initialise the AI provider model chooser.
 */
export const init = () => {
    const modelSelector = document.querySelector(Selectors.fields.selector);
    if (!modelSelector) {
        return;
    }

    modelSelector.addEventListener('change', e => {
        const form = e.target.closest('form');
        const updateButton = form.querySelector(Selectors.fields.updateButton);
        updateButton.click();
    });
};
