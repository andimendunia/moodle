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
 * Test Explain text provider class for Anthropic provider methods.
 *
 * @package    aiprovider_anthropic
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_anthropic\provider
 * @covers     \aiprovider_anthropic\process_explain_text
 * @covers     \aiprovider_anthropic\abstract_processor
 */
final class process_explain_text_test extends \advanced_testcase {

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
            actionclass: \core_ai\aiactions\explain_text::class,
            actionconfig: [
                'systeminstruction' => get_string('action_explain_text_instruction', 'core_ai'),
            ],
        );
        $this->create_action();
    }

    /**
     * Create the action object.
     * @param int $userid The user id to use in the action.
     */
    private function create_action(int $userid = 1): void {
        $this->action = new \core_ai\aiactions\explain_text(
            contextid: 1,
            userid: $userid,
            prompttext: 'This is a complex text that needs explanation',
        );
    }

    /**
     * Test create_request_object creates proper Claude API request format.
     */
    public function test_create_request_object(): void {
        $processor = new process_explain_text($this->provider, $this->action);

        // We're working with a private method here, so we need to use reflection.
        $method = new \ReflectionMethod($processor, 'create_request_object');
        $request = $method->invoke($processor, 'test-user-id');

        $body = json_decode($request->getBody()->getContents());

        // Check Claude API format.
        $this->assertEquals('claude-sonnet-4-20250514', $body->model);
        $this->assertEquals('This is a complex text that needs explanation', $body->messages[0]->content);
        $this->assertEquals('user', $body->messages[0]->role);
        $this->assertEquals(4096, $body->max_tokens);

        // Check system instruction is at top level (Claude format).
        $this->assertObjectHasProperty('system', $body);
        $this->assertEquals(get_string('action_explain_text_instruction', 'core_ai'), $body->system);
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

        $processor = new process_explain_text($this->provider, $this->action);
        $result = $processor->process();

        $this->assertInstanceOf(\core_ai\aiactions\responses\response_base::class, $result);
        $this->assertTrue($result->get_success());
        $this->assertEquals('explain_text', $result->get_actionname());
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
            500,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/fixtures/text_request_error_500.json'),
        ));

        $processor = new process_explain_text($this->provider, $this->action);
        $result = $processor->process();

        $this->assertInstanceOf(\core_ai\aiactions\responses\response_base::class, $result);
        $this->assertFalse($result->get_success());
        $this->assertEquals('explain_text', $result->get_actionname());
        $this->assertEquals(500, $result->get_errorcode());
        $this->assertStringContainsString('server error', $result->get_errormessage());
    }
}
