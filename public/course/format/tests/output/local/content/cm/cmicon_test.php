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

namespace core_courseformat\output\local\content\cm;

use core_courseformat\base as course_format;

/**
 * Tests for the cmicon class in the course format output.
 *
 * @package    core_courseformat
 * @category   test
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(cmicon::class)]
final class cmicon_test extends \advanced_testcase {
    /**
     * Export the cmicon data of an activity.
     *
     * @param string $modname the module name
     * @param array $record the activity record
     * @return array the exported data
     */
    private function export_icon(string $modname, array $record = []): array {
        global $PAGE;
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module($modname, ['course' => $course->id] + $record);
        $format = course_format::instance($course);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        $icon = new cmicon($format, $cm);
        return $icon->export_for_template($PAGE->get_renderer('core'));
    }

    /**
     * Test a module with a design system icon gets the React props.
     */
    public function test_export_for_template_mapped_module(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $data = $this->export_icon('assign');

        $props = json_decode($data['reactprops'], true);
        $this->assertEquals('assignment', $props['icon']);
        $this->assertEquals('', $props['alt']);
        $this->assertEquals('md', $props['size']);
        $this->assertEquals('default', $props['container']);
        $this->assertFalse($data['showlabel']);
    }

    /**
     * Test that the file activity icon depends on the file type.
     */
    public function test_export_for_template_resource(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $data = $this->export_icon('resource');

        // Without a file, the activity has the unknown file type icon.
        $props = json_decode($data['reactprops'], true);
        $this->assertStringStartsWith('file', $props['icon']);
    }

    /**
     * Test the container and the label while the user is editing.
     */
    public function test_export_for_template_editing(): void {
        global $PAGE, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $USER->editing = true;

        $data = $this->export_icon('forum');

        $props = json_decode($data['reactprops'], true);
        $this->assertEquals('forum', $props['icon']);
        $this->assertEquals('none', $props['container']);
        $this->assertTrue($data['showlabel']);
    }

    /**
     * Test the label is not displayed to users that cannot manage the activities.
     */
    public function test_export_for_template_student(): void {
        global $PAGE, $USER;
        $this->resetAfterTest();
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $USER->editing = true;

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $activity = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        $icon = new cmicon(course_format::instance($course), $cm);
        $data = $icon->export_for_template($PAGE->get_renderer('core'));

        $this->assertFalse($data['showlabel']);
    }

    /**
     * Test an activity without a design system icon gets no React props.
     */
    public function test_export_for_template_unmapped_module(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        // Stand in for a third party module, which has no icon in the design system.
        $icon = new class (course_format::instance($course), $cm) extends cmicon {
            /** @var array No design system icons, as for a third party module. */
            protected const DS_ICONS = [];
        };
        $data = $icon->export_for_template($PAGE->get_renderer('core'));

        $this->assertArrayNotHasKey('reactprops', $data);
        $this->assertNotEmpty($data['icon']);
    }

    /**
     * Test the design system icon of the file activity.
     *
     * @param string $path the path of the icon url
     * @param string $expected the expected design system icon
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('file_icon_provider')]
    public function test_get_design_system_icon_resource(string $path, string $expected): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('resource', ['course' => $course->id]);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        $icon = new cmicon(course_format::instance($course), $cm);
        $method = new \ReflectionMethod($icon, 'get_design_system_icon');
        $this->assertEquals($expected, $method->invoke($icon, new \moodle_url($path)));
    }

    /**
     * Data provider for test_get_design_system_icon_resource.
     *
     * @return array
     */
    public static function file_icon_provider(): array {
        return [
            'pdf' => ['/theme/image.php/boost/core/1/f/pdf', 'file-pdf'],
            'known type with a query string' => ['/theme/image.php/boost/core/1/f/video?filtericon=1', 'file-video'],
            'unknown type' => ['/theme/image.php/boost/core/1/f/notatype', 'file'],
            'no type' => ['/theme/image.php/boost/resource/1/monologo', 'file'],
        ];
    }
}
