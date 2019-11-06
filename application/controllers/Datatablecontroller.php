<?php
/**
 * Datatable Controller
 * 
 * This class used to fetch unclaimedmoney data and used for web.
 * @package   Datatable
 * @copyright Copyright(c) 2018
 * */
class Datatablecontroller extends CI_Controller {      

    public function __construct() {
        parent::__construct();
        $this->load->model('state');
        $this->load->model('datatable');
        $this->load->library('Scrapcaptchaproxyless');
        $this->load->library('Simple_html_dom');
        $this->load->library("Scrape");        
    }

    /**
    * Function used to display unclaimed money search form.
    * Search using first name, last name or business name and state. 
    */
    public function index() {
        $states = $this->state->get_all_state();
        $this->layout->setLayout("layout/index");

        $data['states'] = $states;
        $this->layout->view('datatable', $data);
    }

    /**
    * Function used in ajax call to scrape data when search form submit.
    * Scrape using first name, last name or business name and state.
    * @return json 
    */
    public function search_data() {
        $result = array();
        //get posted data
        $search_data = $this->input->post();  
        $result = $this->datatable->check_search($search_data);
        
        if(empty($result)){
            // scrape according to state
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
                case 'WY' : 
                    $result = $this->scrape->scrape_data_wyoming($search_data);
                    break;
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
                case 'DE' : 
                    $result = $this->scrape->scrape_captcha_deleware($search_data);
                    break;
                case 'HI' : 
                    $res_raw = $this->scrape->scrape_hawaii($search_data);
                    $result = $res_raw['arr_state'];
                    $scraped_html = $res_raw['scraped_html'];
                    break;
                case 'IL' : 
                    $res_raw = $this->scrape->scrapeIllinois($search_data);
                    $result = $res_raw['data'];                    
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
                default : 
                    $res_raw = $this->scrape->scrape_data($search_data);
                    $result = $res_raw['arr_state'];
                    $scraped_html = $res_raw['scraped_html'];
                    break;
            }
            if ($result) {
                $search_id = $this->datatable->save_search($search_data, $result, $scraped_html);
            }
        }      
        
        if($result) {
            //get all state with state code and url
            $states = $this->state->get_all_state();
            foreach ($states as $st) {
                $code = $st['code'];
                $state_arr["$code"] = $st['url'];
            }
            
            //append state href url to result array
            foreach ($result as $key => $row) {
                if (isset($row['State']) && $row['State'] != '') {
                    if (array_key_exists($row['State'], $state_arr)) {
                        $result[$key]['url'] = ($state_arr[$row['State']] != '') ? $state_arr[$row['State']] : '';
                    } else {
                        $result[$key]['url'] = '';
                    }
                }
            }        

            // Bring searched state on top
            $searchState = array(); $otherState = array();
            foreach ($result as $key => $value) {            
                if($result[$key]['State'] == $search_data['state']){
                    $searchState[$key] = $value;
                }else{
                    $otherState[$key] = $value;
                }
            }

            $result = array_values($searchState+$otherState);               
        }      

        echo json_encode($result);
    }    
}
