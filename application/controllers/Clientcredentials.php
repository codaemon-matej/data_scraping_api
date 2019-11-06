<?php
/**
* This class is used to generate access token using client credential and used for api.
*
* @package     oauth2
* @copyright   Copyright(c) 2018
**/
defined('BASEPATH') OR exit('No direct script access allowed');
class Clientcredentials extends CI_Controller {
    function __construct(){
        parent::__construct();
        $this->load->library("Server", "server");
        $this->username = $this->config->item('username');
        $this->password = $this->config->item('password');
    }    
    
    /**
    * Method used to generate access token.
    * It takes client_id and client_secret, and return access_token if valid.
    * @return json 
    */
    function index(){
        $username = $this->username;
        $password = $this->password;;
        $grant_type = 'client_credentials';
        $scope = 'userinfo cloud file node';
        
        $client_id = $this->input->get('client_id');
        $client_secret = $this->input->get('client_secret');
        
        $params = array(
            'grant_type' => $grant_type,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => $scope
        );
        
        $_url = base_url().'clientcredentials/sendrequest';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
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
        $this->server->client_credentials();
    }
}
