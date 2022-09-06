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
 * Forms for Edmodo Quiz Block
 *
 * @package    Edmodo Quiz
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

require_once($CFG->libdir . '/formslib.php');
define('BLOCK_EDMODO_NO','0');
define('BLOCK_EDMODO_MC_ABCSMALL','abc');
define('BLOCK_EDMODO_MC_ABCBIG','ABCD');
define('BLOCK_EDMODO_MC_123','123');
define('BLOCK_EDMODO_MC_NONE','none');
define('BLOCK_EDMODO_SA_CASE','1');
define('BLOCK_EDMODO_SA_NOCASE','0');



class edmodo_upload_form_qq extends moodleform {

    function tablify($elarray, $colcount, $id, $haveheader=true){
        $mform = & $this->_form;

        $starttable =  html_writer::start_tag('table',array('class'=>'block_edmodo_form_table'));
        //$startheadrow=html_writer::start_tag('th');
        //$endheadrow=html_writer::end_tag('th');
        $startrow=html_writer::start_tag('tr');
        $startcell = html_writer::start_tag('td',array('class'=>'block_edmodo_form_cell block_edmodo_' . $id .'_col_@@'));
        $startheadcell = html_writer::start_tag('th',array('class'=>'block_edmodo_form_cell block_edmodo_' . $id .'_col_@@'));
        $endcell=html_writer::end_tag('td');
        $endheadcell=html_writer::end_tag('th');
        $endrow=html_writer::end_tag('tr');
        $endtable = html_writer::end_tag('table');

        //start the table
        $tabledelements = array();
        $tabledelements[]=& $mform->createElement('static', 'table_start_' . $id, '', $starttable);


        //loop through rows
        for($row=0;$row<count($elarray);$row= $row+$colcount){
            //loop through cols
            for($col=0;$col<$colcount;$col++){
                //addrowstart
                if($col==0){
                    $tabledelements[]=& $mform->createElement('static', 'tablerow_start_' . $id . '_' . $row, '', $startrow);
                }
                //add a th cell if this is first row, otherwise a td
                if($row==0 && $haveheader){
                    $thestartcell = str_replace('@@', $col,$startheadcell);
                    $theendcell = $endheadcell;
                }else{
                    $thestartcell = str_replace('@@', $col,$startcell);
                    $theendcell = $endcell;
                }
                $tabledelements[]=& $mform->createElement('static', 'tablecell_start_' . $id . '_' . $row .'_'. $col, '', $thestartcell);
                $tabledelements[]=& $elarray[$row+$col];
                $tabledelements[]=& $mform->createElement('static', 'tablecell_end_' . $id . '_' . $row .'_'. $col, '', $theendcell);

                //add row end
                if($col==$colcount-1){
                    $tabledelements[]=& $mform->createElement('static', 'tablerow_end_' . $id . '_' . $row, '', $endrow);
                }
            }//end of col loop
        }//end of row loop

        //close out our table and return it
        $tabledelements[]=& $mform->createElement('static', 'table_end_' . $id, '', $endtable);
        return $tabledelements;
    }

