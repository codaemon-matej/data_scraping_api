<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

include("Anticaptcha.php");
//include("NoCaptchaProxyless.php");

class Nocaptchaproxyless extends Anticaptcha implements AntiCaptchaTaskProtocol {

    private $websiteUrl;
    private $websiteKey;
    private $websiteSToken;
    private $image;
    
    public function getPostData() {
        return array(
            "type"          =>  "NoCaptchaTaskProxyless",
            "websiteURL"    =>  $this->websiteUrl,
            "websiteKey"    =>  $this->websiteKey,
            "websiteSToken" =>  $this->websiteSToken,
        );
    }
    public function getImagePostData() {
        return array(
            "type"          =>  "ImageToTextTask",
            "body"    =>  $this->image,
        );
    }
    
    public function getTaskSolution() {
        return $this->taskInfo->solution->gRecaptchaResponse;
    }

    public function getTaskImageSolution() {
        return $this->taskInfo->solution->text;
    }
    
    public function setWebsiteURL($value) {
        $this->websiteUrl = $value;
    }
    
    public function setWebsiteKey($value) {
        $this->websiteKey = $value;
    }
    
    public function setWebsiteSToken($value) {
        $this->websiteSToken = $value;
    }

    public function setWebsiteSImage($value) {
        //$png_file = str_replace(".bmp", ".png", $value);
        //$bmp_file = image_create_from_bmp($value);
        //imagepng($bmp_file,$png_file);
        $imagedata = file_get_contents($value);
        $this->image = base64_encode($imagedata );
    }
    
}
