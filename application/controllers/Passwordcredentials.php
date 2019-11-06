<?php
/**
* This class is used to generate access token using password credential and used for api.
*
* @package     oauth2
* @copyright   Copyright(c) 2018
**/
defined('BASEPATH') OR exit('No direct script access allowed');
class Passwordcredentials extends CI_Controller {
    function __construct() {
        parent::__construct();
        $this->load->library("Server", "server");
    }    
    
    /**
    * Method used to generate access token.
    * It takes client_id and client_secret, and return access_token if valid.
    * @return json 
    */
    function index() {
        $grant_type = 'password';
        $client_id = 'testclient';
        $client_secret = 'testpass';
        
        $username = $this->input->get('username');
        $password = $this->input->get('password');
        
        $params = array(
            'grant_type' => $grant_type,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'username' => $username,
            'password' => $password
        );
        
        $_url = base_url().'passwordcredentials/sendrequest';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false); 
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);    

        $output=curl_exec($ch);
        curl_close($ch);
        echo $output;
       
    }
    
    /**
    * Sub function to generate access token from library
    * @return json 
    */
    public function sendrequest() {
        if($this->input->post()) {
            return $this->server->password_credentials();
        }
    }
}