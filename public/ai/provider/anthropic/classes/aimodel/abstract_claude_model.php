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

namespace aiprovider_anthropic\aimodel;

use core_ai\aimodel\base;
use MoodleQuickForm;

/**
 * Abstract base class for Anthropic Claude AI models.
 *
 * Provides shared settings (max_tokens, temperature) common to all Claude models.
 *
 * @package    aiprovider_anthropic
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_claude_model extends base implements claude_base {
    /** @var int The default max_tokens value used when a model does not override it. */
    public const DEFAULT_MAX_TOKENS = 8096;

    /**
     * Get the default max_tokens value for this model.
     *
     * Models with a lower output ceiling can override this.
     *
     * @return int
     */
    public function get_default_max_tokens(): int {
        return self::DEFAULT_MAX_TOKENS;
    }

    #[\Override]
    public function get_model_settings(): array {
        return [
            'max_tokens' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_max_tokens',
                    'component' => 'aiprovider_anthropic',
                ],
                'type' => PARAM_INT,
                'default' => $this->get_default_max_tokens(),
                'help' => [
                    'identifier' => 'settings_max_tokens',
                    'component' => 'aiprovider_anthropic',
                ],
            ],
            'temperature' => [
                'elementtype' => 'text',
                'label' => [
                    'identifier' => 'settings_temperature',
                    'component' => 'aiprovider_anthropic',
                ],
                'type' => PARAM_FLOAT,
                'help' => [
                    'identifier' => 'settings_temperature',
                    'component' => 'aiprovider_anthropic',
                ],
            ],
        ];
    }

    #[\Override]
    public function add_model_settings(MoodleQuickForm $mform): void {
        $settings = $this->get_model_settings();
        foreach ($settings as $key => $setting) {
            $mform->addElement(
                $setting['elementtype'],
                $key,
                get_string($setting['label']['identifier'], $setting['label']['component']),
            );
            $mform->setType($key, $setting['type']);
            // Only apply the fallback default if a stored value has not already been merged in via set_data().
            if (array_key_exists('default', $setting) && !array_key_exists($key, $mform->_defaultValues)) {
                $mform->setDefault($key, $setting['default']);
            }
            if (isset($setting['help'])) {
                $mform->addHelpButton($key, $setting['help']['identifier'], $setting['help']['component']);
            }
        }
    }
}
