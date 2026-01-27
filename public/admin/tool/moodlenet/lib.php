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
 * This page lists public api for tool_moodlenet plugin.
 *
 * @package    tool_moodlenet
 * @copyright  2020 Peter Dias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU
 */

defined('MOODLE_INTERNAL') || die;

/**
 * The default endpoint to MoodleNet.
 *
 * @deprecated since Moodle 5.2 MDL-87351
 * @todo MDL-87562 This constant will be removed in Moodle 6.0
 */
define('MOODLENET_DEFAULT_ENDPOINT', "lms/moodle/search");

/**
 * Note: The following functions have been moved to lib/deprecatedlib.php:
 * - generate_mnet_endpoint()
 * - tool_moodlenet_custom_chooser_footer()
 *
 * @deprecated since Moodle 5.2 MDL-87351
 */
