<?php
/**
 * Iframe
 * 
 * This class used to fetch unclaimedmoney data and used for iframe.
 * The return type in datatable format.
 * @copyright   Copyright(c) 2018
 * */
defined('BASEPATH') OR exit('No direct script access allowed');

class Iframe extends CI_Controller {

    private $username;
    private $password; 
    private $client_id;
    private $client_secret; 
    function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('state');
        $this->username = $this->config->item('username');
        $this->password = $this->config->item('password');
        $this->client_id = $this->config->item('client_id');
        $this->client_secret = $this->config->item('client_secret');
    }

    /**
    * Function used to fetch unclaimed money data for various state and show result in datatable.
    * It takes first name, last name and state from url parameter
    */
    function index() {

        if($this->session->tempdata('access_token')) {
            $access_token = $this->session->tempdata('access_token');
        }
        else {
            $access_token = $this->get_access_token();
        }

        $data['access_token'] = $access_token;
        $states = $this->state->get_all_state();       
        $data['states'] = $states;
        $this->layout->setLayout("layout/index");
        $this->layout->view('iframe', $data);
    }


    /**
    * Function used for ajax call to scrape data and show in datatable.
    * It takes first name, last name and state from ajax request.
    * @return json
    */
    public function get_apidata() {
        if ($this->input->post()) {
            $first_name = $this->input->post('fname');
            $last_name = $this->input->post('lname');
            $state = $this->input->post('state');
            if($this->session->tempdata('access_token'))
                $access_token = $this->session->tempdata('access_token');
            else
                $access_token = $this->get_access_token();

            $url = base_url().'getdata';

            $username = $this->username;
            $password = $this->password;

            $fields = array(
                'fname' => $first_name,
                'lname' => $last_name,
                'state' => $state,
                'access_token' => $access_token
            );
            
            //url-ify the data for the POST
            $fields_string = '';
            foreach($fields as $key=>$value) {
                $fields_string .= $key.'='.$value.'&'; 
            }
            $fields_string = rtrim($fields_string,'&');

            $options = array(
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER         => false,    // don't return headers
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_ENCODING       => "",       // handle all encodings
                CURLOPT_USERAGENT      => "spider", // who am i
                CURLOPT_AUTOREFERER    => true,     // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
                CURLOPT_TIMEOUT        => 120,      // timeout on response
                CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
                CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
                CURLOPT_USERPWD        => "$username:$password",
                CURLOPT_POSTFIELDS => $fields_string     // Post
            );

            $ch      = curl_init( $url );
            curl_setopt_array( $ch, $options );
            $content = curl_exec( $ch );

            curl_close( $ch );
            echo $content;die();
        }
    }

    /**
    * Function used to get Oauth access token from api.
    * And set access token in session for api call.
    * @return string
    */
    public function get_access_token() {
        $username = $this->username;
        $password = $this->password;
        $grant_type = 'client_credentials';
        $scope = 'userinfo cloud file node';

        $params = array(
            'grant_type' => $grant_type,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => $scope
        );

        $_url =base_url().'clientcredentials/sendrequest';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $output = curl_exec($ch);

        curl_close($ch);

        $output = json_decode($output);

        $this->session->set_tempdata('access_token', $output->access_token, 3300);

        return $output->access_token;
    }
}