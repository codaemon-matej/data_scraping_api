<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cron
 *
 * @author user
 */
class CronJob extends CI_Model {
    
    
    public function deleteOldRecords() {
        $twoDaysBeforeDate = date('Y-m-d',strtotime("-2 days"));
        $this->db->select('id');
        $this->db->where('date <', $twoDaysBeforeDate);
        $q = $this->db->get('mm_search');        
        $result = $q->result_array();        
        if (!empty($result)) {            
            foreach ($result as $searchRecord) {                
                $search_id = $searchRecord['id'];  
                $this->db->reset_query();
                $this->db->where('id', $search_id);
                $this->db->delete('mm_search');                
                $this->db->where('searchId', $search_id);
                $this->db->delete('mm_searchdata');
            }            
        }
    }
}
