<?php
/**
* This class is used to get all states from database.
* @copyright   Copyright(c) 2018
**/
class State extends CI_Model
{
    
    /**
    * Method used to get all states.
    * @param string $status for active or inactive states
    * @return array
    **/
    public function get_all_state($status = '') {
        $this->db->select('*');
        $this->db->from('mm_states');
        if($status != '') {
            $this->db->where('status =',"$status");
        }
        $result = $this->db->get();
        return $result->result_array();
    }

    /**
    * Method used to get state details like full name, unclaimed  money url.
    * @param string $code state code
    * @return array
    **/
    public function get_state_details($code) {
        $this->db->select('*');
        $this->db->from('mm_states');
                
        $this->db->where('code =',"$code");
        $result = $this->db->get();
        return $result->result_array();
    }
}
