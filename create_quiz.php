<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Export to Quiz for Edmodo Quiz Block
 *
 * @package    block_edmodo
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */
global  $USER, $COURSE;	

require_once("../../config.php");
require_once(dirname(__FILE__).'/forms.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir . '/questionlib.php');

require_login();
if (isguestuser()) {
    die();
}

//Set up page
//$context = context_user::instance($USER->id);
//require_capability('moodle/user:viewalldetails', $context);
//$PAGE->set_context($context);

//get any params we might need
$action = optional_param('action','', PARAM_TEXT);
$courseid = optional_param('courseid',0, PARAM_INT);
$categoryid = optional_param('categoryid',0, PARAM_INT);

if( $courseid==0){
    $course = get_course($COURSE->id);
    $courseid = $course->id;
}else{
     $course = get_course($courseid);
}

$context = context_course::instance($courseid);
$PAGE->set_course($course);
$blockconfig = get_config('block_edmodo');

//get our edmodo quiz helper class thingy
$bqh = new block_edmodo_helper();


$url = new moodle_url('/blocks/edmodo/create_quiz.php', array('courseid'=>$courseid, 'action'=>$action,'$categoryid'=>$categoryid));
$PAGE->set_url($url);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');

if(!$blockconfig->enablecreatequiz){
    echo $renderer->header();
    echo 'Creating quizzes is not enabled on the Edmodo plugin';
    echo $renderer->footer();
    die;
}

//get edmodo search form

/*
require_once($CFG->dirroot . '/question/editlib.php');
list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
    question_edit_setup('export', '/question/export.php');
$defaultcategory =$pagevars['cat'];
$contexts = $contexts->having_one_edit_tab_cap('export');
*/

$defaultcategory = $DB->get_field('course_categories','id',array('name'=>'edmodoquestions'));
$contexts =[$context];

//get our renderer
$renderer = $PAGE->get_renderer('block_edmodo');
//echo footer
echo $renderer->header();

$create_quiz_form = new edmodo_create_quiz_form(null,array('defaultcategory'=>$defaultcategory,'contexts'=>$contexts));
$formdata = $create_quiz_form->get_data();
if ($formdata) {

    $results = $bqh->create_quiz_from_qbank_category($formdata->category,$courseid,1);

    echo $OUTPUT->notification(get_string('quizzes_processed', 'block_edmodo') . ": " . $results['created'], 'notifysuccess');
   // echo $OUTPUT->notification(get_string('errors', 'block_edmodo') . ": " . $results['errors'], ($results['errors'] ? 'notifyproblem' : 'notifysuccess'));

}



echo $renderer->show_create_quiz_intro();

//echo form
$create_quiz_form->set_data(['courseid'=>$courseid]);
$renderer->echo_create_quiz_form($create_quiz_form);

//echo footer
echo $renderer->footer();