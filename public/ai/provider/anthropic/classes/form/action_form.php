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

use aiprovider_anthropic\helper;
use core_ai\form\action_settings_form;

/**
 * Base action settings form for Anthropic provider.
 *
 * @package    aiprovider_anthropic
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_form extends action_settings_form {
    /**
     * @var array Action configuration.
     */
    protected array $actionconfig;
    /**
     * @var string|null Return URL.
     */
    protected ?string $returnurl;
    /**
     * @var string Action name.
     */
    protected string $actionname;
    /**
     * @var string Action class.
     */
    protected string $action;
    /**
     * @var int Provider ID.
     */
    protected int $providerid;
    /**
     * @var string Provider name.
     */
    protected string $providername;

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $this->actionconfig = $this->_customdata['actionconfig']['settings'] ?? [];
        $this->returnurl = $this->_customdata['returnurl'] ?? null;
        $this->actionname = $this->_customdata['actionname'];
        $this->action = $this->_customdata['action'];
        $this->providerid = $this->_customdata['providerid'] ?? 0;
        $this->providername = $this->_customdata['providername'] ?? 'aiprovider_anthropic';

        $mform->addElement('header', 'generalsettingsheader', get_string('general', 'core'));
    }

    #[\Override]
    public function set_data($data): void {
        if (!empty($data['modelextraparams'])) {
            $data['modelextraparams'] = json_encode(json_decode($data['modelextraparams']), JSON_PRETTY_PRINT);
        }

        // Handle model template selection for form loading.
        if (isset($data['model'])) {
            $modellist = $this->get_model_list();
            // Check if the model is in our predefined list.
            if (array_key_exists($data['model'], $modellist)) {
                $data['modeltemplate'] = $data['model'];
            } else {
                // If not found, use custom.
                $data['modeltemplate'] = 'custom';
                $data['custommodel'] = $data['model'];
            }
        }

        parent::set_data($data);
    }

    #[\Override]
    public function get_data(): ?\stdClass {
        $data = parent::get_data();

        if (!empty($data)) {
            // Handle custom model selection.
            if (isset($data->modeltemplate)) {
                if ($data->modeltemplate === 'custom') {
                    // Use the manually entered model name.
                    $data->model = $data->custommodel;
                } else {
                    // Use the selected predefined model.
                    $data->model = $data->modeltemplate;
                }
                // Clean up temporary fields.
                unset($data->custommodel);
                unset($data->modeltemplate);
            }

            // Cast optional numeric fields: empty string → null (filtered out), "0" → 0 (kept).
            // This matches OpenAI's approach using PARAM_RAW + manual casting.
            if (isset($data->temperature)) {
                $data->temperature = $data->temperature !== '' ? floatval($data->temperature) : null;
            }
            if (isset($data->top_p)) {
                $data->top_p = $data->top_p !== '' ? floatval($data->top_p) : null;
            }
            if (isset($data->top_k)) {
                $data->top_k = $data->top_k !== '' ? intval($data->top_k) : null;
            }

            // Remove null values (blank fields).
            $data = (object) array_filter((array) $data, function($value) {
                return $value !== null;
            });
        }

        return $data;
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // Validate custom model name.
        if (isset($data['modeltemplate']) && $data['modeltemplate'] === 'custom') {
            if (empty($data['custommodel'])) {
                $errors['custommodel'] = get_string('required');
            } elseif (!str_starts_with($data['custommodel'], 'claude-')) {
                $errors['custommodel'] = get_string('error_invalid_model_name', 'aiprovider_anthropic');
            }
        }

        // Validate the model name starts with "claude-" (for backward compatibility).
        if (!empty($data['model']) && !str_starts_with($data['model'], 'claude-')) {
            $errors['model'] = get_string('error_invalid_model_name', 'aiprovider_anthropic');
        }

        // Validate max_tokens is positive.
        if (isset($data['max_tokens']) && $data['max_tokens'] <= 0) {
            $errors['max_tokens'] = get_string('error_max_tokens_positive', 'aiprovider_anthropic');
        }

        // Validate max_tokens against model-specific limits.
        if (isset($data['max_tokens']) && isset($data['modeltemplate']) && $data['modeltemplate'] !== 'custom') {
            $modelclass = helper::get_model_class($data['modeltemplate']);
            if ($modelclass) {
                $settings = $modelclass->get_model_settings();
                if (isset($settings['max_tokens']['max'])) {
                    $maxlimit = $settings['max_tokens']['max'];
                    if ($data['max_tokens'] > $maxlimit) {
                        $errors['max_tokens'] = get_string(
                            'error_max_tokens_exceeds_limit',
                            'aiprovider_anthropic',
                            ['max' => $maxlimit],
                        );
                    }
                }
            }
        }

        // Validate temperature range (0.0 - 1.0).
        if (!empty($data['temperature']) && ($data['temperature'] < 0.0 || $data['temperature'] > 1.0)) {
            $errors['temperature'] = get_string('error_temperature_range', 'aiprovider_anthropic');
        }

        // Validate top_p range (0.0 - 1.0).
        if (!empty($data['top_p']) && ($data['top_p'] < 0.0 || $data['top_p'] > 1.0)) {
            $errors['top_p'] = get_string('error_top_p_range', 'aiprovider_anthropic');
        }

        // Validate top_k is positive.
        if (!empty($data['top_k']) && $data['top_k'] <= 0) {
            $errors['top_k'] = get_string('error_top_k_positive', 'aiprovider_anthropic');
        }

        // Validate the extra parameters if they exist.
        if (!empty($data['modelextraparams'])) {
            json_decode($data['modelextraparams']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['modelextraparams'] = get_string('invalidjson', 'aiprovider_anthropic');
            }
        }

        return $errors;
    }

    /**
     * Add model dropdown field to the form.
     */
    protected function add_model_field(): void {
        global $PAGE;

        // Include JavaScript module.
        $PAGE->requires->js_call_amd('aiprovider_anthropic/modelchooser', 'init');

        $mform = $this->_form;

        // Determine which model to use as the default.
        if (!empty($this->actionconfig['model']) &&
                !array_key_exists($this->actionconfig['model'], $this->get_model_list())) {
            $defaultmodel = 'custom';
        } else if (empty($this->actionconfig['model'])) {
            $defaultmodel = 'claude-sonnet-4-20250514';
        } else {
            $defaultmodel = $this->actionconfig['model'];
        }

        // Model chooser dropdown.
        $models = $this->get_model_list();
        $mform->addElement(
            'select',
            'modeltemplate',
            get_string("action:{$this->actionname}:model", 'aiprovider_anthropic'),
            $models,
        );
        $mform->setType('modeltemplate', PARAM_TEXT);
        $mform->addRule('modeltemplate', null, 'required', null, 'client');
        $mform->setDefault('modeltemplate', $defaultmodel);
        $mform->addHelpButton('modeltemplate', "action:{$this->actionname}:model", 'aiprovider_anthropic');

        // Hidden field for the actual model value.
        $mform->addElement('hidden', 'model', $defaultmodel);
        $mform->setType('model', PARAM_TEXT);

        // Custom model name field (shown only when "custom" is selected).
        $mform->addElement('text', 'custommodel', get_string('custom_model_name', 'aiprovider_anthropic'));
        $mform->setType('custommodel', PARAM_TEXT);
        $mform->setDefault('custommodel', $this->actionconfig['model'] ?? '');
        $mform->addHelpButton('custommodel', 'custom_model_name', 'aiprovider_anthropic');
        $mform->hideIf('custommodel', 'modeltemplate', 'neq', 'custom');

        // Hidden submit button for model switching (triggered by JavaScript).
        $mform->registerNoSubmitButton('updateactionsettings');
        $mform->addElement(
            'submit',
            'updateactionsettings',
            'updateactionsettings',
            ['data-modelchooser-field' => 'updateButton', 'class' => 'd-none']
        );
    }

    /**
     * Get the list of Claude models.
     *
     * @return array List of models (model_name => display_name).
     */
    protected function get_model_list(): array {
        $models = [];
        // Add "Custom" option first.
        $models['custom'] = get_string('custom', 'core_form');
        foreach (helper::get_model_classes() as $class) {
            $model = new $class();
            $models[$model->get_model_name()] = $model->get_model_display_name();
        }
        return $models;
    }
}
