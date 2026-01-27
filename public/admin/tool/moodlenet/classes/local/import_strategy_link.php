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
 * Contains the import_strategy_link class.
 *
 * @package tool_moodlenet
 * @copyright 2020 Jake Dallimore <jrhdallimore@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_moodlenet\local;

/**
 * The import_strategy_link class.
 *
 * The import_strategy_link objects contains the setup steps needed to prepare a resource for import as a URL into Moodle.
 *
 * @copyright 2020 Jake Dallimore <jrhdallimore@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated since Moodle 5.2 MDL-87351
 * @todo MDL-87562 This class will be removed in Moodle 6.0
 */
#[\core\attribute\deprecated(
    since: '5.2',
    mdl: 'MDL-87351',
    reason: 'MoodleNet inbound sharing functionality has been deprecated.'
)]
class import_strategy_link implements import_strategy {

    /**
     * Get an array of import_handler_info objects representing modules supporting import of the resource.
     *
     * @param array $registrydata the fully populated registry.
     * @param remote_resource $resource the remote resource.
     * @return import_handler_info[] the array of import_handler_info objects.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_handlers(array $registrydata, remote_resource $resource): array {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        $handlers = [];
        foreach ($registrydata['types'] as $identifier => $items) {
            foreach ($items as $item) {
                if ($identifier === 'url') {
                    $handlers[] = new import_handler_info($item['module'], $item['message'], $this);
                }
            }
        }
        return $handlers;
    }

    /**
     * Import the remote resource according to the rules of this strategy.
     *
     * @param remote_resource $resource the resource to import.
     * @param \stdClass $user the user to import on behalf of.
     * @param \stdClass $course the course into which the remote_resource is being imported.
     * @param int $section the section into which the remote_resource is being imported.
     * @return \stdClass the module data.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function import(remote_resource $resource, \stdClass $user, \stdClass $course, int $section): \stdClass {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        $data = new \stdClass();
        $data->type = 'url';
        $data->course = $course;
        $data->content = $resource->get_url()->get_value();
        $data->displayname = $resource->get_name();
        return $data;
    }
}
