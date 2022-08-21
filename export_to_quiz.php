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
$oauth2code = optional_param('oauth2code', 0, PARAM_RAW);
$action = optional_param('action','', PARAM_TEXT);
$exporttype = optional_param('exporttype','qq', PARAM_TEXT);
$courseid = optional_param('courseid',0, PARAM_INT);
$selectedsets =  optional_param_array('selectedsets',array(), PARAM_ALPHANUMEXT); 

if( $courseid==0){
    $course = get_course($COURSE->id);
    $courseid = $course->id;
}else{
     $course = get_course($courseid);
}

$context = context_course::instance($courseid);
$PAGE->set_course($course);

$url = new moodle_url('/blocks/edmodo/export_to_quiz.php', array('courseid'=>$courseid, 'action'=>$action, 'exporttype'=>$exporttype));
$PAGE->set_url($url);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');

//get edmodo search form
$search_form = new block_edmodo_search_form(null,array('exporttype'=>$exporttype));
$search_data = $search_form->get_data();


//get our renderer
$renderer = $PAGE->get_renderer('block_edmodo');

  //Initialize Edmodo and deal with oauth etc
	//i  - send off to auth screen
	//ii - arrive back unauth, but with oauth2code
	//iii - complete auth by getting access token
	 $args = array(
		'api_scope' => 'read'
	);
	$qiz  = new edmodo_qq($args);
	
	$qmessage = false;
	if(!$qiz->is_authenticated() && $oauth2code){
                $result  = $qiz->get_access_token($oauth2code);
                if(!$result['success']){
                        $qmessage = $result['error'];
                }   
     }


//look for problems, and cancel out if there are
$allok=true;
if($qmessage){
        //print header	
        echo $renderer->header();
	echo $renderer->display_error($qmessage);
	$allok =false;
}elseif(!$qiz->is_authenticated()){
        //print header	
        echo $renderer->header();
	 echo $renderer->display_auth_link($qiz->fetch_auth_url());
	 $allok =false;
}

if(!$allok){
	echo $renderer->footer();
	return;
}

//get information on sets
$param_searchtext = '';
$param_searchtype = '';
$usedata=array();
if(!empty($search_data->searchtext)){
	$param_searchtext = $search_data->searchtext;
}
if(!empty($search_data->searchtype)){
	$param_searchtype = $search_data->searchtype;
}
$searchresult = $qiz->do_search($param_searchtext,$param_searchtype);
if($searchresult['success']){
	if(is_array($searchresult['data'])){
		$setdata = $searchresult['data'];	
	}else{
		$setdata = $searchresult['data']->sets;
	}
	$usedata = $qiz->fetch_set_as_array($setdata);
}

 //get our edmodo quiz helper class thingy
 $bqh = new block_edmodo_helper();


//get sections for display in section box
$sections = $bqh->fetch_section_list();

//deal with question export form
 $badmessage =false;
$qform = new block_edmodo_export_form(null,array('exporttype'=>$exporttype,'qsets'=>$usedata,'sections'=>$sections));

if($action=='qq_dataexport' && !$qform->is_cancelled()){
    $qform_data = $qform->get_data();
    
    //if we have not selected set, refuse to proceed
    if(count($selectedsets)==0){
        $badmessage = get_string('noselectedset', 'block_edmodo');
    }else{
        switch($exporttype){
            case 'qq':
            case 'qq_direct':
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

                break;
            case 'dd':
            case 'dd_direct':    
                $activitytypes = array(); 
                if($qform_data->flashcards){$activitytypes[]='flashcards';}
                if($qform_data->scatter){$activitytypes[]='scatter';}
                if($qform_data->speller){$activitytypes[]='speller';}
                if($qform_data->test){$activitytypes[]='test';}
                if($qform_data->learn){$activitytypes[]='learn';}
                if($qform_data->spacerace){$activitytypes[]='spacerace';}
                if(count($activitytypes)>0){
                    if($exporttype=='dd'){
                        $bqh->export_ddfile($selectedsets,$activitytypes);
                    }else{
					  echo $renderer->header();
						$section = $qform_data->section;
                        $bqh->export_dd_to_course($selectedsets,$activitytypes, $section);
						 
						 //prepare continue page
						 $params =  array('id' => $courseid);
						 $nexturl = new moodle_url('/course/view.php', $params);
						 $nextmessage = get_string('exportedddtocourse', 'block_edmodo');
						echo $renderer->display_continue_page($nexturl,$nextmessage);
                        
                        echo $renderer->footer();
                        exit;
                    }
                   //the selectedsets won't come through in form data, for validation reasons I think
                    //$bqh->export_ddfile($qform_data->selectedsets,$qform_data->activitytype);
                }else{
                    $badmessage = get_string('noactivitytype', 'block_edmodo');
                }
                break;

        }//end of switch
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
$renderer->echo_edmodo_search_form($search_form);
$renderer->echo_question_export_form($qform, $exporttype, $badmessage);
//$renderer->echo_ddrop_export_form($ddform);

//display preview iframe
echo $renderer->display_preview_iframe(BLOCK_QUIZLET_IFRAME_NAME);

//echo footer
echo $renderer->footer();