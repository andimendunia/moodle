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

use core\http_client;
use core_ai\process_base;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Abstract processor base class for Anthropic API actions.
 *
 * @package    aiprovider_anthropic
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_processor extends process_base {
    /**
     * Get the endpoint URI.
     *
     * @return UriInterface
     */
    protected function get_endpoint(): UriInterface {
        return new Uri($this->provider->actionconfig[$this->action::class]['settings']['endpoint']);
    }

    /**
     * Get the name of the model to use.
     *
     * @return string
     */
    protected function get_model(): string {
        return $this->provider->actionconfig[$this->action::class]['settings']['model'];
    }

    /**
     * Get the model settings.
     *
     * Returns only the Claude-specific model parameters like max_tokens, temperature, top_p, top_k.
     * These settings are passed directly to the Claude API.
     *
     * @return array
     */
    protected function get_model_settings(): array {
        $settings = $this->provider->actionconfig[$this->action::class]['settings'];
        $modelsettings = [];

        // Extract Claude-specific model parameters.
        // Blank fields are not saved (handled by form's get_data method).
        $claudeparams = ['max_tokens', 'temperature', 'top_p', 'top_k'];
        foreach ($claudeparams as $param) {
            if (isset($settings[$param]) && $settings[$param] !== '' && $settings[$param] !== null) {
                $modelsettings[$param] = ($param === 'max_tokens' || $param === 'top_k')
                    ? (int)$settings[$param]
                    : (float)$settings[$param];
            }
        }

        // Add custom extra parameters from JSON if provided.
        if (!empty($settings['modelextraparams'])) {
            $params = json_decode($settings['modelextraparams'], true);
            if (is_array($params)) {
                foreach ($params as $key => $param) {
                    $modelsettings[$key] = $param;
                }
            }
        }

        return $modelsettings;
    }

    /**
     * Get the system instructions.
     *
     * @return string
     */
    protected function get_system_instruction(): string {
        return $this->action::get_system_instruction();
    }

    /**
     * Create the request object to send to the Anthropic API.
     *
     * This object contains all the required parameters for the request.
     *
     * @param string $userid The user id.
     * @return RequestInterface The request object to send to the Anthropic API.
     */
    abstract protected function create_request_object(
        string $userid,
    ): RequestInterface;

    /**
     * Handle a successful response from the external AI api.
     *
     * @param ResponseInterface $response The response object.
     * @return array The response.
     */
    abstract protected function handle_api_success(ResponseInterface $response): array;

    #[\Override]
    protected function query_ai_api(): array {
        $request = $this->create_request_object(
            userid: $this->provider->generate_userid($this->action->get_configuration('userid')),
        );
        $request = $this->provider->add_authentication_headers($request);

        $client = \core\di::get(http_client::class);
        try {
            // Call the external AI service.
            $response = $client->send($request, [
                'base_uri' => $this->get_endpoint(),
                RequestOptions::HTTP_ERRORS => false,
            ]);
        } catch (RequestException $e) {
            // Handle any exceptions.
            return \core_ai\error\factory::create($e->getCode(), $e->getMessage())->get_error_details();
        }

        // Double-check the response codes, in case of a non 200 that didn't throw an error.
        $status = $response->getStatusCode();
        if ($status === 200) {
            return $this->handle_api_success($response);
        } else {
            return $this->handle_api_error($response);
        }
    }

    /**
     * Handle an error from the external AI api.
     *
     * Maps Claude-specific error codes to user-friendly messages.
     *
     * @param ResponseInterface $response The response object.
     * @return array The error response.
     */
    protected function handle_api_error(ResponseInterface $response): array {
        $status = $response->getStatusCode();

        // Get the error message from the response.
        if ($status >= 500 && $status < 600) {
            // Server errors.
            $rawmessage = $response->getReasonPhrase();
            $errormessage = $this->get_user_friendly_error_message($status, $rawmessage);
        } else {
            // Client errors (400s).
            $bodyobj = json_decode($response->getBody()->getContents());
            $rawmessage = $bodyobj->error->message ?? $response->getReasonPhrase();
            $errormessage = $this->get_user_friendly_error_message($status, $rawmessage);
        }

        return \core_ai\error\factory::create($status, $errormessage)->get_error_details();
    }

    /**
     * Get user-friendly error message for Claude API errors.
     *
     * @param int $statuscode HTTP status code
     * @param string $rawmessage Raw error message from API
     * @return string User-friendly error message
     */
    protected function get_user_friendly_error_message(int $statuscode, string $rawmessage): string {
        switch ($statuscode) {
            case 400:
                // Bad request - often missing max_tokens or invalid model name.
                if (stripos($rawmessage, 'max_tokens') !== false) {
                    return get_string('error_api_bad_request_max_tokens', 'aiprovider_anthropic');
                } else if (stripos($rawmessage, 'model') !== false) {
                    return get_string('error_api_bad_request_model', 'aiprovider_anthropic');
                }
                return get_string('error_api_bad_request', 'aiprovider_anthropic', $rawmessage);

            case 401:
                // Invalid API key.
                return get_string('error_api_unauthorized', 'aiprovider_anthropic');

            case 403:
                // Permission denied.
                return get_string('error_api_forbidden', 'aiprovider_anthropic');

            case 404:
                // Resource not found.
                return get_string('error_api_not_found', 'aiprovider_anthropic');

            case 429:
                // Rate limit exceeded.
                return get_string('error_api_rate_limit', 'aiprovider_anthropic');

            case 500:
                // Internal server error.
                return get_string('error_api_server_error', 'aiprovider_anthropic');

            case 529:
                // Claude servers overloaded.
                return get_string('error_api_overloaded', 'aiprovider_anthropic');

            default:
                // Generic error.
                return get_string('error_api_generic', 'aiprovider_anthropic', [
                    'statuscode' => $statuscode,
                    'message' => $rawmessage,
                ]);
        }
    }
}