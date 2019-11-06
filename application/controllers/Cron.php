<?php
/**
 * Cron data
 * 
 * This class used to delete old records.
 * The return type in json format.
 * @package     Cron
 * @copyright   Copyright(c) 2019
 * 
**/
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller{    
    
    function __construct() {
        parent::__construct();
        $this->load->model('cronJob');     
    }    
    
    public function deleteOldRecords() {            	
        $result = $this->cronJob->deleteOldRecords();        
    }
}