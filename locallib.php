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

require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Edmodo Quiz
 *
 * @package    block_edmodo
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2014 onwards Justin Hunt
 *
 *
 */

class block_edmodo_helper {

	/**
     * constructor. make sure we have the right course
     * @param integer courseid id
	*/
	function __construct($exporttype=false) {
            $this->exporttype=$exporttype;
        }
   
    //fetch question type file content, and export
    function export_qqfile($edmodosets){
        $filecontent = $this->make_qqfile($edmodosets);
        $filename ="edmodoimportdata.xml";
        send_file($filecontent, $filename, 0, 0, true, true);  
        return;
    }

    /**
     * Recursively process a directory, reading the JSON files
     * them to process_file().
     *
     * @param string $dir the full path of the directory to process
     * @param array $results (by reference) accumulated statistics of
     *              users updated and errors.
     *
     * @return nothing
     */
    function load_jsonfiles ($dir, &$results) {
        global $OUTPUT;
        if(!($handle = opendir($dir))) {
            echo $OUTPUT->notification(get_string('cannotprocessdir', 'block_edmodo'));
            return;
        }

        while (false !== ($item = readdir($handle))) {
            if ($item != '.' && $item != '..') {
                if (is_dir($dir.'/'.$item)) {
                    $this->load_jsonfiles($dir.'/'.$item,  $results);
                } else if (is_file($dir.'/'.$item))  {
                    $ext = pathinfo($dir.'/'.$item, PATHINFO_EXTENSION);
                    if($ext=="json") {
                        $json_file = file_get_contents($dir . '/' . $item);
                        if(!$json_file){
                            $results['errors']++;
                        }else{
                            $results["quizzes"][]=json_decode($json_file);
                            $results['updated']++;
                        }

                    }
                }
            }
        }
        closedir($handle);
    }



    //if question import export, make file content
    function make_qqfile($edmodosets)
    {

        //this whole question type parsing is still rough
        //need to clean up.

        // build XML file - based on moodle/question/xml/format.php
        // add opening tag
        $expout = "";
        $counter = 0;

    foreach($edmodosets as $edmodoquiz){
        //nesting on edmodo set, then question type, then each element in edmodo set as a question
        foreach ($edmodoquiz->simplified_questions as $edmododata) {
            if (!empty($edmododata)) {

                //for each passed in question type

                $questiontype_params = explode("_", $qtype);
                $questiontype = $questiontype_params[0];

                //print out category
                $expout .= $this->print_category($edmododata, $qtype);

                //prepare data by question type for processing
                $terms = array();
                switch ($questiontype) {
                    case 'multichoice':

                        $answerstyle = $questiontype_params[1];
                        foreach ($entries as $entry) {
                            if ($answerside == 0) {
                                $terms[] = $entry->term;
                            } else {
                                $terms[] = $entry->definition;
                            }
                        }
                        break;
                    case 'matching':
                    case 'shortanswer':
                        $answerstyle = $questiontype_params[1];
                        break;
                }

                //make the body of the export per question
                switch ($questiontype) {
                    case 'multichoice':
                    case 'shortanswer':
                        foreach ($entries as $entry) {
                            $counter++;
                            $expout .= $this->data_to_mc_sa_question($entry, $terms, $questiontype, $answerstyle, $counter, $answerside);
                        }
                        break;
                    case 'matching':
                        $entrycount = count($entries);
                        $lastentries = $entrycount % $config->matchingsubcount;
                        $entriesmd = array_chunk($entries, $config->matchingsubcount, false);
                        $entriesmdcount = count($entriesmd);
                        //here we pad the last chunk with additional entries if it is too small
                        if ($entriesmdcount > 1 && $lastentries > 0) {
                            for ($x = 0; $x < ($config->matchingsubcount - $lastentries); $x++) {
                                $entriesmd[$entriesmdcount - 1][] = $entriesmd[$entriesmdcount - 2][$config->matchingsubcount - $x - 1];
                            }
                        }
                        //here we pass in chunks of entries to make matching questions
                        $qsetname = $this->clean_name($edmododata->title);
                        foreach ($entriesmd as $entryset) {
                            $expout .= $this->data_to_matching_question($entryset, $questiontype, $qsetname . '_' . $counter, $counter, $answerside);
                            $counter++;
                        }

                }
            }//end of if entries
        }//end of for each edmodo quiz
    }//end of edmodosets
    	
    	 // initial string;
        // add the xml headers and footers
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                       "<quiz>\n" .
                       $expout . "\n" .
                       "</quiz>";

        // make the xml look nice
        $content = $this->xmltidy( $content );	
        //return the content
       return $content;
    }

   

   //fetch a mform ready list of course sections for appending activities to
   function fetch_section_list(){
	global $CFG, $COURSE, $DB;
	$conditions=array('course'=>$COURSE->id);
	$sections = $DB->get_records_menu('course_sections',$conditions,'section','section,name'); 
	 //massage the section data a little, just in case the name field is blank
	 $usesections = array();
	 foreach($sections as $sectionid=>$sectionname){
		$usesections[$sectionid] = $sectionid . ': ' . $sectionname;
	 }
    return $usesections;
   }
   
