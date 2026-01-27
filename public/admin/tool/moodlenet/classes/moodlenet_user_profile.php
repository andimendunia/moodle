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
 * Moodle net user profile class.
 *
 * @package    tool_moodlenet
 * @copyright  2020 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_moodlenet;

/**
 * A class to represent the moodlenet profile.
 *
 * @package    tool_moodlenet
 * @copyright  2020 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated since Moodle 5.2 MDL-87351
 * @todo MDL-87562 This class will be removed in Moodle 6.0
 */
#[\core\attribute\deprecated(
    since: '5.2',
    mdl: 'MDL-87351',
    reason: 'MoodleNet inbound sharing functionality has been deprecated.'
)]
class moodlenet_user_profile {

    /** @var string $profile The full profile name. */
    protected $profile;

    /** @var int $userid The user ID that this profile belongs to. */
    protected $userid;

    /** @var string $username The username from $userprofile */
    protected $username;

    /** @var string $domain The domain from $domain */
    protected $domain;

    /**
     * Constructor method.
     *
     * @param string $userprofile The moodle net user profile string.
     * @param int $userid The user ID that this profile belongs to.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function __construct(string $userprofile, int $userid) {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        $this->profile = $userprofile;
        $this->userid = $userid;

        $explodedprofile = explode('@', $this->profile);
        if (count($explodedprofile) === 2) {
            // It'll either be an email or WebFinger entry.
            $this->username = $explodedprofile[0];
            $this->domain = $explodedprofile[1];
        } else if (count($explodedprofile) === 3) {
            // We may have a profile link as MoodleNet gives to the user.
            $this->username = $explodedprofile[1];
            $this->domain = $explodedprofile[2];
        } else {
            throw new \moodle_exception('invalidmoodlenetprofile', 'tool_moodlenet');
        }
    }

    /**
     * Get the full moodle net profile.
     *
     * @return string The moodle net profile.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_profile_name(): string {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        return $this->profile;
    }

    /**
     * Get the user ID that this profile belongs to.
     *
     * @return int The user ID.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_userid(): int {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        return $this->userid;
    }

    /**
     * Get the username for this profile.
     *
     * @return string The username.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_username(): string {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        return $this->username;
    }

    /**
     * Get the domain for this profile.
     *
     * @return string The domain.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_domain(): string {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        return $this->domain;
    }
}
