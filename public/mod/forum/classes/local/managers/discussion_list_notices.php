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

use core\output\notification;
use mod_forum\local\entities\forum as forum_entity;
use stdClass;

/**
 * Decides which notices the forum discussion list shows to a user.
 *
 * It only returns data, so the same rules can feed a rendered page and a web service.
 *
 * @package    mod_forum
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussion_list_notices {
    /** @var string The posting cut-off date has passed. */
    public const KIND_CUTOFF = 'cutoff';

    /** @var string Posting is limited to a number of posts in a time period. */
    public const KIND_THROTTLING = 'throttling';

    /** @var string The user can't start a discussion because of their group access. */
    public const KIND_GROUP = 'group';

    /** @var string Q&A forum: the user must post before seeing replies. */
    public const KIND_QANDA = 'qanda';

    /** @var string Each-user forum: each person can start one discussion. */
    public const KIND_EACHUSER = 'eachuser';

    /**
     * Constructor.
     *
     * @param forum_entity $forum The forum the discussion list belongs to
     * @param capability $capabilitymanager The capability manager for the forum
     */
    public function __construct(
        /** @var forum_entity The forum the discussion list belongs to */
        private readonly forum_entity $forum,
        /** @var capability The capability manager for the forum */
        private readonly capability $capabilitymanager,
    ) {
    }

    /**
     * Get the notices to show above the discussion list, in display order.
     *
     * Each notice is an array with:
     * - type: the level, 'info' or 'warning'. These are the notification::NOTIFY_* values and also MDS Alert types.
     *   The MDS Alert has four types (info, success, warning, danger), but Moodle's NOTIFY_ERROR is 'error', not 'danger'.
     *   Only info and warning are used here.
     * - kind: which notice it is, one of the KIND_* constants
     * - message: the plain text message
     *
     * @param stdClass $user The viewing user
     * @param int|null $groupid The group being viewed, null when viewing all participants (view.php passes
     *      groups_get_activity_group($cm, true) ?: null, so 0 is not expected here)
     * @return array[] The notices
     */
    public function get_notices(stdClass $user, ?int $groupid): array {
        $forum = $this->forum;
        $capabilitymanager = $this->capabilitymanager;
        $notices = [];

        if ($forum->is_cutoff_date_reached()) {
            $notices[] = $this->notice(
                notification::NOTIFY_INFO,
                self::KIND_CUTOFF,
                get_string('cutoffdatereached', 'forum'),
            );
        }

        if ($forum->has_blocking_enabled()) {
            $notices[] = $this->notice(
                notification::NOTIFY_INFO,
                self::KIND_THROTTLING,
                get_string('thisforumisthrottled', 'forum', [
                    'blockafter' => $forum->get_block_after(),
                    'blockperiod' => get_string('secondstotime' . $forum->get_block_period()),
                ]),
            );
        }

        if ($forum->is_in_group_mode() && !$capabilitymanager->can_access_all_groups($user)) {
            if ($groupid === null) {
                $isvisiblegroupsmode = $forum->get_effective_group_mode() == VISIBLEGROUPS;
                $isgroupmember = !empty(groups_get_user_groups($forum->get_course_id(), $user->id)[0]);

                if (
                    !$capabilitymanager->can_post_to_my_groups($user)
                    && $isvisiblegroupsmode
                    && $isgroupmember
                ) {
                    $string = 'cannotadddiscussionall';
                } else {
                    $string = 'cannotadddiscussiongroup';
                }
                $notices[] = $this->notice(notification::NOTIFY_WARNING, self::KIND_GROUP, get_string($string, 'mod_forum'));
            } else if (!$capabilitymanager->can_access_group($user, $groupid)) {
                $notices[] = $this->notice(
                    notification::NOTIFY_WARNING,
                    self::KIND_GROUP,
                    get_string('cannotadddiscussion', 'mod_forum'),
                );
            }
        }

        if ('qanda' === $forum->get_type() && !$capabilitymanager->can_manage_forum($user)) {
            $notices[] = $this->notice(notification::NOTIFY_INFO, self::KIND_QANDA, get_string('qandanotify', 'forum'));
        }

        if ('eachuser' === $forum->get_type()) {
            $notices[] = $this->notice(notification::NOTIFY_INFO, self::KIND_EACHUSER, get_string('allowsdiscussions', 'forum'));
        }

        return $notices;
    }

    /**
     * Build one notice.
     *
     * @param string $type The level, a notification::NOTIFY_* value
     * @param string $kind The notice, one of the KIND_* constants
     * @param string $message The plain text message
     * @return array
     */
    private function notice(string $type, string $kind, string $message): array {
        return ['type' => $type, 'kind' => $kind, 'message' => $message];
    }
}
