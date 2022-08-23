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
$exporttype = optional_param('exporttype','qq', PARAM_TEXT);

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


$url = new moodle_url('/blocks/edmodo/export_to_quiz.php', array('courseid'=>$courseid, 'action'=>$action,'exporttype'=>$exporttype));
$PAGE->set_url($url);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');

//get edmodo search form
$upload_form = new edmodo_upload_form_qq(null,array());
$formdata = $upload_form->get_data();
if ($formdata) {

        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        // Create a unique temporary directory, to process the zip file
        // contents.
        $zipdir = my_mktempdir($CFG->tempdir.'/', 'edmodoquizzes');
        $dstfile = $zipdir.'/quizzes.zip';

        if (!$upload_form->save_file('edmodofile', $dstfile, true)) {
            echo $OUTPUT->notification(get_string('cannotsavezip', 'block_edmodo'));
            @remove_dir($zipdir);
        } else {
            $fp = get_file_packer('application/zip');
            $unzipresult = $fp->extract_to_pathname($dstfile, $zipdir);
            if (!$unzipresult) {
                echo $OUTPUT->notification(get_string('cannotunzip', 'block_edmodo'));
                @remove_dir($zipdir);
            } else {
                // We don't need the zip file any longer, so delete it to make
                // it easier to process the rest of the files inside the directory.
                @unlink($dstfile);

                $results = array ('errors' => 0,'updated' => 0, 'quizzes'=>[]);

                $bqh->process_jsonfiles($zipdir,$results);
                $doredirect=false;
                if(count($results['quizzes'])>0) {
                    //export direct to question bank
                    if($exporttype=="qq_direct" &&  $blockconfig->enableqqdirect){
                        $category = question_get_default_category($context->id);
                        if(!$category){
                            $category = question_make_default_categories(array('course'=>$context));
                        }
                        $doredirect =  $bqh->export_qq_to_qbank($results['quizzes'], $formdata->casesensitive,$formdata->multichoice_numbering,$category,$url);
                    //export to file
                    }else {
                        $bqh->export_qqfile($results['quizzes'], $formdata->casesensitive, $formdata->multichoice_numbering);
                    }
                }

                // Finally remove the temporary directory with all the user images and print some stats.
                remove_dir($zipdir);
                echo $OUTPUT->notification(get_string('quizzes_processed', 'block_edmodo') . ": " . $results['updated'], 'notifysuccess');
                echo $OUTPUT->notification(get_string('errors', 'block_edmodo') . ": " . $results['errors'], ($results['errors'] ? 'notifyproblem' : 'notifysuccess'));
                echo '<hr />';

                if($doredirect){
                    $qbankurl =$CFG->wwwroot . '/question/edit.php?courseid=' . $courseid;
                    redirect($qbankurl);
                }
            }
        }

}


//get our renderer
$renderer = $PAGE->get_renderer('block_edmodo');

//echo footer
echo $renderer->header();

echo $renderer->show_intro();

//echo form
$renderer->echo_edmodo_upload_form($upload_form);

//echo footer
echo $renderer->footer();