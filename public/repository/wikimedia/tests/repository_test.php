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
 * Unit tests for repository_wikimedia class.
 *
 * @package    repository_wikimedia
 * @copyright  2026 Andi Permana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_wikimedia;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/wikimedia/lib.php');

/**
 * Unit tests for Wikimedia repository
 *
 * @package    repository_wikimedia
 * @copyright  2026 Andi Permana
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \repository_wikimedia
 */
final class repository_test extends \advanced_testcase {
    /** @var \repository_wikimedia|null Repository instance */
    private $repo = null;

    /**
     * Setup test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $user = get_admin();
        $this->setUser($user);

        // Create repository type if it doesn't exist.
        try {
            $this->getDataGenerator()->create_repository_type('wikimedia');
        } catch (\repository_exception $e) {
            // Repository type might already exist, ignore.
            if ($e->getMessage() !== 'This repository already exists') {
                throw $e;
            }
        }

        // Create repository instance.
        $record = $this->getDataGenerator()->create_repository('wikimedia');

        $this->repo = \repository::get_repository_by_id($record->id, \core\context\system::instance());
    }

    /**
     * Test that get_file method exists and has correct signature.
     *
     * This test verifies the method is callable and accepts the expected parameters.
     * Testing the HTTP 429 rate limiting would require mocking curl which creates
     * challenges since curl is instantiated within the method. Rate limit handling
     * should be verified through integration or manual testing.
     */
    public function test_get_file_method_exists(): void {
        $this->assertTrue(method_exists($this->repo, 'get_file'));

        $reflection = new \ReflectionMethod($this->repo, 'get_file');
        $params = $reflection->getParameters();

        // Verify method signature: get_file($url, $filename = '').
        $this->assertCount(2, $params);
        $this->assertEquals('url', $params[0]->getName());
        $this->assertEquals('filename', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
    }

    /**
     * Test repository instance creation.
     */
    public function test_repository_instance(): void {
        $this->assertInstanceOf(\repository_wikimedia::class, $this->repo);
        $this->assertEquals('wikimedia', $this->repo->get_typename());
    }

    /**
     * Test that the rate limited error string is properly defined.
     */
    public function test_ratelimited_string_exists(): void {
        $string = get_string('ratelimited', 'repository_wikimedia');
        $this->assertNotEmpty($string);
        $this->assertStringContainsString('rate limit', strtolower($string));
    }

    /**
     * Test get_file with invalid URL throws exception.
     *
     * This test verifies that the error handling works when download fails.
     */
    public function test_get_file_invalid_url(): void {
        $this->expectException(\moodle_exception::class);

        // Use an invalid URL that will cause curl to fail.
        $this->repo->get_file('http://invalid.test.local.invalid/nonexistent.jpg');
    }
}
