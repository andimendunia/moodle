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

namespace aiprovider_anthropic\form;

use aiprovider_anthropic\abstract_processor;
use aiprovider_anthropic\helper;

/**
 * Generate text action provider settings form for the Anthropic Claude provider.
 *
 * @package    aiprovider_anthropic
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_generate_text_form extends action_form {
    #[\Override]
    protected function definition(): void {
        parent::definition();
        $mform = $this->_form;

        global $PAGE;
        $PAGE->requires->js_call_amd('aiprovider_anthropic/modelchooser', 'init');

        // Model chooser.
        $defaultmodel = $this->actionconfig['model'] ?? helper::get_default_model();
        $mform->addElement(
            'select',
            'model',
            get_string("action:{$this->actionname}:model", 'aiprovider_anthropic'),
            $this->get_model_list(),
            ['data-modelchooser-field' => 'selector'],
        );
        $mform->setType('model', PARAM_TEXT);
        $mform->addRule('model', null, 'required', null, 'client');
        $mform->setDefault('model', $defaultmodel);
        $mform->addHelpButton('model', "action:{$this->actionname}:model", 'aiprovider_anthropic');

        // API endpoint.
        $defaultendpoint = $this->actionconfig['endpoint'] ?? abstract_processor::ANTHROPIC_API_ENDPOINT;
        $mform->addElement(
            'text',
            'endpoint',
            get_string("action:{$this->actionname}:endpoint", 'aiprovider_anthropic'),
            ['maxlength' => '255', 'size' => '50'],
        );
        $mform->setType('endpoint', PARAM_URL);
        $mform->addRule('endpoint', null, 'required', null, 'client');
        $mform->setDefault('endpoint', $defaultendpoint);

        // Max tokens and temperature are added via after_ai_action_settings_form_hook,
        // delegating to the selected model class (see hook_listener::
        // set_model_form_definition_for_aiprovider_anthropic()).

        // System instruction.
        $mform->addElement(
            'textarea',
            'systeminstruction',
            get_string("action:{$this->actionname}:systeminstruction", 'aiprovider_anthropic'),
            'wrap="virtual" rows="5" cols="20"',
        );
        $mform->setType('systeminstruction', PARAM_TEXT);
        $defaultinstruction = $this->actionconfig['systeminstruction'] ?? $this->action::get_system_instruction();
        $mform->setDefault('systeminstruction', $defaultinstruction);
        $mform->addHelpButton('systeminstruction', "action:{$this->actionname}:systeminstruction", 'aiprovider_anthropic');

        if ($this->returnurl) {
            $mform->addElement('hidden', 'returnurl', $this->returnurl);
            $mform->setType('returnurl', PARAM_LOCALURL);
        }

        $mform->addElement('hidden', 'action', $this->action);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'provider', $this->providername);
        $mform->setType('provider', PARAM_TEXT);

        $mform->addElement('hidden', 'providerid', $this->providerid);
        $mform->setType('providerid', PARAM_INT);

        // Hidden button the modelchooser JS clicks to resubmit the form when the model
        // changes, so the per-model settings added by the after_ai_action_settings_form_hook
        // (see hook_listener::set_model_form_definition_for_aiprovider_anthropic()) refresh
        // to match the newly selected model.
        $mform->registerNoSubmitButton('updateactionsettings');
        $mform->addElement(
            'submit',
            'updateactionsettings',
            'updateactionsettings',
            ['data-modelchooser-field' => 'updateButton', 'class' => 'd-none'],
        );

        $this->set_data($this->actionconfig);
    }
}
