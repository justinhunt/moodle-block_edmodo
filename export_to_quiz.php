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
require_once(dirname(__FILE__).'/edmodo.php');
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

//get our edmodo quiz helper class thingy
$bqh = new block_edmodo_helper();


$url = new moodle_url('/blocks/edmodo/export_to_quiz.php', array('courseid'=>$courseid, 'action'=>$action));
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

        if (!$mform->save_file('edmodofile', $dstfile, true)) {
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

                $bqh->load_jsonfiles($zipdir,$results);

                // Finally remove the temporary directory with all the user images and print some stats.
                remove_dir($zipdir);
                echo $OUTPUT->notification(get_string('quizzes_processed', 'block_edmodo') . ": " . $results['updated'], 'notifysuccess');
                echo $OUTPUT->notification(get_string('errors', 'block_edmodo') . ": " . $results['errors'], ($results['errors'] ? 'notifyproblem' : 'notifysuccess'));
                echo '<hr />';
            }
        }

}


//get our renderer
$renderer = $PAGE->get_renderer('block_edmodo');

//get information on sets
$param_searchtext = '';
$param_searchtype = '';
$usedata=array();
if(!empty($search_data->searchtext)){
	$param_searchtext = $search_data->searchtext;
}



//get sections for display in section box
$sections = $bqh->fetch_section_list();

//deal with question export form
 $badmessage =false;
$qform = new block_edmodo_export_form(null,array('exporttype'=>$exporttype,'qsets'=>$usedata,'sections'=>$sections, 'courseid'=>$COURSE->id));

if($action=='qq_dataexport' && !$qform->is_cancelled()){
    $qform_data = $qform->get_data();
    
    //if we have not selected set, refuse to proceed
    if(count($selectedsets)==0){
        $badmessage = get_string('noselectedset', 'block_edmodo');

    }elseif($qform_data){
       
                $questiontypes = array();
                if($qform_data->multichoice !== BLOCK_QUIZLET_NO){
                    $questiontypes[] = $qform_data->multichoice;
                }
                 if($qform_data->shortanswer !== BLOCK_QUIZLET_NO){
                    $questiontypes[] = $qform_data->shortanswer;
                }
                if($qform_data->matching !== BLOCK_QUIZLET_NO){
                    $questiontypes[] = $qform_data->matching;
                }
                if(count($questiontypes)>0){
                    //if we have questions, export to file
                    if($exporttype == 'qq'){
                        $bqh->export_qqfile($selectedsets,$questiontypes,$qform_data->answerside);
						exit;
                    
                    //or we export to questionbank    
                    }else{

                        echo $renderer->header();
                        //get default category for this course
                        $category = question_get_default_category($context->id);
                        if(!$category){
                        	$category = question_make_default_categories(array('course'=>$context));
                        }
                        $success = $bqh->export_qq_to_qbank($selectedsets,$questiontypes,$qform_data->answerside, $category, $url);
                    
                    	//prepare continue page
						 $params =  array('courseid' => $courseid);
						 $urlone = new moodle_url('/question/edit.php', $params);
						 $labelone = get_string('gotoquestionbank','block_edmodo');
						 $labeltwo = get_string('returntoedmodoblock','block_edmodo');
						 $nextmessage = get_string('exportedqqtoqbank', 'block_edmodo');
						 echo $renderer->display_continue_options($urlone,$labelone,$url,$labeltwo,$nextmessage);
						 echo $renderer->footer();
                        exit;
                    }
                    //the selectesets won't come through in form data, for validation reasons I think
                    // $bqh->export_qqfile($qform_data->selectedsets,$qform_data->multichoice,$qform_data->shortanswer)
                }else{
                    $badmessage = get_string('noquestiontype', 'block_edmodo');
                }

    }//end of if no selected set
    
    //if we have no error message, probably it went through ok
    //if so just exit
    if(!$badmessage){
        return;
    }
}
    
//print header	
echo $renderer->header();
$qform_data = new stdClass();
$qform_data->courseid = $courseid;
$qform_data->exporttype = $exporttype;
$qform_data->multichoice = BLOCK_QUIZLET_NO;
$qform_data->shortanswer = BLOCK_QUIZLET_NO;
$qform_data->matching = BLOCK_QUIZLET_NO;
$qform->set_data($qform_data);

//echo forms
$renderer->echo_edmodo_upload_form($upload_form);
//$renderer->echo_question_export_form($qform, $exporttype, $badmessage);

//display preview iframe
//echo $renderer->display_preview_iframe(BLOCK_QUIZLET_IFRAME_NAME);

//echo footer
echo $renderer->footer();