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
 * Tests for mod_forum/discussionlist/DiscussionList.
 *
 * @copyright  2026 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {render} from '@testing-library/react';
import DiscussionList from '@moodle/lms/mod_forum/discussionlist/DiscussionList';

describe('mod_forum/discussionlist/DiscussionList', () => {
    it('renders its root element', () => {
        const {container} = render(
            <DiscussionList forumid={1} cmid={2} contextid={3} type='general' sortorder={1} page={0} groupid={0} />
        );
        expect(container.querySelector('.mod_forum-discussionlist')).not.toBeNull();
    });
});
