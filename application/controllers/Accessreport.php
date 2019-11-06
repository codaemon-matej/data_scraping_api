<?php
/**
 * Access report
 *
 * This class is used to get access report as html report, stored when scraping
 * If html report not found, it redirects to main website's result page.
 * 
 * @copyright   Copyright(c) 2018
 */
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');

class Accessreport extends CI_Controller {

    function __construct() {
        parent::__construct();        
        $this->load->model('datatable');
        $this->load->library("Server", "server");        
        $this->load->library('user_agent');
        
        /**
         * Used to validate access token and grant access to methods.
         * Takes access token from request parameter
         */
        $this->server->require_scope();
    }

    /**
    * Method used to fetch access report html from database for specific search id.
    * @return json 
    */
    function index() {
        $search_id = $this->input->get_post('search_id');
        if (!empty($search_id)) {
            $data = $this->datatable->get_accessreport($search_id);     
            if($data) {
                //html_entity_decode             
                $data[0]['scraped_html'] = htmlspecialchars($data[0]['scraped_html'], ENT_QUOTES);
                
                if ($data[0]['state'] == 'WY') {
                    if (($data[0]['fname'] != '') && ($data[0]['lname'] != '')) {
                        $name = $data[0]['lname'] . " " . $data[0]['fname'];
                    } elseif ($data[0]['bname'] != '') {
                        $name = $data[0]['bname'];
                    } else {
                        $name = $data[0]['lname'];
                    }

                    $url = urlencode($name);
                    $website_url = 'https://statetreasurer.wyo.gov/UPSearchResult.aspx?searchname='.$url;
                    $data[0]['url'] = $website_url;
                } 
                echo json_encode($data);
            } 
            else {
                echo json_encode(array("error" => "Invalid Request"));
            }
        } 
        else {
            echo json_encode(array("error" => "Invalid Request"));
        }
    }
}
