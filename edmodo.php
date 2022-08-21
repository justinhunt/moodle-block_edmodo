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

/**
 * Internal library of functions for block edmodo
 *
 * All the edmodoimport specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    block_edmodo
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

//require oauthlib for edmodo
require_once($CFG->libdir.'/oauthlib.php');

//require forms data for our search form
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');

/**
 * Authentication class to access Edmodo API
 * originally extended oauth_helper, but it was not so helpful
 * edmodo oauth works differently to facebook/google in some ways
 * i)The initial request url for an oauth_token can be made very simply
 * ii) edmodo uses http basic auth in request for access_token, facebook etc doesnt
 * iii) edmodo uses access token and username in data requests, facebook etc use access_token and secrets
 * 
 *
 * @package    block_edmodo
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edmodo_qq {
   
	const ACCESS_TOKEN = "access_token";
	const ACCESS_USERNAME= "user_id";
	const API_URL = 'https://api.edmodo.com/2.0/';
	const AUTH_URL = 'https://edmodo.com/authorize';
	const TOKEN_URL = 'https://api.edmodo.com/oauth/token';
	
	const TYPE_CARDS = 0;
	const TYPE_SCATTER = 1;
	const TYPE_SPACERACE = 2;
	const TYPE_TEST = 3;
	const TYPE_SPELLER = 4;
	const TYPE_LEARN = 5;
	const TYPE_MOODLE_QUIZ = 6;
   
    /** @var string consumer key, issued by oauth provider*/
    protected $consumer_key;
    /** @var string consumer secret, issued by oauth provider*/
    protected $consumer_secret;
    /** @var string oauth_callback url*/
    protected $oauth_callback;
	/** @var string scope of access to edmodo */
	protected $api_scope;
	
    /**
     * Constructor for edmodo class
     *
     * @param array $args
     */
    function __construct($args) {
    	
    	 global $CFG;


	 $config = get_config('block_edmodo');
         $args = array(
		'api_scope' => 'read%20write_set',
            );

        $this->consumer_key = $config->apikey;
        $this->consumer_secret = $config->apisecret;
        $this->oauth_callback = new moodle_url($CFG->wwwroot. '/admin/oauth2callback.php');
 
        if (empty($args['api_scope'])) {
                $this->api_scope ="read%20write_set";
        }else{
                $this->api_scope = $args['api_scope'];
        }
		
    }
    
	/**
     * Confirm we are authenticated
     * 
     */
	public function is_authenticated(){
		if($this->get_stored_data(self::ACCESS_TOKEN) ==null){
			return false;
		}else{
			return true;
		}
	}
	
	/**
     * Fetch the intial url for confirming auth to edmodo
     * 
     */
    public function fetch_auth_url(){
		global $PAGE;

    	$thecallbackurl = rawurlencode($this->oauth_callback->out(false));

		//This is a bit complex
		//oauth lib for moodle sends the final redirect url as "state" variable
		//the actual security check is done by getting the sessparam from that
		//see /admin/oauth2callback.php to see how it happens.
		$urlstate = $PAGE->url;
		$urlstate->param('sesskey', sesskey());
		$urlstatestring = rawurlencode($urlstate->out(false));
		
    	$ret = self::AUTH_URL . "?state={$urlstatestring}" . 
    			"&client_id={$this->consumer_key}&scope={$this->api_scope}" .
    			"&response_type=code&redirect_uri={$thecallbackurl}";
    			
    	return $ret;		
    	
    }
    
	/**
     * Request oauth access token from server
	 * If this succeeds authentication is done, and it is on 
	 * to getting data
	 *
     * @param string $token
     */
    public function get_access_token($token) {
	
		$payload = array(
			'code' => $token,
			'redirect_uri' => $this->oauth_callback->out(false),
			'grant_type' => 'authorization_code'
		);
		$curl = curl_init(self::TOKEN_URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD, "{$this->consumer_key}:{$this->consumer_secret}");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		
		/* use this for debugging. Make sure you have a writeable curllog.txt
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		$verbose = fopen(dirname(__FILE__) . '/curllog.txt', 'rw+');
		curl_setopt($curl, CURLOPT_STDERR, $verbose);
		*/
		
		$returndata = json_decode(curl_exec($curl), true);
		//error_log(print_r($returndata,true));
		
		$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
	
		// Handle return data or error
		if ($responseCode == 200) { 
			$this->store_data(self::ACCESS_TOKEN, $returndata['access_token']);
			$this->store_data(self::ACCESS_USERNAME, $returndata['user_id']);
			return $this->fetch_data_return($returndata );
		}else{
			return $this->fetch_error_return($returndata );
		}
    }
	
	private function fetch_error_return($data){
		$ret = array();
		$ret['success'] = false;
		$ret['data'] = null;
		if(!$data){
			$ret['error'] = "an unknown error occurred. Nothing recieved from edmodo.";
			return $ret;
		}
		if($data->error){
			$ret['error'] = 'Error code: ' . $data->error ;
		}
		if($data->error_description){
			$ret['error'] = $ret['error'] . ': Description: ' . $data->error_description;
		}
		return $ret;
	}
	
	private function fetch_data_return($data){
		$ret = array();
		$ret['success'] = true;
		$ret['data'] = $data;
		$ret['error'] = '';
		return $ret;
	}
	   /**
     * Request oauth protected resources
     * @param string $method
     * @param string $url
     * @param string $token
     * @param string $secret
     */
    public function request($endpoint, $params){
		//build our request URL
		$endpoint = str_replace('@username@', $this->get_stored_data(self::ACCESS_USERNAME),$endpoint);
		$endpoint = str_replace('@accesstoken@', $this->get_stored_data(self::ACCESS_TOKEN),$endpoint);
		$useparams='?whitespace=1';
		if($params){
			foreach($params  as $key => $value){
				$useparams .='&';
				$useparams .= $key . '=' . $value ;
			}
		}
		$curl = curl_init(self::API_URL . $endpoint . $useparams);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->get_stored_data(self::ACCESS_TOKEN)));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		error_log("API_URL:" . self::API_URL . $endpoint . $useparams);
		error_log("HTTPHEADER:" . 'Authorization: Bearer '. $this->get_stored_data(self::ACCESS_TOKEN));
		
		
		$data = json_decode(curl_exec($curl));
		$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		 //echo self::API_URL . $endpoint . $useparams;
		
		if (floor($responseCode / 100) != 2) { 
			// A non 200-level code is an error (edmodo API typically responds with 200 and 204 on success)
			return $this->fetch_error_return($data);
		}else{
			return $this->fetch_data_return($data);
		}
	
	
    }
	
	    /**
     * Store a token/username between requests. Currently uses
     * session named by get_storename
     *
     * @param stdClass|null $token token object to store or null to clear
     */
    protected function store_data($field, $data) {
        global $SESSION;

        //$this->accesstoken = $token;
        $name = $this->get_storename($field);

        if ($data !== null) {
            $SESSION->{$name} = $data;
        } else {
            unset($SESSION->{$name});
        }
    }
	
	    /**
     * Returns the tokenname/username field for the access_token to be stored
     * through multiple requests.
     *
     * The default implentation is to use the classname combiend
     * with the scope.
     *
     * @return string tokenname for prefernce storage
     */
    protected function get_storename($field) {
        // This is unusual but should work for most purposes.
        return get_class($this).'-'.md5($this->api_scope) . '-' . $field;
    }
	
	/**
     * Retrieve a token stored.
     *
     * @return stdClass|null token object
     */
    protected function get_stored_data($field) {
        global $SESSION;

         $name = $this->get_storename($field);

        if (isset($SESSION->{$name})) {
            return $SESSION->{$name};
        }

        return null;
    }


	public function fetch_set_as_array($setdata_array){
		$retarray = array();
		foreach ($setdata_array as $edmodoset){
					//NB ugly delimeter that passes all the way through. urrrghh
					//but it is just to create a viewable name, so no stress if the name gets messed up
					if(empty($edmodoset) || empty($edmodoset->id)){continue;}
					$qdescription = $edmodoset->title;
					$qdescription  .= ' (' . $edmodoset->term_count . ')';
					$qdescription  .= ' Author:' . $edmodoset->created_by;
					$qdescription  .= ' images:' . ($edmodoset->has_images ? 'yes' : 'no') ;
					$qkey = $edmodoset->id . "-"  . preg_replace("/[^A-Za-z0-9]/", "_", $edmodoset->title);
					$retarray[$qkey] = $qdescription;
		}
		return $retarray;
	
	}
	
	public function fetch_set_selectlist($setdata_array,$dom_id,$multiselect=false){
		$multiple = ($multiselect ? 'multiple' : '');
		if(!$multiple){
			$selectname = "selectedset"; 
			$multiple = "";
		}else{
			$selectname = "selectedset[]"; 
			$multiple = "multiple";
		}
		
		$usedata = $this->fetch_set_as_array($setdata_array);
		$select = "<select name='" . $selectname ."' id='" . $dom_id . "' " . $multiple . " size='10'>";
		foreach ($usedata as $key=>$description){
			$select .= "<option value='" . $key . "'>" . $description . "</option>";
		}
		$select .= "</select>";
		return $select;
		/*
		$select = "<select name='" . $selectname ."' id='" . $dom_id . "' " . $multiple . " size='10'>";
				foreach ($setdata_array as $edmodoset){
					//NB ugly delimeter that passes all the way through. urrrghh
					//but it is just to create a viewable name, so no stress if the name gets messed up
					if(empty($edmodoset) || empty($edmodoset->id)){continue;}
					$qdescription = $edmodoset->title;
					$qdescription  .= ' (' . $edmodoset->term_count . ')';
					$qdescription  .= ' Author:' . $edmodoset->created_by;
					$qdescription  .= ' images:' . ($edmodoset->has_images ? 'yes' : 'no') ;
					$select .= "<option value='" . $edmodoset->id . "-"  . preg_replace("/[^A-Za-z0-9]/", "_", $edmodoset->title ).  "'>" . $qdescription . "</option>";
				}
				$select .= "</select>";
		*/
	}
	
	//do a basic search from the edmodo_search_form
	public function do_search($param_searchtext='',$param_searchtype=''){
		if($param_searchtext =='' || $param_searchtype== get_string("searchmysets", "edmodoimport")){
			$endpoint = 'users/@username@/sets';
			$params = null;
			$mysets = $this->request($endpoint,$params);
			if($mysets['success']){
				$mysetsdata = $mysets['data'];
			}
		}else{
			switch ($param_searchtype){	
				case get_string("searchusers", "edmodoimport"):
					$params=array();
					$params['creator']=$param_searchtext;
					$endpoint = 'search/sets';
					break;
				case get_string("searchterms", "edmodoimport"):
					$params=array();
					$params['term']=$param_searchtext;
					$endpoint = 'search/sets';
					break;	
				case get_string("searchtitles", "edmodoimport"):
				default:
					$params=array();
					$params['q']=$param_searchtext;
					$endpoint = 'search/sets';
					break;	
			}
			
			$mysets = $this->request($endpoint,$params);
			if($mysets['success']){
				$mysetsdata = $mysets['data']->sets;
			}
		}
		
		return $mysets;
	
	}


}

