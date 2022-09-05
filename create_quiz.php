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
$confirm = optional_param('confirm',0, PARAM_INT);
$categoryid = optional_param('categoryid',0, PARAM_INT);
$sectionnumber = optional_param('sectionnumber',0, PARAM_INT);

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


$url = new moodle_url('/blocks/edmodo/create_quiz.php', array('courseid'=>$courseid));
$PAGE->set_url($url);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
//get our renderer
$renderer = $PAGE->get_renderer('block_edmodo');

if(!$blockconfig->enablecreatequiz){
    echo $renderer->header();
    echo 'Creating quizzes is not enabled on the Edmodo plugin';
    echo $renderer->footer();
    die;
}

//get course create form custom data fields (default category, context and sections)
$defaultcategory = $DB->get_field('course_categories','id',array('name'=>'edmodoquestions'));
$contexts =[$context];

$sectionslabel = '';
$usesections = course_format_uses_sections($course->format);
$modinfo = get_fast_modinfo($course);
if ($usesections) {
    $sectionslabel = get_string('sectionname', 'format_'.$course->format);
    $rawsections = $modinfo->get_section_info_all();
    $sections=[];
    foreach($rawsections as $rawsection){
        $sections[$rawsection->section] = get_section_name($course,$rawsection);
    }
}else{
    $sections = false;
    $sectionslabel = '';
}


//if this is a confirmation of a form submission ..
if ($confirm and confirm_sesskey()) {
    // a single quiz
    //$results = $bqh->create_quiz_from_qbank_category($categoryid,$courseid,1);

    //the quiz and all the sub quizzes too
    $results = $bqh->create_quizzes_from_qbank_category($categoryid,$courseid,$sectionnumber);
    redirect($url,get_string('cats_processed', 'block_edmodo',$results));
}


//echo footer
echo $renderer->header();

$create_quiz_form = new edmodo_create_quiz_form(null,array('defaultcategory'=>$defaultcategory,'contexts'=>$contexts,'sections'=>$sections,'sectionslabel'=>$sectionslabel));
$formdata = $create_quiz_form->get_data();
if ($formdata) {

    $strheading = get_string('createquizzesconfirm', 'block_edmodo');
    $PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);
    $PAGE->set_heading($SITE->fullname);
    echo $renderer->heading($strheading);
    $yesurl = new moodle_url('/blocks/edmodo/create_quiz.php', array('courseid'=>$courseid,'categoryid'=>$formdata->category,'sectionnumber'=>$formdata->section, 'confirm' => 1, 'sesskey' => sesskey()));
    $nourl = $url;
    $totalquizzes = $bqh->count_subcategories($formdata->category);
    $message = get_string('createquizzesconfirmmessage', 'block_edmodo',$totalquizzes);
    echo $renderer->confirm($message, $yesurl, $nourl);
    echo $renderer->footer();
    die;
}



echo $renderer->show_create_quiz_intro();

//echo form
$create_quiz_form->set_data(['courseid'=>$courseid]);
$renderer->echo_create_quiz_form($create_quiz_form);

//echo footer
echo $renderer->footer();