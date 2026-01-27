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
 * Contains the import_handler_info class.
 *
 * @package tool_moodlenet
 * @copyright 2020 Jake Dallimore <jrhdallimore@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_moodlenet\local;

/**
 * The import_handler_info class.
 *
 * An import_handler_info object represent an resource import handler for a particular module.
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
class import_handler_info {

    /** @var string $modulename the name of the module. */
    protected $modulename;

    /** @var string $description the description. */
    protected $description;

    /** @var import_strategy $importstrategy the strategy which will be used to import resources handled by this handler */
    protected $importstrategy;

    /**
     * The import_handler_info constructor.
     *
     * @param string $modulename the name of the module handling the file extension. E.g. 'label'.
     * @param string $description A description of how the module handles files of this extension type.
     * @param import_strategy $strategy the strategy which will be used to import the resource.
     * @throws \coding_exception
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function __construct(string $modulename, string $description, import_strategy $strategy) {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        if (empty($modulename)) {
            throw new \coding_exception("Module name cannot be empty.");
        }
        if (empty($description)) {
            throw new \coding_exception("Description cannot be empty.");
        }
        $this->modulename = $modulename;
        $this->description = $description;
        $this->importstrategy = $strategy;
    }

    /**
     * Get the name of the module.
     *
     * @return string the module name, e.g. 'label'.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_module_name(): string {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        return $this->modulename;
    }

    /**
     * Get a human readable, localised description of how the file is handled by the module.
     *
     * @return string the localised description.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_description(): string {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        return $this->description;
    }

    /**
     * Get the import strategy used by this handler.
     *
     * @return import_strategy the import strategy object.
     * @deprecated since Moodle 5.2 MDL-87351
     */
    public function get_strategy(): import_strategy {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        return $this->importstrategy;
    }
}
