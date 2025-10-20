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

use aiprovider_anthropic\test\testcase_helper_trait;
use core_ai\aiactions\base;
use core_ai\provider;
use GuzzleHttp\Psr7\Response;

/**
 * Test Generate text provider class for Anthropic provider methods.
 *
 * @package    aiprovider_anthropic
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_anthropic\provider
 * @covers     \aiprovider_anthropic\process_generate_text
 * @covers     \aiprovider_anthropic\abstract_processor
 */
final class process_generate_text_test extends \advanced_testcase {

    use testcase_helper_trait;

    /** @var string A successful response in JSON format. */
    protected string $responsebodyjson;

    /** @var \core_ai\manager */
    private $manager;

    /** @var provider The provider that will process the action. */
    protected provider $provider;

    /** @var base The action to process. */
    protected base $action;

    /**
     * Set up the test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Load a response body from a file.
        $fixturepath = __DIR__ . '/fixtures/text_request_success.json';
        $this->responsebodyjson = file_get_contents($fixturepath);
        $this->manager = \core\di::get(\core_ai\manager::class);
        $this->provider = $this->create_provider(
            actionclass: \core_ai\aiactions\generate_text::class,
            actionconfig: [
                'systeminstruction' => get_string('action_generate_text_instruction', 'core_ai'),
            ],
        );
        $this->create_action();
    }

    /**
     * Create the action object.
     * @param int $userid The user id to use in the action.
     */
    private function create_action(int $userid = 1): void {
        $this->action = new \core_ai\aiactions\generate_text(
            contextid: 1,
            userid: $userid,
            prompttext: 'This is a test prompt',
        );
    }

