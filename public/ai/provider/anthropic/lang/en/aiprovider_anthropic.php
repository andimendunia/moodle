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

/**
 * Strings for component aiprovider_anthropic, language 'en'.
 *
 * @package    aiprovider_anthropic
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Action settings for explain_text.
$string['action:explain_text:endpoint'] = 'API endpoint';
$string['action:explain_text:model'] = 'AI model';
$string['action:explain_text:model_help'] = 'The Claude model used to explain the provided text.';
$string['action:explain_text:systeminstruction'] = 'System instruction';
$string['action:explain_text:systeminstruction_help'] = 'This instruction is sent to Claude along with the user\'s prompt. Editing this instruction is not recommended unless absolutely required.';

// Custom model.
$string['custom_model_name'] = 'Custom model name';
$string['custom_model_name_help'] = 'Enter the full model name (e.g., claude-sonnet-4-20250514 or claude-opus-5-20260101). Model names must start with "claude-".';

// Action settings for generate_text.
$string['action:generate_text:endpoint'] = 'API endpoint';
$string['action:generate_text:model'] = 'AI model';
$string['action:generate_text:model_help'] = 'The Claude model used to generate the text response.';
$string['action:generate_text:systeminstruction'] = 'System instruction';
$string['action:generate_text:systeminstruction_help'] = 'This instruction is sent to Claude along with the user\'s prompt. Editing this instruction is not recommended unless absolutely required.';

// Action settings for summarise_text.
$string['action:summarise_text:endpoint'] = 'API endpoint';
$string['action:summarise_text:model'] = 'AI model';
$string['action:summarise_text:model_help'] = 'The Claude model used to summarise the provided text.';
$string['action:summarise_text:systeminstruction'] = 'System instruction';
$string['action:summarise_text:systeminstruction_help'] = 'This instruction is sent to Claude along with the user\'s prompt. Editing this instruction is not recommended unless absolutely required.';

// Provider settings.
$string['apikey'] = 'Anthropic API key';
$string['apikey_help'] = 'Get a key from your <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>.';
$string['extraparams'] = 'Extra parameters';
$string['extraparams_help'] = 'Extra parameters can be configured here. We support JSON format. For example:
<pre>
{
    "temperature": 0.5,
    "max_tokens": 2048,
    "top_p": 0.9
}
</pre>';
$string['invalidjson'] = 'Invalid JSON string';
$string['pluginname'] = 'Anthropic API provider';
$string['settings'] = 'Settings';
$string['settings_help'] = 'Adjust the settings below to customise how requests are sent to Anthropic Claude.';

// Privacy.
$string['privacy:metadata'] = 'The Anthropic API provider plugin does not store any personal data.';
$string['privacy:metadata:aiprovider_anthropic:externalpurpose'] = 'This information is sent to the Anthropic API in order for a response to be generated. Your Anthropic account settings may change how Anthropic stores and retains this data. No user data is explicitly sent to Anthropic or stored in Moodle LMS by this plugin.';
$string['privacy:metadata:aiprovider_anthropic:model'] = 'The Claude model used to generate the response.';
$string['privacy:metadata:aiprovider_anthropic:prompttext'] = 'The user entered text prompt used to generate the response.';

// Model settings.
$string['modelsettings'] = 'Model settings';
$string['settings_max_tokens'] = 'Max tokens';
$string['settings_max_tokens_help'] = 'The maximum number of tokens to generate in the response. Claude requires this parameter. Different models have different limits: Sonnet 4/4.5 and Haiku 4.5 support up to 64,000 tokens, while Opus 4/4.1 supports up to 32,000 tokens.';
$string['settings_temperature'] = 'Temperature';
$string['settings_temperature_help'] = 'Controls randomness. Lower values (e.g., 0.3) make output more focused and deterministic, higher values (e.g., 0.9) make it more creative. Range: 0.0 to 1.0. Default: 1.0.';
$string['settings_top_p'] = 'Top P';
$string['settings_top_p_help'] = 'An alternative to temperature for controlling randomness. Use nucleus sampling to select from the smallest set of tokens whose cumulative probability exceeds this value. Range: 0.0 to 1.0. Leave empty to use Claude\'s default.';
$string['settings_top_k'] = 'Top K';
$string['settings_top_k_help'] = 'Sample from the top K most likely tokens. Higher values (e.g., 100) provide more diversity, lower values (e.g., 10) make output more focused. Leave empty to use Claude\'s default.';

// Form validation error messages.
$string['error_invalid_model_name'] = 'Model name must start with "claude-". Example: claude-sonnet-4-20250514';
$string['error_max_tokens_positive'] = 'Max tokens must be a positive number.';
$string['error_max_tokens_exceeds_limit'] = 'Max tokens cannot exceed {$a->max} for this model.';
$string['error_temperature_range'] = 'Temperature must be between 0.0 and 1.0.';
$string['error_top_p_range'] = 'Top P must be between 0.0 and 1.0.';
$string['error_top_k_positive'] = 'Top K must be a positive number.';

// Rate limiting settings.
$string['ratelimit'] = 'Rate limiting';
$string['enableuserratelimit'] = 'Enable user rate limiting';
$string['enableuserratelimit_help'] = 'Enable rate limiting per user. This limits how many requests each user can make per hour.';
$string['userratelimit'] = 'User rate limit';
$string['userratelimit_help'] = 'Maximum number of AI requests a single user can make per hour. Default: 60.';
$string['enableglobalratelimit'] = 'Enable global rate limiting';
$string['enableglobalratelimit_help'] = 'Enable site-wide rate limiting. This limits the total number of requests across all users per hour.';
$string['globalratelimit'] = 'Global rate limit';
$string['globalratelimit_help'] = 'Maximum number of AI requests across all users per hour. Default: 1000.';
$string['error_user_rate_limit'] = 'You have reached the maximum number of AI requests you can make in an hour. Please try again later.';
$string['error_global_rate_limit'] = 'The AI service has reached the maximum number of site-wide requests per hour. Please try again later.';

// API error messages.
$string['error_api_bad_request'] = 'Invalid request to Claude API: {$a}';
$string['error_api_bad_request_max_tokens'] = 'Missing or invalid max_tokens parameter. Please configure max_tokens in the action settings.';
$string['error_api_bad_request_model'] = 'Invalid model name. Please check that the model name is correct and starts with "claude-".';
$string['error_api_unauthorized'] = 'Invalid Anthropic API key. Please check your API key in the provider settings.';
$string['error_api_forbidden'] = 'Access forbidden. Your API key may not have permission to access this resource.';
$string['error_api_not_found'] = 'Resource not found. The API endpoint or model may not exist.';
$string['error_api_rate_limit'] = 'Rate limit exceeded. Please wait a moment and try again.';
$string['error_api_server_error'] = 'Claude API server error. Please try again later.';
$string['error_api_overloaded'] = 'Claude servers are currently overloaded. Please try again in a few moments.';
$string['error_api_generic'] = 'Claude API error (HTTP {$a->statuscode}): {$a->message}';