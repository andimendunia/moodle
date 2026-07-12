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

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class process text generation for the Anthropic Claude provider.
 *
 * @package    aiprovider_anthropic
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_generate_text extends abstract_processor {
    /**
     * Create the request object for the Anthropic Messages API.
     *
     * API reference: https://docs.anthropic.com/en/api/messages
     *
     * @param string $userid User identifier.
     * @return RequestInterface
     */
    protected function create_request_object(string $userid): RequestInterface {
        $requestobj = new \stdClass();
        $requestobj->model = $this->get_model();

        // Max tokens is required by the Anthropic API; default to 8096 if not configured.
        $modelsettings = $this->get_model_settings();
        $requestobj->max_tokens = $modelsettings['max_tokens'] ?? 8096;

        if (isset($modelsettings['temperature'])) {
            $requestobj->temperature = $modelsettings['temperature'];
        }

        // System instruction (top-level field in Anthropic API).
        $systeminstruction = $this->get_system_instruction();
        if (!empty($systeminstruction)) {
            $requestobj->system = $systeminstruction;
        }

        // User message.
        $requestobj->messages = [
            [
                'role' => 'user',
                'content' => $this->action->get_configuration('prompttext'),
            ],
        ];

        return new Request(
            method: 'POST',
            uri: '',
            body: json_encode($requestobj),
            headers: [
                'Content-Type' => 'application/json',
            ],
        );
    }

    /**
     * Handle a successful response from the Anthropic API.
     *
     * @param ResponseInterface $response The response object.
     * @return array The response.
     */
    protected function handle_api_success(ResponseInterface $response): array {
        $bodystring = (string) $response->getBody();
        $responsebody = json_decode($bodystring);

        $contentblock = $responsebody->content[0] ?? null;
        $usage = $responsebody->usage;

        return [
            'success' => true,
            'id' => $responsebody->id,
            'generatedcontent' => $contentblock->text ?? '',
            'finishreason' => $responsebody->stop_reason ?? 'unknown',
            'prompttokens' => $usage->input_tokens,
            'completiontokens' => $usage->output_tokens,
            'model' => $responsebody->model ?? $this->get_model(),
        ];
    }
}
