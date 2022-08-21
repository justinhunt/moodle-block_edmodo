<?php   // $Id: exportfile.php,v 1.8 2007/08/17 12:49:31 skodak Exp $

	global $SESSION, $DB;	
	
	require_once("../../config.php");
	require_once($CFG->dirroot.'/mod/edmodoimport/edmodo.php');
    require_once("../../lib/filelib.php");

    $courseid = required_param('courseid', PARAM_INT);      // Course Module ID
    $edmodosets = optional_param_array('selectedset',array(), PARAM_ALPHANUMEXT);
    $exporttype = optional_param('exporttype',0, PARAM_ALPHANUMEXT);
    $questiontypes =  optional_param_array('questiontype',array(), PARAM_ALPHANUMEXT);  
    $activitytypes =  optional_param_array('activitytype',array(), PARAM_ALPHANUMEXT); 

/*
    if (! $course = $DB->get_record("course", array('id' => $courseid))) {
        error("Course is misconfigured");
    }
*/
    require_login();
    
	//Set up page
	$context = context_user::instance($USER->id);


   // require_capability('block/edmodo:export', $context);
	/*

    $filename = clean_filename(strip_tags(format_string($glossary->name,true)).'.xml');
    $giftcategoryname = $glossary->name;
	*/
	/*
	echo  'QUIZLETSETS<br />';
	 foreach($edmodosets as $set){
		echo $set . '<br />';
	 }
	 
	 echo  'ACTIVITYTYPES<br />';
	  foreach($activitytypes as $activity){
		echo $activity . '<br />';
	 }
	 
	 echo  'QUESTIONTYPES<br />';
	 foreach($questiontypes as $qtype){
		echo $qtype . '<br />';
	 }
	
	return;
	*/
    
	
	//if drag and drop export, make file
	if($exporttype=='dragdrop'){
		$filename = "edmodoset_dragdrop.txt";
		$content="";
		foreach($edmodosets as $qset){
			$qset_params = explode("-", $qset);
    		$qsetid = $qset_params[0];
    		$qsetname = $qset_params[1];
    		
			 foreach($activitytypes as $activity){
				$content.="name=$qsetname,activitytype=$activity,edmodoset=$qsetid,edmodosettitle=$qsetname,mintime=0,showcountdown=0,showcompletion=0\n\n";
			}
		}
		send_file($content, $filename, 0, 0, true, true); 
		return;
	}
	
	
	//Initialize Edmodo
	//assumption here is that we authenticated back on the mod_form 
	 $args = array( 'api_scope' => 'read');
	$qiz  = new edmodo($args);
	//if authenticated we can start fetching data
	$select = "";
	if($qiz->is_authenticated()){
		$qset_ids = array();
		foreach($edmodosets as $qset){
			$qset_params = explode("-", $qset);
    		$qset_ids[] = $qset_params[0];
		}
		

		$endpoint = "sets";
		$qset_ids_string =  implode(",", $qset_ids);
		$params=array();
		//sample two sets
		//$qset_ids_string = "415,13381475";
		//animals
		//$qset_ids_string = "10622858";
		$params['set_ids']=$qset_ids_string;
		
		/*
		$params=array();
		$params['term']='silla';
		$params['q']='spanish';
		$endpoint = '/search/sets';
		*/
		$qiz_return = $qiz->request($endpoint,$params);
		
		/*
		if($qiz_return['success']){
			foreach ($qiz_return['data'] as $edmododata){
				print_r($edmododata);
			}
		}else{
			print_r($qiz_return);
			echo "<br/> idstring: " . $qset_ids_string;
		}
		*/
		if(!$qiz_return['success']){
			print_r($qiz_return);
			echo "<br/> idstring: " . $qset_ids_string;
			return;
		}
			
	}else{
		echo "uh oh: not authenticated.";
		return;
	}
	
	
    // build XML file - based on moodle/question/xml/format.php
    // add opening tag
    $expout = "";
    $counter=0;
	$filename ="edmodoimportdata.xml";
	//nesting on edmodo set, then question type, then each element in edmodo set as a question
	foreach	($qiz_return['data'] as $edmododata){
		  if ( $entries = $edmododata->terms) {
				//for each passed in question type
				foreach ($questiontypes as $qtype){
					$questiontype_params = explode("_", $qtype);
					$questiontype = $questiontype_params[0];
			
					//print out category
					$expout .= print_category($edmododata, $qtype);
			
					if ($questiontype == 'multichoice') {
						$answerstyle = $questiontype_params[1]; 
						$terms = array();    
						foreach ($entries as $entry) {
							$terms[] = $entry->term;          
						}
					} else {
						$answerstyle = $questiontype_params[1];
					}
					foreach ($entries as $entry) {
						$counter++;
						$expout .= data_to_question($entry,$terms,$questiontype, $answerstyle,$counter);
					}
				}//end of for each qtype
			}//end of if entries
	}//end of for each edmodo data
    	
    	 // initial string;
        // add the xml headers and footers
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                       "<quiz>\n" .
                       $expout . "\n" .
                       "</quiz>";

        // make the xml look nice
		$content = xmltidy( $content );	
		//return the content
		send_file($content, $filename, 0, 0, true, true);  
		return;
         
            
    function clean_name($originalname){
    	return preg_replace("/[^A-Za-z0-9]/", "_", $originalname);
    }        
    
    function print_category($edmododata, $questiontype){
		   $ret = "";
		   $cleanname = clean_name($edmododata->title);
		   $categorypath = writetext( 'edmodoquestions/' . $cleanname . '/' . $questiontype );
           $ret  .= "  <question type=\"category\">\n";
           $ret  .= "    <category>\n";
           $ret  .= "        $categorypath\n";
           $ret  .= "    </category>\n";
           $ret  .= "  </question>\n"; 
		return $ret;
	}
	
	function data_to_question($entry,$allterms, $questiontype, $answerstyle, $counter){
	
		$ret = "";
		$definition = trusttext_strip($entry->definition);
            $currentterm = trusttext_strip($entry->term);
            $currentimage = $entry->image;
            
        	$ret .= "\n\n<!-- question: $counter  -->\n";            
    		$name_text = writetext( $currentterm );
            $qtformat = "html";
            $ret .= "  <question type=\"$questiontype\">\n";
            $ret .= "    <name>$name_text</name>\n";
            $ret .= "    <questiontext format=\"$qtformat\">\n";
            if($entry->image){
            	 $ret .= writeimage( $currentimage);
            }else{
            	$ret .= writetext( $definition );
            }
           
            $ret .= "    </questiontext>\n";

				if ($questiontype == 'multichoice') {
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
							$ret .= writetext( $currentterm,3,false )."\n";
							$ret .= "      <feedback>\n";
							$ret .= "      <text>\n";
							$ret .= "      </text>\n";
							$ret .= "      </feedback>\n";                    
							$ret .= "    </answer>\n";
						} else {
							$percent = 0;
							$distracter = $allterms[$rand_keys[$i-1]];
							$ret .= "      <answer fraction=\"$percent\">\n";
							$ret .= writetext( $distracter,3,false )."\n";
							$ret .= "      <feedback>\n";
							$ret .= "      <text>\n";
							$ret .= "      </text>\n";
							$ret .= "      </feedback>\n";
							$ret .= "    </answer>\n";
						} //end of if $i === 0
					}//end of for i loop
				} else { // shortanswer				
					$ret .= "    <usecase>$answerstyle</usecase>\n ";
					$percent = 100;
					$ret .= "    <answer fraction=\"$percent\">\n";
					$ret .= writetext( $currentterm,3,false );
					$ret .= "    </answer>\n";
				}//end of if
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
			$raw = str_replace('\n','<br \>',$raw);
			$raw = str_replace('\r\\n','<br \>',$raw);
		}
        // if required add CDATA tags (if we have html or new line tags)
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

    function writeimage($image, $encoding='base64') {
        if (!($image)) {
            return '';
        }
        $filename = basename($image->url);
        $width = $image->width;
        $height = $image->height;
        $imagestring = fetchimage($image->url);
        $string = '';
		$string .= '<text><![CDATA[<p><img src="@@PLUGINFILE@@/' . $filename . '" alt="' . $filename . '" width="' . $width . '"  height="' . $height . '" /></p>]]></text>';
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
?>