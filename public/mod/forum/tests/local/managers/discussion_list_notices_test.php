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

namespace mod_forum\local\managers;

use mod_forum\local\container;

/**
 * Tests for the discussion list notices.
 *
 * @package    mod_forum
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(discussion_list_notices::class)]
final class discussion_list_notices_test extends \advanced_testcase {
    /**
     * Each rule shows the right notices, with the right level and kind.
     *
     * @param array $forumoptions Extra options for the forum generator
     * @param string $role The role the user has in the course
     * @param bool $member Whether the user is a member of a group in the course
     * @param string $viewing What is being viewed: 'all' (null group id), 'allzero' (group id 0, which is also all
     *      participants), 'omitted' (no group id passed), 'own' (the group the user is in) or 'other' (a group the user is not in)
     * @param array $expected The expected notices as [type, kind, string identifier, component, string parameter]
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('get_notices_provider')]
    public function test_get_notices(array $forumoptions, string $role, bool $member, string $viewing, array $expected): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course, $role);
        $forum = $generator->create_module('forum', ['course' => $course->id] + $forumoptions);

        $own = $generator->create_group(['courseid' => $course->id]);
        $other = $generator->create_group(['courseid' => $course->id]);
        if ($member) {
            $generator->create_group_member(['groupid' => $own->id, 'userid' => $user->id]);
        }
        $this->setUser($user);

        $forumentity = container::get_vault_factory()->get_forum_vault()->get_from_id($forum->id);
        $capabilitymanager = container::get_manager_factory()->get_capability_manager($forumentity);
        $helper = new discussion_list_notices($forumentity, $capabilitymanager);
        if ($viewing === 'omitted') {
            $notices = $helper->get_notices($user);
        } else {
            $groupid = ['all' => null, 'allzero' => 0, 'own' => $own->id, 'other' => $other->id][$viewing];
            $notices = $helper->get_notices($user, $groupid);
        }

        $expectednotices = array_map(function (array $notice): array {
            [$type, $kind, $identifier, $component, $parameter] = $notice;
            if (isset($parameter['blockperiod'])) {
                $parameter['blockperiod'] = get_string('secondstotime' . $parameter['blockperiod']);
            }
            return ['type' => $type, 'kind' => $kind, 'message' => get_string($identifier, $component, $parameter)];
        }, $expected);
        $this->assertSame($expectednotices, $notices);
    }

    /**
     * A visible groups member who can post to their groups, viewing all participants, gets the "not a member" notice.
     *
     * This pins the current behaviour: the wording says the user is in no group although they are a member.
     * The wording is a pre-existing bug, tracked in MDL-90711.
     */
    public function test_get_notices_member_who_can_post_to_my_groups(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course, 'student');
        $forum = $generator->create_module('forum', ['course' => $course->id, 'groupmode' => VISIBLEGROUPS]);
        $group = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('mod/forum:canposttomygroups', CAP_ALLOW, $studentroleid, \context_module::instance($forum->cmid));
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);

        $forumentity = container::get_vault_factory()->get_forum_vault()->get_from_id($forum->id);
        $capabilitymanager = container::get_manager_factory()->get_capability_manager($forumentity);
        $notices = (new discussion_list_notices($forumentity, $capabilitymanager))->get_notices($user, null);

        $this->assertSame([[
            'type' => 'warning',
            'kind' => 'group',
            'message' => get_string('cannotadddiscussiongroup', 'mod_forum'),
        ]], $notices);
    }

    /**
     * Data provider for test_get_notices.
     *
     * @return array[]
     */
    public static function get_notices_provider(): array {
        // The same values are the forum settings and the string parameters of the throttling notice.
        $throttlingoptions = ['blockafter' => 3, 'blockperiod' => DAYSECS];
        $cutoff = ['info', 'cutoff', 'cutoffdatereached', 'mod_forum', null];
        $throttled = ['info', 'throttling', 'thisforumisthrottled', 'mod_forum', $throttlingoptions];
        $qanda = ['info', 'qanda', 'qandanotify', 'mod_forum', null];
        $eachuser = ['info', 'eachuser', 'allowsdiscussions', 'mod_forum', null];
        $nogroup = ['warning', 'group', 'cannotadddiscussiongroup', 'mod_forum', null];
        $noallgroups = ['warning', 'group', 'cannotadddiscussionall', 'mod_forum', null];
        $nomembership = ['warning', 'group', 'cannotadddiscussion', 'mod_forum', null];
        $separate = ['groupmode' => SEPARATEGROUPS];
        $visible = ['groupmode' => VISIBLEGROUPS];

        return [
            'Plain forum' => [[], 'student', false, 'all', []],
            'Cut-off date reached' => [['cutoffdate' => time() - DAYSECS], 'student', false, 'all', [$cutoff]],
            'Cut-off date in the future' => [['cutoffdate' => time() + DAYSECS], 'student', false, 'all', []],
            'Cut-off date reached, teacher' => [['cutoffdate' => time() - DAYSECS], 'editingteacher', false, 'all', [$cutoff]],
            'Throttling' => [$throttlingoptions, 'student', false, 'all', [$throttled]],
            'Q&A, student' => [['type' => 'qanda'], 'student', false, 'all', [$qanda]],
            'Q&A, teacher can manage the forum' => [['type' => 'qanda'], 'editingteacher', false, 'all', []],
            'Each person posts one discussion' => [['type' => 'eachuser'], 'student', false, 'all', [$eachuser]],
            'Each person posts one discussion, teacher' => [['type' => 'eachuser'], 'editingteacher', false, 'all', [$eachuser]],
            'Separate groups, no group' => [$separate, 'student', false, 'all', [$nogroup]],
            'Separate groups, member viewing all participants' => [$separate, 'student', true, 'all', [$nogroup]],
            'Separate groups, viewing another group' => [$separate, 'student', true, 'other', [$nomembership]],
            'Separate groups, viewing own group' => [$separate, 'student', true, 'own', []],
            'Visible groups, no group' => [$visible, 'student', false, 'all', [$nogroup]],
            'Visible groups, member viewing all participants' => [$visible, 'student', true, 'all', [$noallgroups]],
            'Visible groups, viewing another group' => [$visible, 'student', true, 'other', [$nomembership]],
            'Visible groups, viewing own group' => [$visible, 'student', true, 'own', []],
            'Access all groups, no group' => [$separate, 'editingteacher', false, 'all', []],
            'Access all groups, viewing another group' => [$separate, 'editingteacher', true, 'other', []],
            'Group id 0 is all participants, no group' => [$separate, 'student', false, 'allzero', [$nogroup]],
            'Group id 0 is all participants, visible groups member' => [$visible, 'student', true, 'allzero', [$noallgroups]],
            'Group id 0 is all participants, access all groups' => [$separate, 'editingteacher', false, 'allzero', []],
            'Group id omitted is all participants' => [$separate, 'student', false, 'omitted', [$nogroup]],
            'Display order' => [
                ['type' => 'qanda', 'cutoffdate' => time() - DAYSECS] + $separate + $throttlingoptions,
                'student',
                false,
                'all',
                [$cutoff, $throttled, $nogroup, $qanda],
            ],
            'Display order, each person posts one discussion' => [
                ['type' => 'eachuser', 'cutoffdate' => time() - DAYSECS] + $separate + $throttlingoptions,
                'student',
                false,
                'all',
                [$cutoff, $throttled, $nogroup, $eachuser],
            ],
        ];
    }
}
