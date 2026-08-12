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

/**
 * Claude Opus 4.1 AI model.
 *
 * High-capability model for complex analytical tasks.
 *
 * @package    aiprovider_anthropic
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class claudeopus41 extends abstract_claude_model {
    #[\Override]
    public function get_model_name(): string {
        return 'claude-opus-4-1-20250805';
    }

    #[\Override]
    public function get_model_display_name(): string {
        return 'Claude Opus 4.1';
    }

    #[\Override]
    public function supports_temperature(): bool {
        return true;
    }
}
