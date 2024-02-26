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
 * The block_edmodo export complete event.
 *
 * @package    block_edmodo
 * @copyright  2022 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_edmodo\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The block_edmodo attempt started event class.
 *@property-read array $other {
 *      Extra information about event.
 *
 *      - int questionscount: how many qs exported
 * }
 *
 * @package    block_edmodo
 * @since      Moodle 3.11
 * @copyright  2022 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_complete extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if(isset($this->other)) {
            return "An export was completed with: " . s($this->other['questioncount']) . " questions by userid:" . $this->userid;
        } else {
            return "An export was completed by userid:" . $this->userid;
        }
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventexportcomplete', 'block_edmodo');
    }


    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
    }

    public static function get_objectid_mapping() {
        return array();
    }

    public static function get_other_mapping() {
        $othermapped = array();
        return $othermapped;
    }
}
