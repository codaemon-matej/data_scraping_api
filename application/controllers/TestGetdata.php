<?php
/**
 * Get data
 * 
 * This class used to fetch unclaimedmoney data and used for api.
 * The return type in json format.
 * @package     Getdata
 * @copyright   Copyright(c) 2018
 * */
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Credentials: TRUE');
header('Access-Control-Allow-Origin: https://www.unclaimedmoneyfinder.org');

class TestGetdata extends CI_Controller {    
    
    function __construct() {
        parent::__construct();        
        $this->load->library("Server", "server");
        $this->load->library('Scrapcaptchaproxyless');
        $this->load->library('Simple_html_dom');
        $this->load->library("Scrape");
        $this->load->model('state');
        $this->load->model('datatable');       
        
        /**
         * Used to validate access token and grant access to methods.
         * Takes access token from request parameter
         */
        //$this->server->require_scope();
    }
    
    /**
    * Method used to fetch unclaimed money data for various state
    * @return json 
    */
    public function index() {
        $fname = $this->input->get_post('fname');
        $mname = $this->input->get_post('mname');
        $lname = $this->input->get_post('lname');
        $state = $this->input->get_post('state');
        $bname = $this->input->get_post('bname');
        $city = $this->input->get_post('city');
        $zip = $this->input->get_post('zip');
        $propertyId = $this->input->get_post('propertyId');
        $refNumber = $this->input->get_post('refNumber');
        $access_token = $this->input->get_post('access_token');  
        $controllerTime = time();
        echo json_encode("controller request time ===> ".$controllerTime)."\n";
        $post_fields = array();
        $post_fields['fname'] = $fname;
        $post_fields['lname'] = $lname;
        $post_fields['state'] = $state;
        $post_fields['bname'] = $bname;
        $post_fields['city'] = $city;
        $post_fields['mname'] = $mname;
        $post_fields['propertyId'] = $propertyId;
        $post_fields['zip'] = $zip;
        $post_fields['refNumber'] = $refNumber;

        if (isset($state) && $state != '') 
        {           
            // check required fields according to state
            switch ($state) 
            {
                case 'CA' :
                case 'CT' :
                case 'DE' : if (isset($propertyId) || isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'WA' : if (isset($bname) || isset($lname) || isset($fname) || isset($refNumber))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'OR' :
                case 'PA' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'GA' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'IL' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'HI' : if ((isset($fname) && isset($lname)) || isset($bname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'NC' : if (isset($fname) && isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'MI' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'AL' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'CO' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'DC' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'ID' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'IN' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'IA' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'LA' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'ME' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'MA' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'SC' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'SD' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'TX' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'UT' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'WY' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'MD' : if (isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'WV' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                case 'ND' : if (isset($bname) || isset($lname))
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
                default : if ((isset($fname) && isset($lname))) 
                        $this->sendrequest($post_fields);
                    else
                        echo json_encode(array("error" => "Missing parameters"));
                    break;
            }
        }
        else 
        {
            echo json_encode(array("error" => "Invalid state information."));
        }
    }   

    /**
    * Sub function used to scrape data based on state.
    * Only handle post request for valid OAuth2 access token.
    * @return json 
    */
    private function sendrequest($search_data) {
        $scraped_html = '';
        if ($search_data) {
            $result = array();  
            // check record found in database in last 24 hours
            $result = $this->datatable->check_search($search_data);

            // if record not found, then scrape data
            if (empty($result)) {
                switch ($search_data['state']) {
                    case 'CA' : 
                        $res_raw = $this->scrape->scrape_california($search_data);
                        $result = $res_raw['arr_state'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'WA' : 
                        $res_raw = $this->scrape->scrape_data_washington($search_data);
                        $result = $res_raw['arr_state'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    /*case 'WY' : 
                        $result = $this->scrape->scrape_data_wyoming($search_data);
                        break;*/
                    case 'PA' : 
                        $res_raw = $this->scrape->scrape_pennsylvania($search_data);
                        $result = $res_raw['arr_state'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'CT' : 
                        $res_raw = $this->scrape->scrape_connecticut($search_data);
                        $result = $res_raw['arr_state'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'GA' : 
                        $res_raw = $this->scrape->scrape_georgia($search_data);                    
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'HI' : 
                        $res_raw = $this->scrape->scrape_hawaii($search_data);
                        $result = $res_raw['arr_state'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'IL' :                                                 
                        $res_raw = $this->scrape->scrapeIllinois($search_data);
                        $result = $res_raw['data'];     
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'NC' :                                                 
                        $res_raw = $this->scrape->scrapeNorthCorolina($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'MI' :                                                 
                        $res_raw = $this->scrape->scrapeMichigan($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'AL' :                                                 
                        $res_raw = $this->scrape->scrapeAlabama($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'CO' :                                                 
                        $res_raw = $this->scrape->scrapeColorado($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'DC' :                                                 
                        $res_raw = $this->scrape->scrapeDistrictColumbia($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'ID' :                                                 
                        $res_raw = $this->scrape->scrapeIdaho($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'IN' :                                                 
                        $res_raw = $this->scrape->scrapeIndiana($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'IA' :                                                 
                        $res_raw = $this->scrape->scrapeIowa($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'LA' :                                                 
                        $res_raw = $this->scrape->scrapeLouisiana($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'ME' :                                                 
                        $res_raw = $this->scrape->scrapeMaine($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'MA' :                                                 
                        $res_raw = $this->scrape->scrapeMassachusetts($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'SC' :                                                 
                        $res_raw = $this->scrape->scrapeSouthCarolina($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'SD' :                                                 
                        $res_raw = $this->scrape->scrapeSouthDakota($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'TX' :                                                 
                        $res_raw = $this->scrape->scrapeTexas($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'UT' :                                                 
                        $res_raw = $this->scrape->scrapeUtah($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'WY' :                                                 
                        $res_raw = $this->scrape->scrapeWyoming($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    case 'MD' :                                                 
                        $res_raw = $this->scrape->scrapeMaryland($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                    /*case 'ND' :                                                 
                        $res_raw = $this->scrape->scrapeNorthDakota($search_data);
                        $result = $res_raw['data'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;*/
                    default : 
                        $res_raw = $this->scrape->scrapeMissingMoneyData($search_data);
                        $result = $res_raw['arr_state'];
                        $scraped_html = $res_raw['scraped_html'];
                        break;
                } 
                if ($result) {
                    $search_id = $this->datatable->save_search($search_data, $result, $scraped_html);
                }
            } else {

                // If previous result found then wait for 3 sec to show loader
                sleep(3);
            }
            if ($result) {
                
                //get state details like full name, unclaimed  money url.
                $state_details = $this->state->get_state_details($search_data['state']);
                if(!empty($state_details)) {
                    $state_name = $state_details[0]['state'];
                    $state_url = $state_details[0]['url'];
                }

                foreach ($result as $key => $row) {
                    $search_id = !empty($result[$key]['searchId']) ? $result[$key]['searchId'] : $search_id;
                    $result[$key]['searchId'] = $search_id;
                    $result[$key]['state_name'] = isset($state_name) ? $state_name : '';
                    $result[$key]['state_url'] = isset($state_url) ? $state_url : '';
                }

                // Bring searched state on top
                $searchState = array();
                $otherState = array();
                foreach ($result as $key => $value) {
                    if ($result[$key]['State'] == $search_data['state']) {
                        $searchState[$key] = $value;
                    } else {
                        $otherState[$key] = $value;
                    }
                }
                $result = array_values($searchState + $otherState);
            }            
            echo json_encode($result);
        } else {
            echo json_encode(array("error" => "Invalid Request"));
        }
        $controllerTimeEnd = time();
        echo json_encode("controller end request time ===> ".$controllerTimeEnd)."\n";
    }    
    
    public function deleteOldRecords() {        
        $result = $this->datatable->deleteOldRecords();      
         
    }
}