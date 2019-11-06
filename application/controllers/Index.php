<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('state');
        $this->load->model('datatable');
        $this->load->library("Server", "server");
        $this->load->library('getlocation');

        $this->username = $this->config->item('username');
        $this->password = $this->config->item('password');
    }

    function index() {

        // echo'<pre>';print_r($ip_data);echo'</pre>';
        $states = $this->state->get_all_state($status = 1);
        $this->layout->setLayout("layout/index");

        $data['states'] = $states;

        $ip_data = $this->getlocation->get_location();

        if ($ip_data[geoplugin_countryCode] == 'US') {
            $data['state'] = $ip_data['geoplugin_regionCode'];
            $data['state_name'] = $ip_data['geoplugin_regionName'];
        }
//         echo'<pre>';
// print_r($data);die;

        $this->layout->view('datatable', $data);
    }

    function search_user() {
        $states = $this->state->get_all_state($status = 1);
        $data['states'] = $states;



        if ($this->input->post()) {
            $first_name = $this->input->post('fname');
            $last_name = $this->input->post('lname');
            $state = $this->input->post('state');
            if ($first_name != '' && $last_name != '' && $state != '') {
                $data['fname'] = $first_name;
                $data['lname'] = $last_name;
                $data['state'] = $state;
                foreach ($states as $dataState) {
                    if ($dataState['code'] == $state) {
                        $data['state_name'] = $dataState['state'];
                    }
                }
            }
        } else {
            $ip_data = $this->getlocation->get_location();

            $data['state'] = $ip_data['geoplugin_regionCode'];
            $data['state_name'] = $ip_data['geoplugin_regionName'];
        }
        $username = $this->username;
        $password = $this->password;
        $grant_type = 'client_credentials';
        $scope = 'userinfo cloud file node';

        $client_id = 'testclient';
        $client_secret = 'testpass';

        $params = array(
            'grant_type' => $grant_type,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => $scope
        );

        $_url = base_url() . 'clientcredentials/sendrequest';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        echo $output = curl_exec($ch);

        curl_close($ch);
        $this->load->library('../controllers/getdata');
        $this->getdata->index();
        
//        $user_data = base_url();
//        'getdata/index';
//        $output = json_decode($output);
//
//        $this->layout->setLayout("layout/index");
//
//        $data['access_token'] = $output->access_token;
//        $this->layout->view('search', $data);
    }

}
