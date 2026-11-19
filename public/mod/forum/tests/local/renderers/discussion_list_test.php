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

namespace mod_forum\local\renderers;

use mod_forum\local\container;

/**
 * Tests for the discussion list renderer.
 *
 * @package    mod_forum
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(discussion_list::class)]
final class discussion_list_test extends \advanced_testcase {
    /**
     * Create a forum and export the notices the discussion list renderer shows to a user.
     *
     * @param array $forumoptions Extra options for the forum generator
     * @param string $role The role the user has in the course
     * @param callable|null $setup Called with (course, user, forum) before the notices are exported, returns the group id
     * @return array The exported notifications
     */
    private function export_notices(array $forumoptions = [], string $role = 'student', ?callable $setup = null): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course, $role);
        $forum = $generator->create_module('forum', ['course' => $course->id] + $forumoptions);
        $groupid = $setup ? $setup($course, $user, $forum) : null;
        $this->setUser($user);

        $forumentity = container::get_vault_factory()->get_forum_vault()->get_from_id($forum->id);
        $renderer = container::get_renderer_factory()->get_discussion_list_renderer($forumentity);

        $method = new \ReflectionMethod($renderer, 'get_notifications');
        return $method->invoke($renderer, $user, $groupid);
    }

    /**
     * Assert that exactly one notice was exported and that it matches the expectations.
     *
     * @param array $notices The exported notifications
     * @param string $message The expected message
     * @param string $level The expected level, 'info' or 'warning'
     * @param string $extraclasses The expected extra classes
     */
    private function assert_single_notice(array $notices, string $message, string $level, string $extraclasses = ''): void {
        $this->assertCount(1, $notices);
        $notice = reset($notices);
        $this->assertSame($message, $notice['message']);
        $this->assertSame($level === 'info', $notice['isinfo']);
        $this->assertSame($level === 'warning', $notice['iswarning']);
        // The renderer calls set_show_closebutton() without an argument, which hides the close button.
        $this->assertFalse($notice['closebutton']);
        $this->assertSame($extraclasses, $notice['extraclasses']);
    }

    /**
     * Create two groups, enrolled student is a member of the first one only.
     *
     * @param \stdClass $course
     * @param \stdClass $user
     * @return array The ids of the group the user belongs to and of the other group
     */
    private function create_groups(\stdClass $course, \stdClass $user): array {
        $generator = $this->getDataGenerator();
        $own = $generator->create_group(['courseid' => $course->id]);
        $other = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $own->id, 'userid' => $user->id]);
        return [$own->id, $other->id];
    }

    /**
     * A plain forum shows no notice.
     */
    public function test_no_notices(): void {
        $this->resetAfterTest();
        $this->assertSame([], $this->export_notices());
    }

    /**
     * A forum past its cut-off date says so.
     */
    public function test_cutoff_notice(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['cutoffdate' => time() - DAYSECS]);
        $this->assert_single_notice($notices, get_string('cutoffdatereached', 'forum'), 'info');
    }

    /**
     * A forum with posting throttling shows the limit and the period.
     */
    public function test_throttling_notice(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['blockafter' => 3, 'blockperiod' => DAYSECS]);
        $message = get_string('thisforumisthrottled', 'forum', [
            'blockafter' => 3,
            'blockperiod' => get_string('secondstotime' . DAYSECS),
        ]);
        $this->assert_single_notice($notices, $message, 'info');
    }

    /**
     * A Q&A forum tells students to post before they see replies, with extra spacing.
     */
    public function test_qanda_notice_for_student(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['type' => 'qanda']);
        $this->assert_single_notice($notices, get_string('qandanotify', 'forum'), 'info', 'mt-3');
    }

    /**
     * Users who can manage the forum don't get the Q&A notice.
     */
    public function test_qanda_notice_hidden_from_teacher(): void {
        $this->resetAfterTest();
        $this->assertSame([], $this->export_notices(['type' => 'qanda'], 'editingteacher'));
    }

    /**
     * An each-user forum says each person can start one discussion.
     */
    public function test_eachuser_notice(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['type' => 'eachuser']);
        $this->assert_single_notice($notices, get_string('allowsdiscussions', 'forum'), 'info');
    }

    /**
     * In separate groups mode, a user with no group can't start a discussion.
     */
    public function test_group_notice_user_without_group(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['groupmode' => SEPARATEGROUPS]);
        $this->assert_single_notice($notices, get_string('cannotadddiscussiongroup', 'mod_forum'), 'warning');
    }

    /**
     * In visible groups mode, a member who can't post to all groups is told so when viewing all participants.
     */
    public function test_group_notice_visible_groups_member_viewing_all(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['groupmode' => VISIBLEGROUPS], 'student', function ($course, $user) {
            $this->create_groups($course, $user);
            return null;
        });
        $this->assert_single_notice($notices, get_string('cannotadddiscussionall', 'mod_forum'), 'warning');
    }

    /**
     * In visible groups mode, a user with no group gets the "not a member of any group" notice.
     */
    public function test_group_notice_visible_groups_non_member_viewing_all(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['groupmode' => VISIBLEGROUPS]);
        $this->assert_single_notice($notices, get_string('cannotadddiscussiongroup', 'mod_forum'), 'warning');
    }

    /**
     * In separate groups mode, viewing a group the user doesn't belong to says group membership is required.
     */
    public function test_group_notice_other_group_selected(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['groupmode' => SEPARATEGROUPS], 'student', function ($course, $user) {
            [, $otherid] = $this->create_groups($course, $user);
            return $otherid;
        });
        $this->assert_single_notice($notices, get_string('cannotadddiscussion', 'mod_forum'), 'warning');
    }

    /**
     * No group notice when the user belongs to the selected group.
     */
    public function test_no_group_notice_for_own_group(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['groupmode' => SEPARATEGROUPS], 'student', function ($course, $user) {
            [$ownid] = $this->create_groups($course, $user);
            return $ownid;
        });
        $this->assertSame([], $notices);
    }

    /**
     * Users who can access all groups never get a group notice.
     */
    public function test_no_group_notice_with_access_all_groups(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices(['groupmode' => SEPARATEGROUPS], 'editingteacher', function ($course, $user) {
            [, $otherid] = $this->create_groups($course, $user);
            return $otherid;
        });
        $this->assertSame([], $notices);
    }

    /**
     * Notices appear in a fixed order: cut-off, throttling, group, Q&A.
     */
    public function test_notice_order(): void {
        $this->resetAfterTest();
        $notices = $this->export_notices([
            'type' => 'qanda',
            'groupmode' => SEPARATEGROUPS,
            'cutoffdate' => time() - DAYSECS,
            'blockafter' => 3,
            'blockperiod' => DAYSECS,
        ]);

        $this->assertSame([
            get_string('cutoffdatereached', 'forum'),
            get_string('thisforumisthrottled', 'forum', [
                'blockafter' => 3,
                'blockperiod' => get_string('secondstotime' . DAYSECS),
            ]),
            get_string('cannotadddiscussiongroup', 'mod_forum'),
            get_string('qandanotify', 'forum'),
        ], array_column($notices, 'message'));
    }
}
