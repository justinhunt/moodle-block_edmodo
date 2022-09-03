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
 * Create a unique temporary directory with a given prefix name,
 * inside a given directory, with given permissions. Return the
 * full path to the newly created temp directory.
 *
 * @param string $dir where to create the temp directory.
 * @param string $prefix prefix for the temp directory name (default '')
 *
 * @return string The full path to the temp directory.
 */
function my_mktempdir($dir, $prefix='') {
    global $CFG;

    if (substr($dir, -1) != '/') {
        $dir .= '/';
    }

    do {
        $path = $dir.$prefix.mt_rand(0, 9999999);
    } while (file_exists($path));

    check_dir_exists($path);

    return $path;
}

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
    function export_qqfile($edmodosets, $casesensitive,$multichoice_numbering){
        $filecontent = $this->make_qqfile($edmodosets,$casesensitive,$multichoice_numbering);
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
    function process_jsonfiles ($dir, &$results) {
        global $OUTPUT;
        if(!($handle = opendir($dir))) {
            echo $OUTPUT->notification(get_string('cannotprocessdir', 'block_edmodo'));
            return;
        }

        while (false !== ($item = readdir($handle))) {
            if ($item != '.' && $item != '..') {
                if (is_dir($dir.'/'.$item)) {
                    $this->process_jsonfiles($dir.'/'.$item,  $results);
                } else if (is_file($dir.'/'.$item))  {
                    $ext = pathinfo($dir.'/'.$item, PATHINFO_EXTENSION);
                    if($ext=="json" && strpos($item,'quiz_content')>-1) {
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
    function make_qqfile($edmodosets,$casesensitive,$multichoice_numbering)
    {

        // build XML file - based on moodle/question/xml/format.php
        // add opening tag
        $expout = "";
        $counter = 0;


        foreach($edmodosets as $edmodoquiz){

            //print out category
            $expout .= $this->print_category($edmodoquiz->containing_folder_name, $edmodoquiz->slug);

            //make sure ths quiz has questions!!
            if(!isset($edmodoquiz->simplified_questions)){continue;}

            //nesting on edmodo set, then question type, then each element in edmodo set as a question
            foreach ($edmodoquiz->simplified_questions as $edmododata) {
                if (!empty($edmododata)) {


                    $questiontype = $this->edmodo_qtype_to_moodle_qtype($edmododata->question_type);

                    //prepare data by question type for processing
                    $terms = array();
                    switch ($questiontype) {
                        case 'multichoice':
                        case 'multichoice_ma':
                            $answerstyle = $multichoice_numbering;//abc ABC 123 none
                            break;
                        case 'matching':
                        case 'cloze':
                            $answerstyle = $casesensitive;// Case Sensitive 1 / Case insensitive 0
                            break;
                    }

                    //make the body of the export per question
                    switch ($questiontype) {
                        case 'multichoice':
                            $counter++;
                            $expout .= $this->data_to_mc_question($edmododata, $answerstyle, $counter);
                            break;

                        case 'multichoice_ma':
                            $counter++;
                            $expout .= $this->data_to_mc_question($edmododata, $answerstyle, $counter,true);
                            break;

                        case 'essay':
                            $counter++;
                            $expout .= $this->data_to_essay_question($edmododata, $counter);
                            break;

                        case 'matching':
                            $counter++;
                            $expout .= $this->data_to_matching_question($edmododata, $counter);
                            break;

                        case 'truefalse':
                            $counter++;
                            $expout .= $this->data_to_tf_question($edmododata, $counter);
                            break;

                        case 'cloze':
                            $counter++;
                            $expout .= $this->data_to_cloze_question($edmododata, $counter);
                            break;

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

   
     //export direct to qbank
   function export_qq_to_qbank($edmodosets,$casesensitive,$multichoice_numbering,$category, $pageurl){
       global $CFG, $DB, $COURSE;
       $success=true;
       //get export file
       $filecontent = $this->make_qqfile($edmodosets,$casesensitive,$multichoice_numbering);
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
    
    function print_category($containing_folder, $slug){
		   $ret = "";
		   $cleanfolder = $this->clean_name($containing_folder);
           $cleantitle = $this->clean_name($slug);
		   $categorypath = $this->writetext( 'edmodoquestions/' . $cleanfolder . '/' . $cleantitle );
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
            case 'short_answer': return 'essay';
            case 'fill_in_blank': return 'cloze';
            case 'matching': return 'matching';
            case 'multi_answer': return 'multichoice_ma';
        }
    }

   
   function data_to_matching_question($qdata,  $counter){

           $ret = "";
            $files = $this->parsefiles($qdata);

            $ret .= "\n\n<!-- question: $counter  -->\n";            
            $qtformat = "html";
            $ret .= "  <question type=\"matching\">\n";
            $ret .= "    <name><text>Matching</text></name>\n";
            $ret .= "    <questiontext format=\"$qtformat\">\n";
           if(count( $files)>0){
               $links='';
               $filetags='';
               foreach($files as $filesdata){
                   $links .= $filesdata['text'];
                   $filetags .= $filesdata['file'];
               }
               $ret .= $this->writetext( $qdata->text . $links );
               $ret .= $filetags;
           }else{
               $ret .= $this->writetext( $qdata->text );
           }
            $ret .= "    </questiontext>\n";
            for($i=0;$i<count($qdata->choices);$i++){
                $theterm = trusttext_strip($qdata->correct_answers[$i]);
                $thedefinition = trusttext_strip($qdata->choices[$i]);
                $theimage =false; // $entry->image;

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

            }
           
            // close the question tag
            $ret .= "</question>\n";		
            return $ret;
	}//end of function            

    function data_to_sa_question($qdata, $questiontype, $answerstyle, $counter){

        $ret = "";
        $files = $this->parsefiles($qdata);

        $currentterm = trusttext_strip($qdata->choices[$qdata->correct_answer]);

        $ret .= "\n\n<!-- question: $counter  -->\n";
        $qtformat = "html";
        $ret .= "  <question type=\"$questiontype\">\n";
        $ret .= "    <name><text>short answer</text></name>\n";
        $ret .= "    <questiontext format=\"$qtformat\">\n";
        if(count( $files)>0){
            $links='';
            $filetags='';
            foreach($files as $filesdata){
                $links .= $filesdata['text'];
                $filetags .= $filesdata['file'];
            }
            $ret .= $this->writetext( $qdata->text . $links );
            $ret .= $filetags;
        }else{
            $ret .= $this->writetext( $qdata->text );
        }

        $ret .= "    </questiontext>\n";


        $ret .= "    <usecase>$answerstyle</usecase>\n ";
        $percent = 100;
        $ret .= "    <answer fraction=\"$percent\">\n";
        $ret .= $this->writetext( $currentterm,3,false );
        $ret .= "    </answer>\n";


        // close the question tag
        $ret .= "</question>\n";
        return $ret;
    }//end of function

    function data_to_essay_question($qdata,  $counter){

        $ret = "";
        $files = $this->parsefiles($qdata);

        $ret .= "\n\n<!-- question: $counter  -->\n";
        $qtformat = "html";
        $ret .= "  <question type=\"essay\">\n";
        $ret .= "    <name><text>Short Answer (essay)</text></name>\n";
        $ret .= "    <questiontext format=\"$qtformat\">\n";
        if(count( $files)>0){
            $links='';
            $filetags='';
            foreach($files as $filesdata){
                $links .= $filesdata['text'];
                $filetags .= $filesdata['file'];
            }
            $ret .= $this->writetext( $qdata->text . $links );
            $ret .= $filetags;
        }else{
            $ret .= $this->writetext( $qdata->text );
        }

        $ret .= "    </questiontext>\n";
        $ret .= "    <answer fraction=\"0\">\n";
        $ret .= "      <text></text>\n";
        $ret .= "    </answer>\n";


        // close the question tag
        $ret .= "</question>\n";
        return $ret;
    }//end of function

    function data_to_cloze_question($qdata,  $counter){

        $ret = "";
        $files = $this->parsefiles($qdata);

        $ret .= "\n\n<!-- question: $counter  -->\n";
        $qtformat = "html";
        $ret .= "  <question type=\"cloze\">\n";
        $ret .= "    <name><text>Fill in the blanks</text></name>\n";
        $ret .= "    <questiontext format=\"$qtformat\">\n";

        //make sure we have answers otherwise and then make cloze bits
        if(!empty($qdata->correct_answers) && is_countable($qdata->correct_answers)) {
            foreach ($qdata->correct_answers as $canswer) {
                $cloze_answer = "&nbsp;{1:SHORTANSWER:=$canswer}&nbsp;";
                $pos = strpos($qdata->text, '_');
                if ($pos !== false) {
                    $qdata->text = substr_replace($qdata->text, $cloze_answer, $pos, 1);
                }
            }
        }
        if(count( $files)>0){
            $links='';
            $filetags='';
            foreach($files as $filesdata){
                $links .= $filesdata['text'];
                $filetags .= $filesdata['file'];
            }
            $ret .= $this->writetext( $qdata->text . $links );
            $ret .= $filetags;
        }else{
            $ret .= $this->writetext( $qdata->text );
        }
        $ret .= "    </questiontext>\n";


        // close the question tag
        $ret .= "</question>\n";
        return $ret;
    }//end of function


    function data_to_mc_question($qdata,  $answerstyle, $counter,$multianswer=false){

            $ret = "";

            //make sure we have answers otherwise and then make cloze bits
            if(empty($qdata->choices) || !is_countable($qdata->choices)) {
                return $ret;
            }

            $files = $this->parsefiles($qdata);

        	$ret .= "\n\n<!-- question: $counter  -->\n";
            $qtformat = "html";
            $ret .= "  <question type=\"multichoice\">\n";
            $ret .= "    <name><text>Multi Choice</text></name>\n";
            $ret .= "    <questiontext format=\"$qtformat\">\n";

            if(count( $files)>0){
                $links='';
                $filetags='';
                foreach($files as $filesdata){
                    $links .= $filesdata['text'];
                    $filetags .= $filesdata['file'];
                }
                $ret .= $this->writetext( $qdata->text . $links );
                $ret .= $filetags;
            }else{
                $ret .= $this->writetext( $qdata->text );
            }
           
            $ret .= "    </questiontext>\n";

            $ret .= "    <shuffleanswers>true</shuffleanswers>\n";
            if($multianswer) {
                $ret .= "    <single>false</single>\n";
            }else{
                $ret .= "    <single>true</single>\n";
            }
            $ret .= "    <answernumbering>".$answerstyle."</answernumbering>\n";

            $correctanswers=[];
            if($multianswer){
                foreach($qdata->selected_answers as $key=>$answer){
                    if($answer){
                        $correctanswers[]=$key;
                    }
                }
            }else{
                $correctanswers[]=  $qdata->correct_answer;
            }

            for ($i=0; $i<count($qdata->choices); $i++) {
                $thechoice = $qdata->choices[$i];
                if(empty($thechoice) || !is_string($thechoice)){
                    $thechoice ='-';
                }


                    //If we have files inline in the answers we need to process those.
                   // Its a bit hacky but we make a dummy qdata object so that we can pass the attachments info to parsefiles function
                    $alinks='';
                    $afiletags='';
                    if(isset($qdata->answer_attachments) && isset($qdata->answer_attachments[$i]) && $qdata->answer_attachments[$i]!==null){
                        $dummyqdata = new \stdClass();
                        $dummyqdata->attachments = new \stdClass();
                        $dummyqdata->attachments->files = [$qdata->answer_attachments[$i]];
                        $answerfiles = $this->parsefiles($dummyqdata);
                        foreach($answerfiles as $afilesdata){
                            $alinks .= $afilesdata['text'];
                            $afiletags .= $afilesdata['file'];
                        }
                        $thechoice .= $alinks;
                    }
                    if (in_array($i,$correctanswers)) {
                            $percent = 100;
                            $ret .= "      <answer fraction=\"$percent\">\n";

                            $ret .= $this->writetext($thechoice,3,false )."\n";
                            //output file tags if we have them
                            if(!empty($afiletags)){$ret .= $afiletags;}
                            $ret .= "      <feedback>\n";
                            $ret .= "      <text>\n";
                            $ret .= "      </text>\n";
                            $ret .= "      </feedback>\n";
                            $ret .= "    </answer>\n";
                    } else {
                            $percent = 0;

                            $distracter =trusttext_strip($thechoice); ;
                            $ret .= "      <answer fraction=\"$percent\">\n";
                            $ret .= $this->writetext( $distracter,3,false )."\n";
                            if(!empty($afiletags)){$ret .= $afiletags;}
                            $ret .= "      <feedback>\n";
                            $ret .= "      <text>\n";
                            $ret .= "      </text>\n";
                            $ret .= "      </feedback>\n";
                            $ret .= "    </answer>\n";
                    } //end of if $i === 0
            }//end of for i loop

            // close the question tag
            $ret .= "</question>\n";		
            return $ret;
	}//end of function

    function data_to_tf_question($qdata, $counter){

        $ret = "";
        $files = $this->parsefiles($qdata);

        $ret .= "\n\n<!-- question: $counter  -->\n";
        $qtformat = "html";
        $ret .= "  <question type=\"truefalse\">\n";
        $ret .= "    <name><text>True/False</text></name>\n";
        $ret .= "    <questiontext format=\"$qtformat\">\n";
        if(count($files)>0){
            $links='';
            $filetags='';
            foreach($files as $filesdata){
                $links .= $filesdata['text'];
                $filetags .= $filesdata['file'];
            }
            $ret .= $this->writetext( $qdata->text . $links );
            $ret .= $filetags;
        }else{
            $ret .= $this->writetext( $qdata->text );
        }
        $ret .= "    </questiontext>\n";

        $truepercent=0;
        $falsepercent=0;
        if ($qdata->correct_answer===true) {
            $truepercent = 100;
        }else{
            $falsepercent = 0;
        }
        $ret .= " <answer fraction=\"$truepercent\">\n";
        $ret .= "      <text>true</text>\n";
        $ret .= "      <feedback>\n";
        $ret .= "      <text>\n";
        $ret .= "      </text>\n";
        $ret .= "      </feedback>\n";
        $ret .= "    </answer>\n";
        $ret .= " <answer fraction=\"$falsepercent\">\n";
        $ret .= "      <text>false</text>\n";
        $ret .= "      <feedback>\n";
        $ret .= "      <text>\n";
        $ret .= "      </text>\n";
        $ret .= "      </feedback>\n";
        $ret .= "    </answer>\n";

        // close the question tag
        $ret .= "</question>\n";
        return $ret;
    }//end of function

    function parsefiles($qdata){
        $files=[];
        if(isset($qdata->attachments->files) && count($qdata->attachments->files)>0){
            foreach($qdata->attachments->files as $file){
                if(in_array(strtolower($file->file_type),['jpg','jpeg','gif','png','svg','webp','bmp'])){
                    $files[]=$this->writelink($file->download_url,$file->file_name, true,'base64');
                }else{
                    $files[]=$this->writelink($file->download_url,$file->file_name, false,'base64');
                }
            }
        }

        if(isset($qdata->attachments->embeds) && count($qdata->attachments->embeds)>0){
            foreach($qdata->attachments->embeds as $embed) {
                //we might get lucky and get a youtube embed link, which we can patch up for Moodle to display properly
                $embed->content = str_replace('https://www.youtube-nocookie.com/embed/','https://www.youtube.com/watch?v=',$embed->content);
                $files[] = ['text'=>'<p><a href="' . $embed->content . '" />' . $embed->title . '</a></p>','file'=>''];
            }
        }
        if(isset($qdata->attachments->links) && count($qdata->attachments->links)>0){
            foreach($qdata->attachments->links as $link) {
                $files[] = ['text'=>'<p><a href="' . $link->link_url . '" />' . $link->title . '</a></p>','file'=>''];
            }
        }
        return $files;
    }

    /**
     * generates <text></text> tags, processing raw text therein
     * @param int ilev the current indent level
     * @param boolean short stick it on one line
     * @return string formatted text
     */
    function writetext($raw, $ilev = 0, $short = true) {
        $indent = str_repeat('  ', $ilev);
		

		if(!empty($raw)){
            //tweak new lines
			$raw = str_replace("\n",'<br />',$raw);
			$raw = str_replace("\r\n",'<br />',$raw);

            //clean up any bad characters
            //$raw = preg_replace('/\x0b/', ' ', $raw); //found VT , ETX  ,STX probably ms word artifacts
            $raw = preg_replace('/[[:cntrl:]]/', '', $raw);

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

    function writelink($url, $originalname,$isimage=true, $encoding='base64') {
        if (!($url)) {
            return '';
        }

        $filedata = $this->fetchfile($url);
        $textstring = '';
        $filestring = '';
        if($isimage) {
            $textstring.= '<p><img src="@@PLUGINFILE@@/' . $originalname . '" alt="' . $originalname . '"/></p>';
        }else{
            $textstring .= '<p><a href="@@PLUGINFILE@@/' . $originalname . '" />' .$originalname . '</a></p>';
        }
        $filestring  .= '<file name="' . $originalname . '" path="/" encoding="' . $encoding . '">';
        $filestring  .= base64_encode($filedata);
        $filestring  .= '</file>';

        return ['text'=>$textstring,'file'=>$filestring];
    }

    function fetchfile($url){
        $headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg, image/*, audio/*, video/*, application/pdf';
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
