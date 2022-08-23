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
 * Block Edmodo Quiz renderer.
 * @package   block_edmodo
 * @copyright 2014 Justin Hunt (poodllsupport@gmail.com)
 * @author    Justin Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_edmodo_renderer extends plugin_renderer_base {
	
	function display_error($qmessage) {
		 echo $qmessage;
	}//end of func

	
	function display_continue_options($urlone,$labelone,$urltwo,$labeltwo,$message){
		$nextmessage = $this->output->heading($message, 3, 'main');
		return $nextmessage . $this->output->single_button($urlone, $labelone) . $this->output->single_button($urltwo, $labeltwo) ;
	}


    function echo_edmodo_upload_form($form){
		echo $this->output->box_start('generalbox');
		$form->display();
		echo $this->output->box_end();
	}

    /**
     * Show the introduction text is as set in the activity description
     */
    public function show_intro() {
        $ret = "";
        $ret .= $this->output->box_start('generalbox');
        $ret .= get_string('uploadinstructions', 'block_edmodo');;
        $ret .= $this->output->box_end();
        return $ret;
    }

}