     //export direct to qbank
   function export_qq_to_qbank($edmodosets,$questiontypes, $answerside,$category, $pageurl){
       global $CFG, $DB, $COURSE;
       $success=true;
       //get export file
       $filecontent = $this->make_qqfile($edmodosets);
        $categorycontext = context::instance_by_id($category->contextid);
        $category->context = $categorycontext;
        $contexts = new question_edit_contexts($categorycontext);

        $realfilename = 'edmodo_tmp' . time() . '.xml';
        $importfile = "{$CFG->tempdir}/questionimport/{$realfilename}";
        $result = make_temp_directory('questionimport');
        if($result){$result = file_put_contents($importfile, $filecontent);}
        if (!$result) {
            throw new moodle_exception('uploadproblem');
              $success=false;
        }
        //die;
        //get the xml format processor
        $qformat = new qformat_xml();

        // load data into class
        $qformat->setCategory($category);
        $qformat->setContexts($contexts);
        $qformat->setCourse($COURSE);
        $qformat->setFilename($importfile);
        $qformat->setRealfilename($realfilename);
        $qformat->setMatchgrades('error');
        $qformat->setCatfromfile(true);
        $qformat->setContextfromfile(true);
        $qformat->setStoponerror(true);

        // Do anything before that we need to
        if (!$qformat->importpreprocess()) {
            print_error('cannotimport', '', $pageurl->out());
              $success=false;
        }

        // Process the uploaded file
        if (!$qformat->importprocess($category)) {
            print_error('cannotimport', '', $pageurl->out());
              $success=false;
        }

        // In case anything needs to be done after
        if (!$qformat->importpostprocess()) {
            print_error('cannotimport', '', $pageurl->out());
            $success=false;
        }
         return $success;
       
   }
    
        
    function clean_name($originalname){
    	return preg_replace("/[^A-Za-z0-9]/", "_", $originalname);
    }        
    
    function print_category($edmododata, $questiontype){
		   $ret = "";
		   $cleanname = $this->clean_name($edmododata->title . '_' . $edmododata->created_by . '_' . $edmododata->id);
		   $categorypath = $this->writetext( 'edmodoquestions/' . $cleanname . '/' . $questiontype );
           $ret  .= "  <question type=\"category\">\n";
           $ret  .= "    <category>\n";
           $ret  .= "        $categorypath\n";
           $ret  .= "    </category>\n";
           $ret  .= "  </question>\n"; 
		return $ret;
	}

    function edmodo_qtype_to_moodle_qtype($edmodo_qtype){
        switch($edmodo_qtype){
            case 'true_false': return 'truefalse';
            case 'multi_choice': return 'multichoice';
            case 'short_answer': return 'shortanswer';
            case 'fill_in_blank': return 'cloze';
            case 'matching': return 'matching';
            case 'multi_answer': return 'multichoice';

        }
    }

   
   function data_to_matching_question($allentries, $questiontype, $qname, $counter,$answerside){
	
            $ret = "";          
            $ret .= "\n\n<!-- question: $counter  -->\n";            
            $qtformat = "html";
            $ret .= "  <question type=\"$questiontype\">\n";
            $ret .= "    <name>" . $this->writetext($qname,2,true ). "</name>\n";
            $ret .= "    <questiontext format=\"$qtformat\">\n";
            $ret .= $this->writetext(get_string('matchingquestiontext','block_edmodo'),2,false);
            $ret .= "    </questiontext>\n";
            foreach($allentries as $entry){
                if($answerside==0){
                    $thedefinition = trusttext_strip($entry->definition);
                    $theterm = trusttext_strip($entry->term);
                }else{
                   $thedefinition = trusttext_strip($entry->term);
                    $theterm = trusttext_strip($entry->definition); 
                }
                $theimage = $entry->image;
                
                switch($questiontype){
                    case 'matching':
                         $ret .= "<subquestion format=\"html\">\n ";
                         if($theimage){
                            $ret .= $this->writeimage( $theimage,'base64',$thedefinition)."\n";  
                         }else{
                            $ret .= $this->writetext( $thedefinition,3,false )."\n";          
                         }
                           $ret .= "    <answer>\n";
                           $ret .= $this->writetext( $theterm,3,true );
                           $ret .= "    </answer>\n";
                            $ret .= "</subquestion>\n";
                           break;
                }//end of switch
            }
           
            // close the question tag
            $ret .= "</question>\n";		
            return $ret;
	}//end of function            
	
        
        