    function definition() {
        global $CFG;

        $mform =& $this->_form;
        //courseid
        $mform->addElement('hidden', 'courseid');
        $mform->addElement('hidden', 'exporttype');
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('exporttype', PARAM_TEXT);

        //multichoice questions
        $attributes = array();
        $mc_array=array();
        $mc_array[] =& $mform->createElement('radio', 'multichoice_numbering', '', get_string('answernumberingnone', 'qtype_multichoice'), BLOCK_EDMODO_MC_NONE, $attributes);
        $mc_array[] =& $mform->createElement('radio', 'multichoice_numbering', '', get_string('answernumberingabc', 'qtype_multichoice'), BLOCK_EDMODO_MC_ABCSMALL, $attributes);
        $mc_array[] =& $mform->createElement('radio', 'multichoice_numbering', '', get_string('answernumberingABCD', 'qtype_multichoice'), BLOCK_EDMODO_MC_ABCBIG, $attributes);
        $mc_array[] =& $mform->createElement('radio', 'multichoice_numbering', '', get_string('answernumbering123', 'qtype_multichoice'), BLOCK_EDMODO_MC_123, $attributes);
        $mform->setDefault('multichoice_numbering',BLOCK_EDMODO_MC_NONE);
        $mc_arraytable = $this->tablify($mc_array,1, 'mc_table',false);

        //case sensitive questions
        $attributes = array();
        $sa_array=array();
        $sa_array[] =& $mform->createElement('radio', 'casesensitive', '', get_string('caseinsensitive', 'block_edmodo'), BLOCK_EDMODO_SA_NOCASE, $attributes);
        $sa_array[] =& $mform->createElement('radio', 'casesensitive', '', get_string('casesensitive', 'block_edmodo'), BLOCK_EDMODO_SA_CASE, $attributes);
        $mform->setDefault('casesensitive',BLOCK_EDMODO_SA_NOCASE);
        $sa_arraytable = $this->tablify($sa_array,1, 'sa_table',false);

        //fill or drag into blanks
        $foptions=[0=>get_string("typeintheblanks","block_edmodo"),1=>get_string("dragdropintheblanks","block_edmodo")];
        $mform->addElement('select', 'fillblanksstyle', get_string('fillblanksstyle', 'block_edmodo'),$foptions);
        $mform->setDefault('fillblanksstyle',0);


        $mform->addGroup($sa_arraytable, 'casesensitive_group',get_string('fib_casesensitive','block_edmodo'), array(' '), false);
        $mform->addGroup($mc_arraytable, 'multichoice_group',get_string('multichoice_numbering','block_edmodo'), array(' '), false);

        //ddmatch
        $mform->addElement('selectyesno', 'ddmatch', get_string('useddmatch', 'block_edmodo'));
        $mform->setDefault('ddmatch',0);
        $mform->addElement('static', 'ddmatchinstructions', '',
            get_string('useddmatch_desc', 'block_edmodo'));

        $options = array();
        $options['accepted_types'] = array('.zip');
        $mform->addElement('filepicker', 'edmodofile', get_string('file'), 'size="40"', $options);
        $mform->addRule('edmodofile', null, 'required');

        $this->add_action_buttons(false, get_string('uploadfile', 'block_edmodo'));

    }
    public function definition_after_data() {
        parent::definition_after_data();
        $courseid = optional_param('courseid', 0, PARAM_INT);

        $mform =& $this->_form;
        if($courseid > 0){
            $el_courseid =& $mform->getElement('courseid');
            $el_courseid->setValue($courseid);
        }

    }//end of function
}

/**
 * Form to create quiz from category
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edmodo_create_quiz_form extends moodleform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        $defaultcategory = $this->_customdata['defaultcategory'];
        $contexts = $this->_customdata['contexts'];
        $sections = $this->_customdata['sections'];
        $sectionslabel = $this->_customdata['sectionslabel'];


        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        if($sections) {
            $mform->addElement('select', 'section', $sectionslabel, $sections);
        }else{
            $mform->addElement('hidden', 'section');
            $mform->setType('section', PARAM_INT);
            $mform->setDefault('section', 0);
        }

        //questions per page
        $qoptions=array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10);
        $mform->addElement('select', 'questionsperpage', get_string('questionsperpage','block_edmodo'), $qoptions);
        $mform->setDefault('questionsperpage', 1);

        //question category
        $mform->addElement('questioncategory', 'category', get_string('use_category', 'block_edmodo'),array('contexts' => $contexts, 'top' => true));
        $mform->setDefault('category', $defaultcategory);

        // Submit button.
        $this->add_action_buttons(false, get_string('create_quizzes', 'block_edmodo'));

    }
}
