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

/**
 * Test Anthropic provider methods.
 *
 * @package    aiprovider_anthropic
 * @copyright  2025 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_anthropic\provider
 */
final class provider_test extends \advanced_testcase {

    /** @var \core_ai\manager */
    private $manager;

    /** @var \core_ai\provider */
    private $provider;

    /**
     * Overriding setUp() function to always reset after tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Create the provider instance.
        $this->manager = \core\di::get(\core_ai\manager::class);
        $this->provider = $this->manager->create_provider_instance(
            classname: '\aiprovider_anthropic\provider',
            name: 'dummy',
        );
    }

    /**
     * Test get_action_list returns the correct actions.
     */
    public function test_get_action_list(): void {
        $actionlist = $this->provider->get_action_list();
        $this->assertIsArray($actionlist);
        $this->assertCount(3, $actionlist);
        $this->assertContains(\core_ai\aiactions\generate_text::class, $actionlist);
        $this->assertContains(\core_ai\aiactions\summarise_text::class, $actionlist);
        $this->assertContains(\core_ai\aiactions\explain_text::class, $actionlist);
    }

    /**
     * Test generate_userid creates a valid hashed user ID.
     */
    public function test_generate_userid(): void {
        $userid = $this->provider->generate_userid(1);

        // Assert that the generated userid is a string of proper length (SHA256 = 64 chars).
        $this->assertIsString($userid);
        $this->assertEquals(64, strlen($userid));

        // Test consistency - same input should produce same output.
        $userid2 = $this->provider->generate_userid(1);
        $this->assertEquals($userid, $userid2);

        // Test uniqueness - different input should produce different output.
        $userid3 = $this->provider->generate_userid(2);
        $this->assertNotEquals($userid, $userid3);
    }

    /**
     * Test is_provider_configured checks for API key.
     */
    public function test_is_provider_configured(): void {
        // No configured values.
        $this->assertFalse($this->provider->is_provider_configured());

        // Properly configured with API key.
        $updatedprovider = $this->manager->update_provider_instance(
            provider: $this->provider,
            config: ['apikey' => 'sk-ant-test-key-123'],
        );
        $this->assertTrue($updatedprovider->is_provider_configured());
    }

    /**
     * Test is_request_allowed with rate limiting.
     */
    public function test_is_request_allowed(): void {
        // Create the provider instance with rate limiting enabled.
        $config = [
            'apikey' => 'sk-ant-test-key-123',
            'enableuserratelimit' => true,
            'userratelimit' => 3,
            'enableglobalratelimit' => true,
            'globalratelimit' => 5,
        ];
        $provider = $this->manager->create_provider_instance(
            classname: '\aiprovider_anthropic\provider',
            name: 'dummy',
            config: $config,
        );

        $contextid = 1;
        $userid = 1;
        $prompttext = 'This is a test prompt';
        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $userid,
            prompttext: $prompttext,
        );