class edmodo_search_form_qq extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

     $mform->addElement('text', 'searchtext', get_string('searchtext', 'block_edmodo'),array('size'=>64));
     $mform->setType('searchtext',PARAM_TEXT);
     $mform->addElement('hidden', 'courseid');
     $mform->addElement('hidden', 'caller');
     $mform->setType('courseid',PARAM_INT);
     $mform->setType('caller',PARAM_URL);
     $buttons = array(); 
     $buttons[] =& $mform->createElement('submit', 'searchtype',  get_string("searchmysets", "block_edmodo"));
     $buttons[] =& $mform->createElement('submit', 'searchtype',  get_string("searchtitles", "block_edmodo"));
     $buttons[] =& $mform->createElement('submit', 'searchtype',  get_string("searchusers", "block_edmodo"));
      $mform->addElement('group', 'buttonsgrp', '', $buttons, ' ', false);

    }
	 public function definition_after_data() {
		global $COURSE;
        parent::definition_after_data();
        $courseid = optional_param('courseid', 0, PARAM_INT);
		$caller = optional_param('caller', '', PARAM_URL);

		$mform =& $this->_form;
		if($courseid > 0){
			$el_courseid =& $mform->getElement('courseid');
			$el_courseid->setValue($courseid);
		}
		if(!empty($caller)){
			$el_caller =& $mform->getElement('caller');
			$el_caller->setValue($caller);
		}
	}//end of function
}
