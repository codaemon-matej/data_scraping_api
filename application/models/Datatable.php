<?php
/**
* This class is used to get all active states.
* @copyright   Copyright(c) 2018
**/
class Datatable extends CI_Model {

    /**
    * Method used to fetch previous search result, if exist in last 24 hours.
    * If record not found in last 24 hours, then delete previous records if any.
    * @param array $search_data as unclaimedmoney search result
    * @return int $search_id  
    **/

    public function check_search($search_data) {
        $this->db->reset_query();
        $this->db->select('id');
        $this->db->where('fname', $search_data['fname']);
        $this->db->where('lname', $search_data['lname']);        
        $this->db->where('state', $search_data['state']);
        $this->db->order_by("id", "desc");
        $this->db->limit('1');
        $q = $this->db->get('mm_search');        
        $result = $q->result_array();        
        if (!empty($result)) {
            $search_id = $result[0]['id'];  
            $this->db->reset_query();
            $this->db->select('*');
            $this->db->where('searchId', $search_id);
            $q = $this->db->get('mm_searchdata');                            
            return $q->result_array();            
        }
    }

    public function check_search_old($search_data) {
        $this->db->reset_query();
        $this->db->select('id,date');
        $this->db->where('fname', $search_data['fname']);
        $this->db->where('lname', $search_data['lname']);        
        $this->db->where('state', $search_data['state']);
        $this->db->order_by("id", "desc");
        $this->db->limit('1');
        $q = $this->db->get('mm_search');        
        $result = $q->result_array();        
        if (!empty($result)) {
            $search_id = $result[0]['id'];  
            $then = $result[0]['date'];
            //Convert it into a timestamp.
            $then = strtotime($then);

            //Get the current timestamp.
            $now = time();

            //Calculate the difference.
            $difference = $now - $then;

            //Convert seconds into days.
            $days = floor($difference / (60 * 60 * 24));

            if ($days < 2) {
                $this->db->reset_query();
                $this->db->select('*');
                $this->db->where('searchId', $search_id);
                $q = $this->db->get('mm_searchdata');                            
                return $q->result_array();
            } else {
                $this->db->reset_query();
                $this->db->where('id', $search_id);
                $this->db->delete('mm_search');                
                $this->db->where('searchId', $search_id);
                $this->db->delete('mm_searchdata');
                return "";
            }
        }
    }

    /**
    * Method used to save scrape data.
    * @param array $search_data as unclaimedmoney search result
    * @param array $search_result
    * @param string $scraped_html as html string for access report
    * @return int $search_id  
    **/
    public function save_search($search_data, $search_result = '', $scraped_html = '') {
        $data['fname'] = $search_data['fname'];
        $data['lname'] = $search_data['lname'];
        $data['bname'] = $search_data['bname'];
        $data['state'] = $search_data['state'];
        $data['scraped_html'] = $scraped_html;
        $this->db->reset_query();
        $this->db->insert('mm_search', $data);

        $search_id = $this->db->insert_id();
        $this->db->reset_query();
        if (!empty($search_result)) {
            foreach ($search_result as $key => $value) {
                $search_result[$key]['searchId'] = $search_id;
            }
            $this->db->insert_batch('mm_searchdata', $search_result);            
        }        
        return $search_id;
    }

    /**
    * Method used to fetch access report for perticular search id from database.
    * @param int $search_id
    * @return array 
    **/
    public function get_accessreport($search_id) {
        $this->db->reset_query();
        $this->db->select('t1.fname,t1.lname,t1.bname,t1.state,t1.scraped_html,t2.url');
        $this->db->from('mm_search as t1');        
        $this->db->where('t1.id', $search_id);        
        $this->db->join('mm_states as t2', 't1.state = t2.code', 'LEFT');
        $q = $this->db->get();        
        $result = $q->result_array();        
        return $result;
    }
    
    
    
}
