<?php // $Id: block_edmodo.php,v 1.0. 2010/11/27 01:01:01 rezeau Exp $

class block_edmodo extends block_base {
    function init() {
        $this->title = get_string('pluginname','block_edmodo');
       // $SESSION->block_edmodo->status = '';
    }
    
    function has_config() {
        return true;
    }

    function specialization() {
        global $CFG, $DB, $OUTPUT, $PAGE;
       // $this->config->title = get_string('pluginname','block_edmodo');
        $course = $this->page->course;
        $this->course = $course;
    }
    
    function instance_allow_multiple() {
    // Are you going to allow multiple instances of each block?
    // If yes, then it is assumed that the block WILL USE per-instance configuration
        return false;
    }
    
    function get_content() {
        global $USER, $CFG, $DB, $PAGE, $SESSION;
        $editing = $PAGE->user_is_editing();
        $blockconfig = get_config('block_edmodo');

        // set view block permission to course:mod/glossary:export to prevent students etc to view this block
        $course = $this->page->course; 
        $context = context_course::instance($course->id);
        if (!has_capability('block/edmodo:export', $context)) {
            return;
        }

        $this->content = new stdClass;
        $url = new moodle_url('/blocks/edmodo/export_to_quiz.php', array('courseid'=>$course->id,'exporttype'=>'qq'));
        $this->content->text = html_writer::link($url,get_string('qq_exportlink','block_edmodo'));
        if($blockconfig->enableqqdirect) {
            $url = new moodle_url('/blocks/edmodo/export_to_quiz.php', array('courseid' => $course->id, 'exporttype' => 'qq_direct'));
            $this->content->text .= html_writer::link($url, get_string('qq_direct_exportlink', 'block_edmodo'));
        }

        if($blockconfig->enablecreatequiz) {
            $url = new moodle_url('/blocks/edmodo/create_quiz.php', array('courseid' => $course->id));
            $this->content->text .= html_writer::link($url, get_string('qq_create_quiz', 'block_edmodo'));
        }

        $this->content->footer = '';
        return $this->content;
        
        
    }
}
?>