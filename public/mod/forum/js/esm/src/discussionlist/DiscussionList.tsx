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
 * Root React component for the forum discussion list.
 *
 * Mounted from mod/forum/view.php via
 * html_writer::react_component('@moodle/lms/mod_forum/discussionlist/DiscussionList', $props).
 *
 * @module     mod_forum/discussionlist/DiscussionList
 * @copyright  2026 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export type DiscussionListProps = {
    /** The forum instance id. */
    forumid: number;
    /** The course module id. */
    cmid: number;
    /** The module context id. */
    contextid: number;
    /** The forum type: general, eachuser, qanda or news. */
    type: string;
    /** The initial sort order (vault constant 1 to 12). */
    sortorder: number;
    /** The initial page, starting at 0. */
    page: number;
    /** The initial group id; 0 means the activity group. */
    groupid: number;
};

/**
 * Render the discussion list.
 *
 * Empty for now: the data, cards and controls arrive in later tickets.
 *
 * @param props Component props.
 * @returns The list root element.
 */
export default function DiscussionList(props: DiscussionListProps) {
    return <div className='mod_forum-discussionlist' data-forumid={props.forumid} />;
}
