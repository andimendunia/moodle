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

namespace mod_forum;

use mod_forum\local\container;
use mod_forum\local\managers\discussion_list_notices;

/**
 * Tests for the discussion list notices.
 *
 * @package    mod_forum
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(discussion_list_notices::class)]
final class managers_discussion_list_notices_test extends \advanced_testcase {
    /**
     * Each rule shows the right notices, with the right level and kind.
     *
     * @param array $forumoptions Extra options for the forum generator
     * @param string $role The role the user has in the course
     * @param string $groupscenario 'none' (no groups, all participants), 'viewingall' (member of a group, all participants),
     *      'own' (member of the group being viewed) or 'other' (viewing a group the user is not in)
     * @param array $expected The expected notices as [type, kind, string identifier, component, string parameter]
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('get_notices_provider')]
    public function test_get_notices(array $forumoptions, string $role, string $groupscenario, array $expected): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course, $role);
        $forum = $generator->create_module('forum', ['course' => $course->id] + $forumoptions);

        $groupid = null;
        if ($groupscenario !== 'none') {
            $own = $generator->create_group(['courseid' => $course->id]);
            $other = $generator->create_group(['courseid' => $course->id]);
            $generator->create_group_member(['groupid' => $own->id, 'userid' => $user->id]);
            $groupid = ['viewingall' => null, 'own' => $own->id, 'other' => $other->id][$groupscenario];
        }
        $this->setUser($user);

        $forumentity = container::get_vault_factory()->get_forum_vault()->get_from_id($forum->id);
        $capabilitymanager = container::get_manager_factory()->get_capability_manager($forumentity);
        $notices = (new discussion_list_notices($forumentity, $capabilitymanager))->get_notices($user, $groupid);

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
        $cutoff = ['info', 'cutoff', 'cutoffdatereached', 'forum', null];
        $throttled = ['info', 'throttling', 'thisforumisthrottled', 'forum', $throttlingoptions];
        $qanda = ['info', 'qanda', 'qandanotify', 'forum', null];
        $eachuser = ['info', 'eachuser', 'allowsdiscussions', 'forum', null];
        $nogroup = ['warning', 'group', 'cannotadddiscussiongroup', 'mod_forum', null];
        $noallgroups = ['warning', 'group', 'cannotadddiscussionall', 'mod_forum', null];
        $nomembership = ['warning', 'group', 'cannotadddiscussion', 'mod_forum', null];

        return [
            'Plain forum' => [[], 'student', 'none', []],
            'Cut-off date reached' => [['cutoffdate' => time() - DAYSECS], 'student', 'none', [$cutoff]],
            'Throttling' => [$throttlingoptions, 'student', 'none', [$throttled]],
            'Q&A, student' => [['type' => 'qanda'], 'student', 'none', [$qanda]],
            'Q&A, teacher can manage the forum' => [['type' => 'qanda'], 'editingteacher', 'none', []],
            'Each person posts one discussion' => [['type' => 'eachuser'], 'student', 'none', [$eachuser]],
            'Separate groups, no group' => [['groupmode' => SEPARATEGROUPS], 'student', 'none', [$nogroup]],
            'Visible groups, no group' => [['groupmode' => VISIBLEGROUPS], 'student', 'none', [$nogroup]],
            'Visible groups, member viewing all participants' => [
                ['groupmode' => VISIBLEGROUPS], 'student', 'viewingall', [$noallgroups],
            ],
            'Separate groups, viewing another group' => [
                ['groupmode' => SEPARATEGROUPS], 'student', 'other', [$nomembership],
            ],
            'Separate groups, viewing own group' => [['groupmode' => SEPARATEGROUPS], 'student', 'own', []],
            'Access all groups, no group' => [['groupmode' => SEPARATEGROUPS], 'editingteacher', 'none', []],
            'Access all groups, viewing another group' => [['groupmode' => SEPARATEGROUPS], 'editingteacher', 'other', []],
            'Display order' => [
                ['type' => 'qanda', 'groupmode' => SEPARATEGROUPS, 'cutoffdate' => time() - DAYSECS] + $throttlingoptions,
                'student',
                'none',
                [$cutoff, $throttled, $nogroup, $qanda],
            ],
        ];
    }
}