	function data_to_mc_sa_question($entry,$allterms, $questiontype, $answerstyle, $counter,$answerside){
	
		$ret = "";
            if($answerside==0){
                $definition = trusttext_strip($entry->definition);
                $currentterm = trusttext_strip($entry->term);
            }else{
                $definition = trusttext_strip($entry->term);
                $currentterm = trusttext_strip($entry->definition);
            }
            $currentimage = $entry->image;
            
        	$ret .= "\n\n<!-- question: $counter  -->\n";            
    		$name_text = $this->writetext( $currentterm );
            $qtformat = "html";
            $ret .= "  <question type=\"$questiontype\">\n";
            $ret .= "    <name>$name_text</name>\n";
            $ret .= "    <questiontext format=\"$qtformat\">\n";
            if($entry->image){
            	 $ret .= $this->writeimage( $currentimage,'base64',$definition);
            }else{
            	$ret .= $this->writetext( $definition );
            }
           
            $ret .= "    </questiontext>\n";

            switch($questiontype){
                case 'multichoice':
                    $answerscount = 4;
                    $ret .= "    <shuffleanswers>true</shuffleanswers>\n";
                    $ret .= "    <answernumbering>".$answerstyle."</answernumbering>\n";
                    //$terms2 = $terms;
                    //try terms2 simply as allterms
                    foreach ($allterms as $key => $value) {
                       if ($value == $currentterm) {
                               unset($allterms[$key]);
                            }//end of if
                    }//end of foreach

                    //make sure we have enough terms in the edmodo set to make the question
                    //if not use fewer answers
                    if(count($allterms)<$answerscount){
                            $answerscount = count($allterms) + 1;
                    }


                    //get a random list of distractor answers
                    //if we only have 1 distratctor, it won't be an array so we make one
                    $rand_keys = array_rand($allterms, $answerscount-1);
                    if(!is_array($rand_keys)){
                            $rand_keys=array($rand_keys);
                    }

                    for ($i=0; $i<$answerscount; $i++) {
                            if ($i === 0) {
                                    $percent = 100;
                                    $ret .= "      <answer fraction=\"$percent\">\n";
                                    $ret .= $this->writetext( $currentterm,3,false )."\n";
                                    $ret .= "      <feedback>\n";
                                    $ret .= "      <text>\n";
                                    $ret .= "      </text>\n";
                                    $ret .= "      </feedback>\n";                    
                                    $ret .= "    </answer>\n";
                            } else {
                                    $percent = 0;
                                    $distracter = $allterms[$rand_keys[$i-1]];
                                    $ret .= "      <answer fraction=\"$percent\">\n";
                                    $ret .= $this->writetext( $distracter,3,false )."\n";
                                    $ret .= "      <feedback>\n";
                                    $ret .= "      <text>\n";
                                    $ret .= "      </text>\n";
                                    $ret .= "      </feedback>\n";
                                    $ret .= "    </answer>\n";
                            } //end of if $i === 0
                    }//end of for i loop
                    break;
            case 'shortanswer':				
                    $ret .= "    <usecase>$answerstyle</usecase>\n ";
                    $percent = 100;
                    $ret .= "    <answer fraction=\"$percent\">\n";
                    $ret .= $this->writetext( $currentterm,3,false );
                    $ret .= "    </answer>\n";
                    break;

            }//end of switch
           
            // close the question tag
            $ret .= "</question>\n";		
            return $ret;
	}//end of function            
	
    /**
     * generates <text></text> tags, processing raw text therein
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */
    function writetext($raw, $ilev = 0, $short = true) {
        $indent = str_repeat('  ', $ilev);
		
		//tweak new lines
		if(!empty($raw)){
			$raw = str_replace("\n",'<br />',$raw);
			$raw = str_replace("\r\n",'<br />',$raw);
		}

        // if required add CDATA tags
        if (!empty($raw) and (htmlspecialchars($raw) != $raw)) {
            $raw = "<![CDATA[$raw]]>";
        }

        if ($short) {
            $xml = "$indent<text>$raw</text>";
        } else {
            $xml = "$indent<text>\n$raw\n$indent</text>\n";
        }

        return $xml;
    }

    function writeimage($image, $encoding='base64',$text='') {
        if (!($image)) {
            return '';
        }
        $filename = basename($image->url);
        $width = $image->width;
        $height = $image->height;
        $imagestring = $this->fetchimage($image->url);
        $string = '';
		$string .= '<text><![CDATA[' . $text . '<p><img src="@@PLUGINFILE@@/' . $filename . '" alt="' . $filename . '" width="' . $width . '"  height="' . $height . '" /></p>]]></text>';
		$string .= '<file name="' . $filename . '" path="/" encoding="' . $encoding . '">';
		$string .= base64_encode($imagestring);
		$string .= '</file>';

        return $string;
    }
    
    function fetchimage($url){
		$headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';              
		$headers[] = 'Connection: Keep-Alive';         
		$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';         
		$user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)';         
		$process = curl_init($url);         
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);         
		curl_setopt($process, CURLOPT_HEADER, 0);         
		curl_setopt($process, CURLOPT_USERAGENT, $user_agent);         
		curl_setopt($process, CURLOPT_TIMEOUT, 30);         
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);         
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);         
		$return = curl_exec($process);         
		curl_close($process);         
		return $return;     
    }
    
	function xmltidy( $content ) {
        // can only do this if tidy is installed
        if (extension_loaded('tidy')) {
            $config = array( 'input-xml'=>true, 'output-xml'=>true, 'indent'=>true, 'wrap'=>0 );
            $tidy = new tidy;
            $tidy->parseString($content, $config, 'utf8');
            $tidy->cleanRepair();
            return $tidy->value;
        }
        else {
            return $content;
        }
    }	

}
