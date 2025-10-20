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

namespace aiprovider_anthropic;

use core_ai\aiactions;
use core_ai\form\action_settings_form;
use core_ai\rate_limiter;
use Psr\Http\Message\RequestInterface;

/**
 * Class provider.
 *
 * @package    aiprovider_anthropic
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider extends \core_ai\provider {
    /**
     * Get the list of actions that this provider supports.
     *
     * @return array An array of action class names.
     */
    public static function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
            \core_ai\aiactions\summarise_text::class,
            \core_ai\aiactions\explain_text::class,
        ];
    }

    #[\Override]
    public function add_authentication_headers(RequestInterface $request): RequestInterface {
        return $request
            ->withAddedHeader('x-api-key', $this->config['apikey'])
            ->withAddedHeader('anthropic-version', '2023-06-01');
    }

    #[\Override]
    public static function get_action_settings(
        string $action,
        array $customdata = [],
    ): action_settings_form|bool {
        $actionname = substr($action, (strrpos($action, '\\') + 1));
        $customdata['actionname'] = $actionname;
        $customdata['action'] = $action;
        if ($actionname === 'generate_text' || $actionname === 'summarise_text' || $actionname === 'explain_text') {
            return new form\action_generate_text_form(customdata: $customdata);
        }

        return false;
    }

    #[\Override]
    public static function get_action_setting_defaults(string $action): array {
        $actionname = substr($action, (strrpos($action, '\\') + 1));
        $customdata = [
            'actionname' => $actionname,
            'action' => $action,
            'providername' => 'aiprovider_anthropic',
        ];
        if ($actionname === 'generate_text' || $actionname === 'summarise_text' || $actionname === 'explain_text') {
            $mform = new form\action_generate_text_form(customdata: $customdata);
            $defaults = $mform->get_defaults();

            // Add default max_tokens for the default model (Claude Sonnet 4).
            // This ensures new actions work without requiring configuration first.
            if (!isset($defaults['max_tokens'])) {
                $defaults['max_tokens'] = 16384;
            }

            return $defaults;
        }

        return [];
    }

    /**
     * Check if this request is allowed given rate limiting settings.
     *
     * @param aiactions\base $action The action to check.
     * @return array|bool True if allowed, array with error details if not.
     */
    #[\Override]
    public function is_request_allowed(aiactions\base $action): array|bool {
        $ratelimiter = \core\di::get(rate_limiter::class);
        $component = \core\component::get_component_from_classname(get_class($this));

        // Check the user rate limit.
        if (!empty($this->config['enableuserratelimit'])) {
            if (!$ratelimiter->check_user_rate_limit(
                component: $component,
                ratelimit: $this->config['userratelimit'],
                userid: $action->get_configuration('userid')
            )) {
                return [
                    'success' => false,
                    'errorcode' => 429,
                    'errormessage' => get_string('error_user_rate_limit', 'aiprovider_anthropic'),
                ];
            }
        }

        // Check the global rate limit.
        if (!empty($this->config['enableglobalratelimit'])) {
            if (!$ratelimiter->check_global_rate_limit(
                component: $component,
                ratelimit: $this->config['globalratelimit']
            )) {
                return [
                    'success' => false,
                    'errorcode' => 429,
                    'errormessage' => get_string('error_global_rate_limit', 'aiprovider_anthropic'),
                ];
            }
        }

        return true;
    }

    /**
     * Check this provider has the minimal configuration to work.
     *
     * @return bool Return true if configured.
     */
    public function is_provider_configured(): bool {
        return !empty($this->config['apikey']);
    }
}