    /**
     * Test create_request_object creates proper Claude API request format.
     */
    public function test_create_request_object(): void {
        $processor = new process_generate_text($this->provider, $this->action);

        // We're working with a private method here, so we need to use reflection.
        $method = new \ReflectionMethod($processor, 'create_request_object');
        $request = $method->invoke($processor, 'test-user-id');

        $body = json_decode($request->getBody()->getContents());

        // Check Claude API format.
        $this->assertEquals('claude-sonnet-4-20250514', $body->model);
        $this->assertEquals('This is a test prompt', $body->messages[0]->content);
        $this->assertEquals('user', $body->messages[0]->role);
        $this->assertEquals(4096, $body->max_tokens);

        // Check system instruction is at top level (Claude format).
        $this->assertObjectHasProperty('system', $body);
        $this->assertEquals(get_string('action_generate_text_instruction', 'core_ai'), $body->system);

        // Check headers.
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));
    }

    /**
     * Test create_request_object with extra model settings.
     */
    public function test_create_request_object_with_model_settings(): void {
        $this->provider = $this->create_provider(
            actionclass: \core_ai\aiactions\generate_text::class,
            actionconfig: [
                'systeminstruction' => get_string('action_generate_text_instruction', 'core_ai'),
                'temperature' => 0.5,
                'top_p' => 0.9,
                'top_k' => 40,
                'max_tokens' => 2048,
            ],
        );
        $processor = new process_generate_text($this->provider, $this->action);

        // We're working with a private method here, so we need to use reflection.
        $method = new \ReflectionMethod($processor, 'create_request_object');
        $request = $method->invoke($processor, 'test-user-id');

        $body = json_decode($request->getBody()->getContents());

        $this->assertEquals('claude-sonnet-4-20250514', $body->model);
        $this->assertEquals(0.5, $body->temperature);
        $this->assertEquals(0.9, $body->top_p);
        $this->assertEquals(40, $body->top_k);
        $this->assertEquals(2048, $body->max_tokens);
    }

    /**
     * Test create_request_object with custom model via modelextraparams.
     */
    public function test_create_request_object_with_modelextraparams(): void {
        $this->provider = $this->create_provider(
            actionclass: \core_ai\aiactions\generate_text::class,
            actionconfig: [
                'model' => 'claude-opus-4-20241022',
                'systeminstruction' => get_string('action_generate_text_instruction', 'core_ai'),
                'modelextraparams' => '{"temperature": 0.7, "max_tokens": 8192}',
            ],
        );
        $processor = new process_generate_text($this->provider, $this->action);

        $method = new \ReflectionMethod($processor, 'create_request_object');
        $request = $method->invoke($processor, 'test-user-id');

        $body = json_decode($request->getBody()->getContents());

        $this->assertEquals('claude-opus-4-20241022', $body->model);
        $this->assertEquals(0.7, $body->temperature);
        $this->assertEquals(8192, $body->max_tokens);
    }

    /**
     * Test the API error response handler method.
     */
    public function test_handle_api_error(): void {
        $responses = [
            400 => new Response(
                400,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/fixtures/text_request_error_400.json'),
            ),
            401 => new Response(
                401,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/fixtures/text_request_error_401.json'),
            ),
            429 => new Response(
                429,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/fixtures/text_request_error_429.json'),
            ),
            500 => new Response(
                500,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/fixtures/text_request_error_500.json'),
            ),
            529 => new Response(
                529,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/fixtures/text_request_error_529.json'),
            ),
        ];

        $processor = new process_generate_text($this->provider, $this->action);
        $method = new \ReflectionMethod($processor, 'handle_api_error');

        foreach ($responses as $status => $response) {
            $result = $method->invoke($processor, $response);
            $this->assertFalse($result['success']);
            $this->assertEquals($status, $result['errorcode']);

            // Verify user-friendly error messages.
            switch ($status) {
                case 400:
                    $this->assertStringContainsString('max_tokens', $result['errormessage']);
                    break;
                case 401:
                    $this->assertStringContainsString('API key', $result['errormessage']);
                    break;
                case 429:
                    $this->assertStringContainsString('Rate limit', $result['errormessage']);
                    break;
                case 500:
                    $this->assertStringContainsString('server error', $result['errormessage']);
                    break;
                case 529:
                    $this->assertStringContainsString('overloaded', $result['errormessage']);
                    break;
            }
        }
    }

    /**
     * Test the API success response handler method.
     */
    public function test_handle_api_success(): void {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->responsebodyjson,
        );

        // We're testing a private method, so we need to setup reflector magic.
        $processor = new process_generate_text($this->provider, $this->action);
        $method = new \ReflectionMethod($processor, 'handle_api_success');

        $result = $method->invoke($processor, $response);

        $this->assertTrue($result['success']);
        $this->assertEquals('msg_01XFDUDYJgAACzvnptvVoYEL', $result['id']);
        $this->assertStringContainsString('test response from Claude', $result['generatedcontent']);
        $this->assertEquals('end_turn', $result['finishreason']);
        $this->assertEquals(15, $result['prompttokens']);
        $this->assertEquals(25, $result['completiontokens']);
        $this->assertEquals('claude-sonnet-4-20250514', $result['model']);
    }

    /**
     * Test query_ai_api for a successful call.
     */
    public function test_query_ai_api_success(): void {
        // Mock the http client to return a successful response.
        $mock = new \GuzzleHttp\Handler\MockHandler();
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);
        $client = new \core\http_client(['handler' => $handlerstack]);
        \core\di::set(\core\http_client::class, $client);

        // The response from Claude API.
        $mock->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->responsebodyjson,
        ));

        $processor = new process_generate_text($this->provider, $this->action);
        $method = new \ReflectionMethod($processor, 'query_ai_api');
        $result = $method->invoke($processor);

        $this->assertTrue($result['success']);
        $this->assertEquals('msg_01XFDUDYJgAACzvnptvVoYEL', $result['id']);
        $this->assertStringContainsString('test response from Claude', $result['generatedcontent']);
        $this->assertEquals('end_turn', $result['finishreason']);
        $this->assertEquals(15, $result['prompttokens']);
        $this->assertEquals(25, $result['completiontokens']);
        $this->assertEquals('claude-sonnet-4-20250514', $result['model']);
    }

    /**
     * Test prepare_response success.
     */
    public function test_prepare_response_success(): void {
        $processor = new process_generate_text($this->provider, $this->action);

        // We're working with a private method here, so we need to use reflection.
        $method = new \ReflectionMethod($processor, 'prepare_response');

        $response = [
            'success' => true,
            'id' => 'msg_01XFDUDYJgAACzvnptvVoYEL',
            'generatedcontent' => 'This is a test response from Claude',
            'finishreason' => 'end_turn',
            'prompttokens' => 15,
            'completiontokens' => 25,
            'model' => 'claude-sonnet-4-20250514',
        ];

        $result = $method->invoke($processor, $response);

        $this->assertInstanceOf(\core_ai\aiactions\responses\response_base::class, $result);
        $this->assertTrue($result->get_success());
        $this->assertEquals('generate_text', $result->get_actionname());
        $this->assertEquals($response['success'], $result->get_success());
        $this->assertEquals($response['generatedcontent'], $result->get_response_data()['generatedcontent']);
        $this->assertEquals($response['model'], $result->get_response_data()['model']);
    }

    /**
     * Test prepare_response error.
     */
    public function test_prepare_response_error(): void {
        $processor = new process_generate_text($this->provider, $this->action);

        // We're working with a private method here, so we need to use reflection.
        $method = new \ReflectionMethod($processor, 'prepare_response');

        $response = [
            'success' => false,
            'errorcode' => 401,
            'error' => 'Authentication error',
            'errormessage' => 'Invalid API key',
        ];

        $result = $method->invoke($processor, $response);

        $this->assertInstanceOf(\core_ai\aiactions\responses\response_base::class, $result);
        $this->assertFalse($result->get_success());
        $this->assertEquals('generate_text', $result->get_actionname());
        $this->assertEquals($response['errorcode'], $result->get_errorcode());
        $this->assertEquals($response['error'], $result->get_error());
        $this->assertEquals($response['errormessage'], $result->get_errormessage());
    }

    /**
     * Test process method.
     */
    public function test_process(): void {
        // Log in user.
        $this->setUser($this->getDataGenerator()->create_user());

        // Mock the http client to return a successful response.
        $mock = new \GuzzleHttp\Handler\MockHandler();
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);
        $client = new \core\http_client(['handler' => $handlerstack]);
        \core\di::set(\core\http_client::class, $client);

        // The response from Claude API.
        $mock->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->responsebodyjson,
        ));

        $processor = new process_generate_text($this->provider, $this->action);
        $result = $processor->process();

        $this->assertInstanceOf(\core_ai\aiactions\responses\response_base::class, $result);
        $this->assertTrue($result->get_success());
        $this->assertEquals('generate_text', $result->get_actionname());
    }

    /**
     * Test process method with error.
     */
    public function test_process_error(): void {
        // Log in user.
        $this->setUser($this->getDataGenerator()->create_user());

        // Mock the http client to return an error response.
        $mock = new \GuzzleHttp\Handler\MockHandler();
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);
        $client = new \core\http_client(['handler' => $handlerstack]);
        \core\di::set(\core\http_client::class, $client);

        // The error response from Claude API.
        $mock->append(new Response(
            401,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/fixtures/text_request_error_401.json'),
        ));

        $processor = new process_generate_text($this->provider, $this->action);
        $result = $processor->process();

        $this->assertInstanceOf(\core_ai\aiactions\responses\response_base::class, $result);
        $this->assertFalse($result->get_success());
        $this->assertEquals('generate_text', $result->get_actionname());
        $this->assertEquals(401, $result->get_errorcode());
        $this->assertStringContainsString('API key', $result->get_errormessage());
    }
}