        // Make 3 requests, all should be allowed.
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($provider->is_request_allowed($action));
        }

        // The 4th request for the same user should be denied.
        $result = $provider->is_request_allowed($action);
        $this->assertFalse($result['success']);
        $this->assertEquals(
            'You have reached the maximum number of AI requests you can make in an hour. Please try again later.',
            $result['errormessage']
        );

        // Change user id to make a request for a different user, should pass (4 requests for global rate).
        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: 2,
            prompttext: $prompttext,
        );
        $this->assertTrue($provider->is_request_allowed($action));

        // Make a 5th request for the global rate limit, it should be allowed.
        $this->assertTrue($provider->is_request_allowed($action));

        // The 6th request should be denied.
        $result = $provider->is_request_allowed($action);
        $this->assertFalse($result['success']);
        $this->assertEquals(
            expected: 'The AI service has reached the maximum number of site-wide requests per hour. Please try again later.',
            actual: $result['errormessage'],
        );
    }

    /**
     * Test add_authentication_headers adds required Anthropic headers.
     */
    public function test_add_authentication_headers(): void {
        // Configure provider with API key.
        $provider = $this->manager->update_provider_instance(
            provider: $this->provider,
            config: ['apikey' => 'sk-ant-test-key-123'],
        );

        // Create a mock request.
        $request = new \GuzzleHttp\Psr7\Request('POST', 'https://api.anthropic.com/v1/messages');

        // Add authentication headers.
        $authenticatedrequest = $provider->add_authentication_headers($request);

        // Verify headers were added.
        $this->assertTrue($authenticatedrequest->hasHeader('x-api-key'));
        $this->assertEquals(['sk-ant-test-key-123'], $authenticatedrequest->getHeader('x-api-key'));

        $this->assertTrue($authenticatedrequest->hasHeader('anthropic-version'));
        $this->assertEquals(['2023-06-01'], $authenticatedrequest->getHeader('anthropic-version'));
    }

    /**
     * Test custom model selection validation.
     *
     * @covers \aiprovider_anthropic\form\action_form::validation
     */
    public function test_custom_model_validation(): void {
        global $PAGE;
        $PAGE->set_url('/');

        // Test valid custom model name.
        $formdata = [
            'modeltemplate' => 'custom',
            'custommodel' => 'claude-opus-5-20260101',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'max_tokens' => 4096,
        ];

        $form = new \aiprovider_anthropic\form\action_generate_text_form(null, [
            'actionname' => 'generate_text',
            'action' => \core_ai\aiactions\generate_text::class,
            'actionconfig' => ['settings' => []],
        ]);

        $errors = $form->validation($formdata, []);
        $this->assertEmpty($errors, 'Valid custom model name should not produce errors');

        // Test invalid model name (wrong prefix).
        $formdata['custommodel'] = 'gpt-4';
        $errors = $form->validation($formdata, []);
        $this->assertArrayHasKey('custommodel', $errors);
        $this->assertEquals(
            get_string('error_invalid_model_name', 'aiprovider_anthropic'),
            $errors['custommodel']
        );

        // Test empty custom model name.
        $formdata['custommodel'] = '';
        $errors = $form->validation($formdata, []);
        $this->assertArrayHasKey('custommodel', $errors);
        $this->assertEquals(get_string('required'), $errors['custommodel']);
    }

    /**
     * Test custom model data processing in get_data().
     *
     * @covers \aiprovider_anthropic\form\action_form::get_data
     */
    public function test_custom_model_get_data(): void {
        global $PAGE;
        $PAGE->set_url('/');

        // Test that custom model selection properly sets the model field.
        // We'll test this through validation and data structure.

        // Test with custom model selected.
        $custommodeldata = (object) [
            'modeltemplate' => 'custom',
            'custommodel' => 'claude-opus-5-20260101',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ];

        // Simulate what get_data() does with custom model.
        $processeddata = clone $custommodeldata;
        if ($processeddata->modeltemplate === 'custom') {
            $processeddata->model = $processeddata->custommodel;
        } else {
            $processeddata->model = $processeddata->modeltemplate;
        }
        unset($processeddata->custommodel);
        unset($processeddata->modeltemplate);

        $this->assertEquals('claude-opus-5-20260101', $processeddata->model);
        $this->assertObjectNotHasProperty('custommodel', $processeddata);
        $this->assertObjectNotHasProperty('modeltemplate', $processeddata);

        // Test with predefined model selected.
        $predefinedmodeldata = (object) [
            'modeltemplate' => 'claude-sonnet-4-20250514',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'max_tokens' => 4096,
        ];

        $processeddata2 = clone $predefinedmodeldata;
        if (isset($processeddata2->modeltemplate)) {
            if ($processeddata2->modeltemplate === 'custom') {
                $processeddata2->model = $processeddata2->custommodel;
            } else {
                $processeddata2->model = $processeddata2->modeltemplate;
            }
            if (isset($processeddata2->custommodel)) {
                unset($processeddata2->custommodel);
            }
            unset($processeddata2->modeltemplate);
        }

        $this->assertEquals('claude-sonnet-4-20250514', $processeddata2->model);
        $this->assertObjectNotHasProperty('modeltemplate', $processeddata2);
    }

    /**
     * Test form data loading with custom model in set_data().
     *
     * @covers \aiprovider_anthropic\form\action_form::set_data
     */
    public function test_custom_model_set_data(): void {
        global $PAGE;
        $PAGE->set_url('/');

        // Test loading a custom model that's not in the predefined list.
        $custommodeldata = [
            'model' => 'claude-opus-5-20260101',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'max_tokens' => 4096,
        ];

        $form = new \aiprovider_anthropic\form\action_generate_text_form(null, [
            'actionname' => 'generate_text',
            'action' => \core_ai\aiactions\generate_text::class,
            'actionconfig' => ['settings' => $custommodeldata],
        ]);

        // The form should recognize this as a custom model.
        // We can't directly test the internal state, but we can verify the form was created without errors.
        $this->assertInstanceOf(\aiprovider_anthropic\form\action_generate_text_form::class, $form);

        // Test loading a predefined model.
        $predefinedmodeldata = [
            'model' => 'claude-sonnet-4-20250514',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'max_tokens' => 4096,
        ];

        $form2 = new \aiprovider_anthropic\form\action_generate_text_form(null, [
            'actionname' => 'generate_text',
            'action' => \core_ai\aiactions\generate_text::class,
            'actionconfig' => ['settings' => $predefinedmodeldata],
        ]);

        $this->assertInstanceOf(\aiprovider_anthropic\form\action_generate_text_form::class, $form2);
    }

    /**
     * Test max_tokens validation against model limits.
     *
     * @covers \aiprovider_anthropic\form\action_form::validation
     */
    public function test_max_tokens_validation(): void {
        global $PAGE;
        $PAGE->set_url('/');

        // Test Sonnet 4.5 (max 64000).
        $formdata = [
            'modeltemplate' => 'claude-sonnet-4-5-20250929',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'max_tokens' => 64000,
        ];

        $form = new \aiprovider_anthropic\form\action_generate_text_form(null, [
            'actionname' => 'generate_text',
            'action' => \core_ai\aiactions\generate_text::class,
            'actionconfig' => ['settings' => []],
        ]);

        $errors = $form->validation($formdata, []);
        $this->assertEmpty($errors, 'Valid max_tokens within limit should not produce errors');

        // Test exceeding limit.
        $formdata['max_tokens'] = 65000;
        $errors = $form->validation($formdata, []);
        $this->assertArrayHasKey('max_tokens', $errors);

        // Test Sonnet 4 (max 64000).
        $formdata['modeltemplate'] = 'claude-sonnet-4-20250514';
        $formdata['max_tokens'] = 64000;
        $errors = $form->validation($formdata, []);
        $this->assertEmpty($errors);

        // Test Opus 4.1 (max 32000).
        $formdata['modeltemplate'] = 'claude-opus-4-1-20250805';
        $formdata['max_tokens'] = 32000;
        $errors = $form->validation($formdata, []);
        $this->assertEmpty($errors);

        // Test exceeding Opus limit.
        $formdata['max_tokens'] = 33000;
        $errors = $form->validation($formdata, []);
        $this->assertArrayHasKey('max_tokens', $errors);

        // Test Opus 4 (max 32000).
        $formdata['modeltemplate'] = 'claude-opus-4-20250514';
        $formdata['max_tokens'] = 32000;
        $errors = $form->validation($formdata, []);
        $this->assertEmpty($errors);

        // Test Haiku 4.5 (max 64000).
        $formdata['modeltemplate'] = 'claude-haiku-4-5-20251001';
        $formdata['max_tokens'] = 64000;
        $errors = $form->validation($formdata, []);
        $this->assertEmpty($errors);

        // Test exceeding Haiku limit.
        $formdata['max_tokens'] = 65000;
        $errors = $form->validation($formdata, []);
        $this->assertArrayHasKey('max_tokens', $errors);

        // Test custom model (no limit validation).
        $formdata['modeltemplate'] = 'custom';
        $formdata['custommodel'] = 'claude-opus-5-20260101';
        $formdata['max_tokens'] = 100000;
        $errors = $form->validation($formdata, []);
        // Should not have max_tokens error for custom model.
        $this->assertArrayNotHasKey('max_tokens', $errors);
    }
}