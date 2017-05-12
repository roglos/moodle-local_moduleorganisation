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
 * A scheduled task for scripted database integrations.
 *
 * @package    local_moduleorganisation
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moduleorganisation\task;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->libdir . '/accesslib.php');

/**
 * A scheduled task for scripted database integrations.
 *
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moduleorganisation extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('moduleorganisation', 'local_moduleorganisation');
    }

    /**
     * Run sync.
     */
    public function execute() {
        global $DB; // Ensures use of Moodle data-manipulation api.

        /* Get records from USR_DATA_COURSES feed.
         * --------------------------------------- */
        $datacourses = array();
        $sql = 'SELECT * FROM usr_data_courses';
        /***************************************
         * usr_data_courses                    *
         *     course_idnumber                 *
         *     course_fullname                 *
         *     course_shortname                *
         *     course_startdate                *
         *     category_idnumber               *
         ***************************************/

        $datacourses = $DB->get_records_sql($sql);
//        print_r($datacourses);

        foreach ($datacourses as $datacourse) {
            if ($DB->get_record('course', array('idnumber' => $datacourse->course_idnumber)) && $datacourse->course_idnumber !=='' ) {
                /* Get course record for each data course. */
                $thiscourse = $DB->get_record('course', array('idnumber' => $datacourse->course_idnumber));
//                print_r($thiscourse);
                /* Check any changes. */
//                echo '<p>'.$datacourse->category_idnumber.': ';
                $updated = 0;
                if ($thiscourse->fullname !== $datacourse->course_fullname) {
                    $updated++;
//                    echo $thiscourse->fullname.'-->'.$datacourse->course_fullname.': ';
                    $thiscourse->fullname = $datacourse->course_fullname;
                }
                if ($thiscourse->shortname !== $datacourse->course_shortname) {
                    $updated++;
//                    echo $thiscourse->shortname.'-->'.$datacourse->course_shortname.': ';
                    $thiscourse->shortname = $datacourse->course_shortname;
                }
                if ($thiscourse->startdate > $datacourse->course_startdate) { // Staff may bring date forward, not delay it.
                    $updated++;
//                    echo $thiscourse->startdate.'-->'.$datacourse->course_startdate.': ';
                    $thiscourse->startdate = $datacourse->course_startdate;
                }
                // Get category id for the relevant category idnumber - this is what is needed in the table.
                if ($DB->get_record('course_categories', array('idnumber' => $datacourse->category_idnumber)) ) {
                    $category = $DB->get_record('course_categories', array('idnumber' => $datacourse->category_idnumber));
                    if ($thiscourse->category !== $category->id) {
                        $updated++;
//                        echo $thiscourse->category.'-->'.$category->id.': ';
                        $thiscourse->category = $category->id;
                    }
                }
//                echo 'Updated count = '.$updated.'</p>';
                $DB->update_record('course', $thiscourse);
            }
        }

    }
}
