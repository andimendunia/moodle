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
 * Base action settings form for the Anthropic Claude provider.
 *
 * @package    aiprovider_anthropic
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_form extends action_settings_form {
    /** @var array Action configuration. */
    protected array $actionconfig;
    /** @var string|null Return URL. */
    protected ?string $returnurl;
    /** @var string Action name. */
    protected string $actionname;
    /** @var string Action class. */
    protected string $action;
    /** @var int Provider ID. */
    protected int $providerid;
    /** @var string Provider name. */
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

    /**
     * Get the list of available models for the dropdown.
     *
     * @return array Model name => display name.
     */
    protected function get_model_list(): array {
        $models = [];
        foreach (helper::get_model_classes() as $class) {
            $model = new $class();
            $models[$model->get_model_name()] = $model->get_model_display_name();
        }
        return $models;
    }

    #[\Override]
    public function get_defaults(): array {
        $data = parent::get_defaults();
        return $data;
    }
}
