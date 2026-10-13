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
 * Tests for core_course/ActivityIcon.
 *
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {render} from '@testing-library/react';
import ActivityIcon from '@moodle/lms/core_course/ActivityIcon';

// @moodlehq/design-system is ESM only, so Jest cannot resolve it from a CommonJS test bundle.
// Stand ActivityIcon in with markup exposing the props it receives.
jest.mock('@moodlehq/design-system', () => ({
    ActivityIcon: ({icon, alt, container, size}: {icon: string; alt: string; container: string; size: string}) => (
        <span data-testid="ds-icon" data-icon={icon} data-alt={alt} data-container={container} data-size={size} />
    ),
}), {virtual: true});

describe('core_course/ActivityIcon', () => {
    it('passes the icon through with the card defaults', () => {
        const {getByTestId} = render(<ActivityIcon icon="quiz" />);
        const icon = getByTestId('ds-icon');
        expect(icon.dataset.icon).toBe('quiz');
        expect(icon.dataset.alt).toBe('');
        expect(icon.dataset.container).toBe('default');
        expect(icon.dataset.size).toBe('md');
    });

    it('passes the container, size and alt props through', () => {
        const {getByTestId} = render(<ActivityIcon icon="forum" alt="Forum" container="none" size="lg" />);
        const icon = getByTestId('ds-icon');
        expect(icon.dataset.alt).toBe('Forum');
        expect(icon.dataset.container).toBe('none');
        expect(icon.dataset.size).toBe('lg');
    });
});
