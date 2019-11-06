<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Scrape {
    protected $CI;
    private $proxyauth;
    private $proxy;
    private $proxy_port;
    private $username;
    private $password;

    public function __construct() {
        $this->CI = & get_instance();

        $this->proxyauth = $this->CI->config->item('proxyauth');
        $this->proxy = $this->CI->config->item('proxy');
        $this->proxy_port = $this->CI->config->item('proxyport');
        // get browser uthentication username password
        $this->username = $this->CI->config->item('username');
        $this->password = $this->CI->config->item('password');
    }
    
    /**
    * Sub function used to scrape data from missingmoney.com with pagination.
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_data($search_data) {
        $row = 1;
        $final_data = array();
        $result = array();
        $res_raw = array();
        $keep_requesting = TRUE;
        $scraped_html = "";
        $response = $this->get_search_result($row, $search_data);        
        return $response;        
    }

    /**
    * Sub function used to scrape html from missingmoney.com with pagination
    * $param int $row page number for pagination
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function get_search_result($row, $search_data) { 
        $data_arr = array();
        //https://missingmoney.com/en/Property/Search?searchName=john%20doe&State=IL&page=3           
        $website_url = 'https://missingmoney.com/en/Property/Search';        
        $fields = array(
            'SearchName' => $search_data['fname']." ".$search_data['lname'],            
            'City' => $search_data['city'],
            'State' => $search_data['state']            
        );
        
        //call curl function and get response as html
        $result = $this->CI->scrapcaptchaproxyless->call_curl($website_url, $fields);              
        //load html from string
        $html = $this->CI->simple_html_dom->load($result);        
        $prependcode = '<base href = "'.$website_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
       
        $data_arr['scraped_html'] = "";
        //declare array and variables
        $even_data_arr = array();              
        $i = 0;
        $j = 0;
        $data_arr['status'] = FALSE;
        $data_arr['data'] = array();        
        //get even rows count              
        foreach ($html->find('table.hrs-table tbody tr') as $e) {               
            foreach ($e->find('td') as $input) {                    
                if ($i == 1) {                        
                    $even_data_arr[$j]['Name'] = strip_tags(trim($input->innertext));
                } else if ($i == 2) {
                    $even_data_arr[$j]['State'] = strip_tags(trim($input->innertext));
                } else if ($i == 3) {
                    $even_data_arr[$j]['Location'] = strip_tags(trim($input->innertext));
                } else if ($i == 4) {
                    $even_data_arr[$j]['ReportedBy'] = strip_tags(trim(str_replace("Reported By:", "", $input->innertext)));
                } else if ($i == 5) {
                    $even_data_arr[$j]['Amount'] = strip_tags(trim($input->innertext));
                    $even_data_arr[$j]['PropertyId'] = '';                        
                    $even_data_arr[$j]['CoOwnerName'] = '';
                    $even_data_arr[$j]['ReportingCompany'] = '';                        
                    $even_data_arr[$j]['Shares'] = '';
                    $i = 0;
                    $j++;
                }
                $i++;
            }                
            $i = 0;  
        }  

        $data_arr['status'] = FALSE;        
        $data_arr['data'] = $even_data_arr;
        return $data_arr;
    }

    /**
    * Sub function used to scrape data with captcha for state Oregon
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_hawaii($search_data) {
        //recaptcha key from target website
        $base_url = "https://www.ehawaii.gov/lilo/";
        $website_url = $base_url . 'app';
        $website_key = '6LflvxYTAAAAAFrbZAe6u4CqRN8S_K2YOvIfBDbN';
        //get recaptcha token
        $recaptcha_token = $this->CI->scrapcaptchaproxyless->get_recaptcha_token($website_url, $website_key);
        if ($search_data['bname'] != "") {
            $search_data['fname'] = "";
            $search_data['lname'] = "";
        }
        //set form fields 
        $fields = array(
            'page' => "search",
            'lastName' => $search_data['lname'],
            'firstName' => $search_data['fname'],
            'businessName' => $search_data['bname'],
            'g-recaptcha-response' => "$recaptcha_token",
            'search.search.x' => 32,
            'search.search.y' => 17
        );
                
        //call curl function and get response as html
        $result = $this->CI->scrapcaptchaproxyless->call_curl($website_url, $fields);
        //load html from string
        $html = $this->CI->simple_html_dom->load($result);        
        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        $i = 0;
        $j = 0;
        $data_arr = array();
        $row_count = count($html->find('table tr td font[class!=ietitle]'));
        if ($row_count > 2) {
            foreach ($html->find('table tr td font[class!=ietitle]') as $e) {
                if ($i == 1) {
                    $data_arr[$j]['Name'] = '';
                    $data_arr[$j]['ReportedBy'] = '';
                    foreach ($e->find('input[type=submit]') as $input) {
                        $data_arr[$j]['PropertyId'] = $input->value;
                    }
                } else if ($i == 2) {
                    $temp = explode('<br>', $e->innertext);
                    $data_arr[$j]['Name'] = isset($temp[0]) ? trim($temp[0]) : '';
                    $data_arr[$j]['CoOwnerName'] = isset($temp[1]) ? trim($temp[1]) : '';
                } else if ($i == 3) {
                    $data_arr[$j]['Location'] = trim(preg_replace('#[\s]+#', ' ', strip_tags($e->innertext)));
                    $state = explode(" ", $data_arr[$j]['Location']);
                    $data_arr[$j]['State'] = $state[count($state) - 2];
                } else if ($i == 4) {
                    $data_arr[$j]['ReportingCompany'] = trim($e->innertext);
                } else if ($i == 5) {
                    $data_arr[$j]['Amount'] = $e->innertext;
                } else if ($i == 6) {
                    $data_arr[$j]['Shares'] = $e->innertext;
                    $i = 0;
                    $j++;
                }
                $i++;
            }
        }
        $res_raw['arr_state'] = $data_arr;
        $res_raw['scraped_html'] = $html;
        return $res_raw;
    }

    /**
    * Sub function used to scrape data with pagination for state Oregon
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_oregonup_data($search_data) {
        $row = 1;
        $final_data = array();
        $keep_requesting = TRUE;

        while ($keep_requesting) {
            $response = $this->get_scrape_oregonup_data($row, $search_data);
            if ($response['status'] == FALSE) {
                $keep_requesting = FALSE;
            } else {
                if (count($response['data'] > 0)) {
                    $final_data[] = $response['data'];
                    break;
                } else {
                    break;
                }
            }
        }

        //merge all sub arrays into one
        $result = array();
        foreach ($final_data as $arr) {
            $result = array_merge($result, $arr);
        }

        return $result;
    }

    public function get_image_captcha() {
        $ch = file_get_contents("https://oregonup.us/upweb/up/captcha.asp");
        file_put_contents(FCPATH . "captcha.bmp", $ch);
        $filename = FCPATH . 'captcha.bmp';
        $info = @getimagesize($filename);
        if (($info['mime'] === "image/x-ms-bmp")) {
            $bmp = $this->CI->scrapcaptchaproxyless->image_create_from_bmp($filename);
            imagejpeg($bmp, FCPATH . 'recaptcha.jpg');
        }
    }

    /**
    * Sub function used to scrape html with pagination for state Oregon
    * $param int $row page number for pagination
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function get_scrape_oregonup_data($row, $search_data) {
        $website_url = 'https://oregonup.us/upweb/up/UP_Search.asp';
        $this->get_image_captcha();
        //recaptcha key from target website
        $image_url = base_url() . 'recaptcha.jpg';

        //get recaptcha token
        $captcha_token = $this->CI->scrapcaptchaproxyless->get_imagecaptcha_token($image_url);
        $authLog = "captchaToken = " . $captcha_token;
        if ($search_data['bname'] != "") {
            $search_data['fname'] = "";
            $search_data['lname'] = $search_data['bname'];
        }

        //set form fields 
        $fields = array(
            'LastName' => $search_data['lname'],
            'FirstName' => $search_data['fname'],
            'captchacode' => "$captcha_token",
            'Submit' => "Search+Properties"
        );
        $authLog .= "<br> fields = " . print_r($fields, TRUE);
        write_file(FCPATH . 'log.txt', $authLog, "a+");
        //call curl function and get response as html        
        $result = $this->CI->scrapcaptchaproxyless->call_curl($website_url, $fields);
        $authLog .= "<br> result = " . $result;
        write_file(FCPATH . 'log.txt', $authLog, "a+");

        //load html from string
        $html = $this->CI->simple_html_dom->load($result);
        $i = 0;
        $j = 0;
        $data_arr = array();
        $final_data_arr['status'] = FALSE;
        $final_data_arr['data'] = array();
        $row_count = count($html->find('table tr td font[class!=ietitle]'));
        if ($row_count > 2) {
            foreach ($html->find('table tr td font[class!=ietitle]') as $e) {
                if ($i == 1) {
                    $data_arr[$j]['Name'] = '';
                    $data_arr[$j]['ReportedBy'] = '';
                    foreach ($e->find('input[type=submit]') as $input) {
                        $data_arr[$j]['PropertyId'] = $input->value;
                    }
                } else if ($i == 2) {
                    $temp = explode('<br>', $e->innertext);
                    $data_arr[$j]['Name'] = isset($temp[0]) ? trim($temp[0]) : '';
                    $data_arr[$j]['CoOwnerName'] = isset($temp[1]) ? trim($temp[1]) : '';
                } else if ($i == 3) {
                    $data_arr[$j]['Location'] = trim(preg_replace('#[\s]+#', ' ', strip_tags($e->innertext)));
                    $state = explode(" ", $data_arr[$j]['Location']);
                    $data_arr[$j]['State'] = $state[count($state) - 2];
                } else if ($i == 4) {
                    $data_arr[$j]['ReportingCompany'] = trim($e->innertext);
                } else if ($i == 5) {
                    $data_arr[$j]['Amount'] = $e->innertext;
                } else if ($i == 6) {
                    $data_arr[$j]['Shares'] = $e->innertext;
                    $i = 0;
                    $j++;
                }
                $i++;
                $final_data_arr['status'] = TRUE;
            }
        }
        $final_data_arr['data'] = $data_arr;
        return $final_data_arr;
    }

    /**
    * Sub function used to scrape data with pagination for state Pennsylvania
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_pennsylvania($search_data) {
        $row = 1;
        $final_data = array();
        $keep_requesting = TRUE;

        while ($keep_requesting) {
            $response = $this->extract_data_pennsylvania($row, $search_data);

            if ($response['status'] == FALSE) {
                $keep_requesting = FALSE;
            } else {
                if (count($response['data'] > 0)) {
                    $final_data[] = $response['data'];
                    break;
                } else {
                    break;
                }
            }
        }

        //merge all sub arrays into one
        $result = array();
        foreach ($final_data as $arr) {
            $result = array_merge($result, $arr);
        }
        $res_raw['arr_state'] = $result;
        $res_raw['scraped_html'] = $response['scraped_html'];
        return $res_raw;
    }

    /**
    * Sub function used to scrape html with pagination for state Pennsylvania
    * $param int $row page number for pagination
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function extract_data_pennsylvania($row, $search_data) {
        $base_url = "https://patreasury.gov/";
        $website_url = $base_url . 'Unclaimed/SearchResults.asp';
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $mname = isset($search_data['mname']) ? $search_data['mname'] : "";
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        $fields = array(
            'LastName_VC' => $name, //"Company or Last Name"
            'FirstName_VC' => isset($search_data['fname']) ? $search_data['fname'] : "",
            'MiddleName_VC' => $mname,
            'City_VC' => $city,
            'ZipCode_VC' => $zip,
            'State_VC' => ""
        );

        //call curl function and get response as html         
        $fileContent = $this->CI->scrapcaptchaproxyless->call_curl($website_url, $fields);
        $html = $this->CI->simple_html_dom->load($fileContent);
        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;       
        $even_data_arr = array();
        $odd_data_arr = array();
        $data_arr = array();
        $i = 0;
        $j = 0;
        $final_data_arr['status'] = FALSE;
        $final_data_arr['data'] = array();

        foreach ($html->find('table.table-responsive tr[bgcolor!=#002469]') as $e) {
            foreach ($e->find('td') as $input) {
                if ($i == 0) {
                    $i++;
                } else if ($i == 1) {
                    $lastname = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                    $i++;
                } else if ($i == 2) {
                    $firstname = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                    $data_arr[$j]['Name'] = $lastname . " " . $firstname;
                    $i++;
                } else if ($i == 3) {
                    $mi = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                    $data_arr[$j]['Name'] = $lastname . " " . $mi . " " . $firstname;
                    $i++;
                } else if ($i == 4) {
                    $city = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                    $i++;
                } else if ($i == 5) {
                    $data_arr[$j]['State'] = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                    $i++;
                } else if ($i == 6) {
                    $zip = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                    $data_arr[$j]['Location'] = $city . " " . $zip;
                    $i++;
                } else if ($i == 7) {
                    $data_arr[$j]['ReportingCompany'] = str_replace("&nbsp;", "", strip_tags(trim($input->innertext))); // Holder
                    $i++;
                } else if ($i == 8) {
                    $data_arr[$j]['Amount'] = strip_tags(trim(str_replace("<nobr>", "", $input->innertext)));
                    $data_arr[$j]['ReportedBy'] = '';
                    $data_arr[$j]['PropertyId'] = '';
                    $data_arr[$j]['CoOwnerName'] = '';
                    $data_arr[$j]['Shares'] = '';
                    $i = 0;
                    $j++;
                }
                $final_data_arr['status'] = TRUE;
            }
        }
        $final_data_arr['data'] = $data_arr;
        $final_data_arr['scraped_html'] = $html;
        return $final_data_arr;
    }

    /**
    * Sub function used to scrape data with pagination for state Georgia
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_georgia($search_data) {
        $row = 1;
        $final_data = array();
        $result = array();
        $res_raw = array();
        $keep_requesting = TRUE;
        $scraped_html = "";
        while ($keep_requesting) 
        {
            $response = $this->extract_data_georgia($row, $search_data);
            $scraped_html .= $response['scraped_html']."thisneedtobreak";            
            if ($response['status'] == FALSE) 
            {
                $keep_requesting = FALSE;
            }
            else
            {
                if(count($response['data'] > 0))
                {   
                    $final_data[] = $response['data'];                    
                    $row++;
                }
                else
                {
                    break;
                }
            }
        }        
        //merge all sub arrays into one       
        foreach($final_data as $arr) {
            $result = array_merge($result, $arr);
        }   
        $res_raw['data'] = $result;
        $scraped_html = explode('thisneedtobreak', $scraped_html);
        if(!empty($scraped_html))
            $scraped_html = $scraped_html[0];
        $res_raw['scraped_html'] = $scraped_html;
        return $res_raw;
    }

    /**
    * Sub function used to scrape html with pagination for state Georgia
    * $param int $row page number for pagination
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function extract_data_georgia($row, $search_data) {
        $website_url = 'https://gaclaims.unclaimedproperty.com/en/Property/SearchIndex';
        $post_website_url = 'https://gaclaims.unclaimedproperty.com/en/Property/SearchIndex';
        $website_key = '6Lfldx0TAAAAADWOGNUBVxBpsGcELIH3AoiEnWxY';
        $base_url = 'https://gaclaims.unclaimedproperty.com';
        
        $last_name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $first_name = isset($search_data['fname']) ? $search_data['fname'] : '';
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        
        $fields = array(
            'page' => $row,
            'lastName' => $last_name,
            'firstName' => $first_name,
            'city' => $city,
            'searchType' => 'Person',
            'propertyId' => 0
        );
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        $fields_string = rtrim($fields_string, '&');
        $get_website_url = $post_website_url."?".$fields_string;
        //call curl function and get response as html
        $cookieFile = "";
        // the path to a file we can read/write; this will
        // store cookies we need for accessing secured pages
        $cookies = 'someReadableWritableFileLocation\cookie.txt';
        //open connection
        $ch = curl_init();
        $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
        $header[] = "Content-Type: application/x-www-form-urlencoded";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 3000";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: "; // browsers keep this blank. 
        $header[] = "Accept-Encoding: gzip, deflate, br";
        $header[] = "Host: gaclaims.unclaimedproperty.com";
        
        //proxy suport
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);        
        curl_setopt($ch, CURLOPT_URL, $get_website_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_REFERER, $website_url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);

        //execute post
        $fileContent = curl_exec($ch);
        //close connection
        curl_close($ch);
        $html = $this->CI->simple_html_dom->load($fileContent);
        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        
        if($html->find('div[id=RecaptchaDiv]',0)) {
            $html->find('div[id=RecaptchaDiv]',0)->outertext = "";
        }
        if($html->find('div[id=dialog-missing-captcha]',0)) {
            $html->find('div[id=dialog-missing-captcha]',0)->outertext = "";
        }
        
        foreach ($html->find('strong') as $str) {
            if (strpos($str->innertext, 'Check the reCAPTCHA') !== FALSE) {
                $str->outertext = "";
            }
        }
        
        $data_arr = array();
        $final_data_arr['status'] = FALSE;
        $final_data_arr['data'] = array();
        $i = 0;
        $j = 0;
        $k = 0;
        foreach ($html->find('table[id=searchTable] tbody tr') as $e) {
            if ($j == 18)
                break;

            if ($k == 0) {
                foreach ($e->find('td') as $input) {
                    if ($i == 0) {
                        $i++;
                    } else if ($i == 1) {
                        $data_arr[$j]['PropertyId'] = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                        $i++;
                    } else if ($i == 2) {
                        $data_arr[$j]['Name'] = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                        $i++;
                    } else if ($i == 3) {
                        $data_arr[$j]['Location'] = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                        $i++;
                    } else if ($i == 4) {
                        $data_arr[$j]['State'] = str_replace("&nbsp;", "", strip_tags(trim($input->innertext)));
                        $data_arr[$j]['Amount'] = "";
                        $data_arr[$j]['ReportingCompany'] = '';
                        $data_arr[$j]['ReportedBy'] = '';
                        $data_arr[$j]['CoOwnerName'] = '';
                        $data_arr[$j]['Shares'] = '';
                        $i = 0;
                        $j++;
                        $k++;
                        break;
                    }
                    $final_data_arr['status'] = TRUE;
            } 
            }
            else if ($k == 1){
            $k++;               
            }
            else if ($k == 2){
                $k = 0;
            }
        }
        $final_data_arr['data'] = $data_arr;
        $final_data_arr['scraped_html'] = $html;
        return $final_data_arr;       
    }

    /*     * **********************************************
     * utility function: regex_extract
     *    use the given regular expression to extract
     *    a value from the given text;  $regs will
     *    be set to an array of all group values
     *    (assuming a match) and the nthValue item
     *    from the array is returned as a string
     * ********************************************** */

    public function regex_extract($text, $regex, $regs, $nthValue) {
        if (preg_match($regex, $text, $regs)) {
            $result = $regs[$nthValue];
        } else {
            $result = "";
        }
        return $result;
    }

    /**
    * Sub function used to scrape html with pagination for state Connecticut
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_connecticut($search_data) {
        $row = 1;
        $final_data = array();
        $keep_requesting = TRUE;

        while ($keep_requesting) {
            $response = $this->extract_connecticut($row, $search_data);
            if ($response['status'] == FALSE) {
                $keep_requesting = FALSE;
            } else {
                if (count($response['data'] > 0)) {
                    $final_data[] = $response['data'];
                    break;
                } else {
                    break;
                }
            }
        }

        //merge all sub arrays into one
        $result = array();
        foreach ($final_data as $arr) {
            $result = array_merge($result, $arr);
        }
        $res_raw['arr_state'] = $result;
        $res_raw['scraped_html'] = $response['scraped_html'];

        return $res_raw;
    }

    /**
    * Sub function used to scrape html with pagination for state Connecticut
    * $param int $row page number for pagination
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function extract_connecticut($row, $search_data) {
        $base_url = "https://www.ctbiglist.com/";
        $website_url = $base_url . 'index.asp';
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $fields = array(
            'LastName' => $name, //"Business or Last Name"
            'FirstName' => $search_data['fname'],
            'PropertyID' => $propertyId
        );

        //call curl function and get response as html                
        $fileContent = $this->CI->scrapcaptchaproxyless->call_curl($website_url, $fields);
        $html = $this->CI->simple_html_dom->load($fileContent);
        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        $data_arr = array();
        $final_data_arr['status'] = FALSE;
        $final_data_arr['data'] = array();
        $i = 0;
        $j = -1;

        foreach ($html->find('table[cellspacing="1"] tbody tr') as $e) {
            $i = 0;
            foreach ($e->find('td.style9') as $input) {
                if ($i == 0) {
                    $data_arr[$j]['PropertyId'] = str_replace("&nbsp;", "", strip_tags(trim($input->plaintext)));
                    $data_arr[$j]['Amount'] = "";
                    $data_arr[$j]['ReportingCompany'] = '';
                    $data_arr[$j]['ReportedBy'] = '';
                    $data_arr[$j]['CoOwnerName'] = '';
                    $data_arr[$j]['Shares'] = '';
                    $data_arr[$j]['Location'] = '';
                } else if ($i == 1) {
                    $data_arr[$j]['Name'] = str_replace("&nbsp;", "", strip_tags(trim($input->plaintext)));
                } else if ($i == 2) {
                    $address = strip_tags(trim($input->plaintext));
                } else if ($i == 3) {
                    $city = strip_tags(trim($input->plaintext));
                } else if ($i == 4) {
                    $data_arr[$j]['State'] = str_replace("&nbsp;", "", strip_tags(trim($input->plaintext)));
                } else if ($i == 5) {
                    $zip = strip_tags(trim($input->plaintext));
                    $data_arr[$j]['Location'] = $address . " " . $city . " " . $zip;
                }
                $i++;
                $final_data_arr['status'] = TRUE;
            }
            $j++;
        }
        $final_data_arr['data'] = $data_arr;
        $final_data_arr['scraped_html'] = $html;
        return $final_data_arr;
    }

    /**
    * Sub function used to scrape data with pagination for state wyoming
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_data_wyoming($search_data) {
        $row = 1;
        $final_data = array();
        $keep_requesting = TRUE;

        while ($keep_requesting) {
            $response = $this->get_search_result_wyoming($row, $search_data);
            if ($response['status'] == FALSE) {
                $keep_requesting = FALSE;
            } else {
                if (count($response['data'] > 0)) {
                    $final_data[] = $response['data'];
                    break;
                } else {
                    break;
                }
            }
        }

        //merge all sub arrays into one
        $result = array();
        foreach ($final_data as $arr) {
            $result = array_merge($result, $arr);
        }
        return $result;
    }

    /**
    * Sub function used to scrape html with pagination for state Wyoming
    * $param int $row page number for pagination
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function get_search_result_wyoming($row, $search_data) {
        if (($search_data['fname'] != '') && ($search_data['lname'] != '')) {
            $name = $search_data['lname'] . " " . $search_data['fname'];
        } elseif ($search_data['bname'] != '') {
            $name = $search_data['bname'];
        } else {
            $name = $search_data['lname'];
        }
        $url = urlencode($name);
        $website_url = 'https://statetreasurer.wyo.gov/UPSearchResult.aspx?searchname=' . $url;
        $fields = "";
        $content = $this->CI->scrapcaptchaproxyless->call_curl($website_url, $fields);

        //load html from string
        $html = $this->CI->simple_html_dom->load($content);
        $even_data_arr = array();
        $i = 1;
        $j = 0;
        $final_data_arr['status'] = FALSE;
        $final_data_arr['data'] = array();
        foreach ($html->find('span#contentMain_spnResults') as $element) {
            foreach ($element->find('li') as $li) {
                $name = trim($li->innertext);
                $ex = explode('Name: ', $name);
                $ex1 = explode('- Property @:', $ex[1]);
                $even_data_arr[$j]['Name'] = $ex1[0];
                $even_data_arr[$j]['PropertyId'] = '';
                $even_data_arr[$j]['State'] = 'WY';
                $even_data_arr[$j]['Location'] = $ex1[1];
                $even_data_arr[$j]['Amount'] = '';
                $even_data_arr[$j]['CoOwnerName'] = '';
                $even_data_arr[$j]['ReportingCompany'] = '';
                $even_data_arr[$j]['ReportedBy'] = '';
                $even_data_arr[$j]['Shares'] = '';
                $i = 0;
                $j++;
            }
            $j++;
        }
        $final_data_arr['status'] = TRUE;
        $final_data_arr['data'] = $even_data_arr;
        return $final_data_arr;
    }

    /**
    * Sub function used to scrape html with pagination for state washington
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_data_washington($search_data) {       
        $final_data = array();
        $keep_requesting = TRUE;

        while ($keep_requesting) {
            $response = $this->get_search_result_washington($search_data);
            if ($response['status'] == FALSE) {
                $keep_requesting = FALSE;
            } else {
                if (count($response['data'] > 0)) {
                    $final_data[] = $response['data'];
                    break;
                } else {
                    break;
                }
            }
        }

        //merge all sub arrays into one
        $result = array();
        foreach ($final_data as $arr) {
            $result = array_merge($result, $arr);
        }
        $res_raw['arr_state'] = $result;
        $res_raw['scraped_html'] = $response['scraped_html'];
        return $res_raw;
    }

    /**
    * Sub function used to scrape html with pagination for state Washington    
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function get_search_result_washington($search_data) {
        $base_url = "http://ucp.dor.wa.gov/";
        $website_url = $base_url . 'Results.aspx';
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fields = array(
            'LastName' => $name, //"Company or Last Name"
            'FirstName' => $search_data['fname'],
            'fp' => 1
        );
        //Search by reference number
        if (isset($search_data['refNumber']) && $search_data['refNumber'] != '') {
            $fields = array(
                'ReferenceNumber' => $search_data['refNumber']
            );
        }
        //url-ify the data for the POST
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $options = array(
            CURLOPT_RETURNTRANSFER => TRUE, // return web page
            CURLOPT_HEADER => FALSE, // don't return headers
            CURLOPT_FOLLOWLOCATION => TRUE, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_USERAGENT => "spider", // who am i
            CURLOPT_AUTOREFERER => TRUE, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT => 120, // timeout on response
            CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => FALSE, // Disabled SSL Cert checks
            CURLOPT_POSTFIELDS => $fields_string     // Post
        );

        $ch = curl_init($website_url);
        //proxy suport
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);
        curl_setopt_array($ch, $options);
        $fileContent = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $fileContent;

        //load html from string
        $html = $this->CI->simple_html_dom->load($fileContent);
        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        $d = '';
        $even_data_arr = array();
        $odd_data_arr = array();
        $data_arr = array();
        $i = 1;
        $j = 0;
        $final_data_arr['status'] = FALSE;
        $final_data_arr['data'] = array();

        foreach ($html->find('table#UCPResults21_dgListResults2') as $element) {
            $x = 0;
            foreach ($element->find('tr') as $tableTD) {
                if ($x > 0) {
                    $d = ltrim($tableTD->innertext);
                    $loc_tag = ltrim($tableTD->plaintext);
                    $imgurl = explode('src="', $d);
                    $imgURL = explode('" ', $imgurl[1]);
                    if ($imgURL[0] != "images/previous-white.png") {
                        $url = 'http://ucp.dor.wa.gov/' . $imgURL[0];
                        $even_data_arr[$j]['Name'] = ' <img src="' . $url . '">';
                        $even_data_arr[$j]['PropertyId'] = '';
                        $even_data_arr[$j]['State'] = 'WA';
                        $even_data_arr[$j]['Location'] = $loc_tag;
                        $even_data_arr[$j]['Amount'] = '';
                        $even_data_arr[$j]['CoOwnerName'] = '';
                        $even_data_arr[$j]['ReportingCompany'] = '';
                        $even_data_arr[$j]['Shares'] = '';
                        $even_data_arr[$j]['ReportedBy'] = '';
                        $i = 0;
                        $j++;
                    }
                }
                $x++;
                $j++;
            }
        }
        $final_data_arr['status'] = TRUE;
        $final_data_arr['data'] = $even_data_arr;
        $final_data_arr['scraped_html'] = $html;
        return $final_data_arr;
    }
    
    /**
    * Sub function used to scrape data with pagination for state Deleware
    * Scrape recursively until pagination.
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrapeDeleware($search_data) {
        $website_url = 'https://unclaimedproperty.delaware.gov/app/claim-search';
        
        $post_website_url = 'https://unclaimedproperty.delaware.gov/SWS/properties';
        $website_key = '6LeaaUwUAAAAAHkmQp7ZHaT-wR9znmIhndeyyAb4';
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$fname." ".$name,
            'city' => $city,
            'searchZipCode' => $zip,
        );

        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    

    /**
    * Sub function used to scrape html data for state california
    * @param array $search_data array contains values first name, last name, business name, state etc.
    * @return array 
    */
    public function scrape_california($search_data) {
        $base_url = 'https://ucpi.sco.ca.gov/ucp/';        
        $website_url = 'https://ucpi.sco.ca.gov/ucp/';
        $website_key = '6LejYT8UAAAAAEEiCWgM0NaW-6F1oXxV3rNNCTOp';
        
        //get recaptcha token
        $captcha_token = $this->CI->scrapcaptchaproxyless->get_recaptcha_token($website_url, $website_key);
        
        $urlLogin = "https://ucpi.sco.ca.gov/ucp/";  

        $valSearchLastName = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];

        $valSearchFirstName = $search_data['fname'];
        $valSearchMiddleName = '';        // the value to submit for the username
        $valSearchCity = "";        // the value to submit for the password
        
        $regs = array(); $cookieFile = ""; 

        $regexViewstate = '/__VIEWSTATE\" value=\"(.*)\"/i';
        $regexEventVal  = '/__EVENTVALIDATION\" value=\"(.*)\"/i';  
        $regexStateGen  = '/__EVENTVALIDATION\" value=\"(.*)\"/i';  
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlLogin);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $data=curl_exec($ch);

        $viewstate = $this->regex_extract($data,$regexViewstate,$regs,1);
        $eventval = $this->regex_extract($data, $regexEventVal,$regs,1);
        $stategen = $this->regex_extract($data, $regexStateGen,$regs,1);

        $postData = '__VIEWSTATE='.rawurlencode($viewstate)
          .'&__EVENTVALIDATION='.rawurlencode($eventval)
          .'&__EVENTARGUMENT='
          .'&__VIEWSTATEGENERATOR='.rawurlencode($stategen)
          .'&__EVENTTARGET='
          .'&ctl00$ContentPlaceHolder1$txtLastName='.$valSearchLastName
          .'&ctl00$ContentPlaceHolder1$txtFirstName='.$valSearchFirstName
          .'&ctl00$ContentPlaceHolder1$txtMiddleInitial='.$valSearchMiddleName
          .'&ctl00$ContentPlaceHolder1$txtIndividualCity='.$valSearchCity
          .'&ctl00$ContentPlaceHolder1$btnSearch=Search' 
          ;
        
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $urlLogin);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);

        $viewstate = $this->regex_extract($response,$regexViewstate,$regs,1);
        $eventval = $this->regex_extract($response, $regexEventVal,$regs,1);
        $stategen = $this->regex_extract($response, $regexStateGen,$regs,1);

        $postData = '__VIEWSTATE='.rawurlencode($viewstate)
          .'&__EVENTVALIDATION='.rawurlencode($eventval)
          .'&__EVENTARGUMENT='
          .'&__VIEWSTATEGENERATOR='.rawurlencode($stategen)
          .'&__EVENTTARGET='
          .'&ctl00$ContentPlaceHolder1$txtLastName='.$valSearchLastName
          .'&ctl00$ContentPlaceHolder1$txtFirstName='.$valSearchFirstName
          .'&ctl00$ContentPlaceHolder1$txtMiddleInitial='.$valSearchMiddleName
          .'&ctl00$ContentPlaceHolder1$txtIndividualCity='.$valSearchCity
          .'&g-recaptcha-response='.$captcha_token 
          .'&ctl00$ContentPlaceHolder1$btnClosePopup=Continue' 
          ;

        
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $urlLogin);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);

        $response2 = curl_exec($ch);
        
        //load html from string
        $html = $this->CI->simple_html_dom->load($response2);

        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;

        $i = 0;
        $j = -1;
        $data_arr = array();
       
        foreach ($html->find('table[id=ctl00_ContentPlaceHolder1_gvResults] tbody tr') as $e) {
            $i = 0;
            foreach ($e->find('td') as $input) {                
                if($j>=25) break;
                if($i==0){                                                         
                    $data_arr[$j]['Name'] = isset($input->innertext) ? trim($input->innertext) : '';
                    $data_arr[$j]['Shares'] = '';
                    $data_arr[$j]['ReportedBy'] = '';
                    $data_arr[$j]['Amount'] = '';                    
                    $data_arr[$j]['Shares'] = '';
                    $data_arr[$j]['ReportingCompany'] = '';
                }else if ($i == 1) {                      
                    $data_arr[$j]['Location'] = isset($input->innertext) ? trim($input->innertext) : '';
                    $data_arr[$j]['CoOwnerName'] = '';
                } else if ($i == 2) {                    
                    if(!empty($input->innertext)){
                        $state = explode(" ", $input->innertext);
                        $data_arr[$j]['State'] = $state[count($state) - 2];
                    }else{
                        $data_arr[$j]['State'] = '';
                    }
                } else if ($i == 3) {
                    foreach ($input->find('a') as $ip) {
                        $data_arr[$j]['PropertyId'] = trim(strstr($ip->href,'='),'=');
                    }
                }
                $i++;                 
            }
            $j++;
        }
        $res_raw['arr_state'] = $data_arr;        
        $res_raw['scraped_html'] = $html;
        return $res_raw;
    }

    /**
    * Sub function used to convert scraped html to array for california state
    * @param string $html html string
    * @param string $propertyId
    * @param string $state state name
    * @return array 
    */
    public function scrape_california_by_propertyid($html, $propertyId, $state) {
        $data_arr = array();
        $final_data_arr['status'] = FALSE;
        $final_data_arr['data'] = array();
        foreach ($html->find('table[id=PropertyDetailsTable] tbody tr') as $e) {

            foreach ($e->find('td') as $input) {
                if ($input->id == 'OwnersNameData') {
                    $data_arr[0]['Name'] = trim(strip_tags($input->innertext));
                }
                if ($input->id == 'ReportedAddressData') {
                    $data_arr[0]['Location'] = trim(strip_tags($input->innertext));
                }
                if ($input->id == 'ctl00_ContentPlaceHolder1_CashReportData') {
                    $data_arr[0]['Amount'] = trim(strip_tags($input->innertext));
                }
                if ($input->id == 'ReportedByData') {
                    $data_arr[0]['ReportedBy'] = trim(strip_tags($input->innertext));
                }
                $data_arr[0]['ReportingCompany'] = '';
                $data_arr[0]['CoOwnerName'] = '';
                $data_arr[0]['Shares'] = '';
                $data_arr[0]['PropertyId'] = $propertyId;
                $data_arr[0]['State'] = $state;
            }
        }
        $final_data_arr['status'] = TRUE;
        $final_data_arr['data'] = $data_arr;
        return $final_data_arr;
    }
     
    public function scrapeIllinois($search_data)
    {
        $url    = 'https://icash.illinoistreasurer.gov/SWS/properties';
        $post_website_url = 'https://icash.illinoistreasurer.gov/SWS/properties';
        $website_url = 'https://icash.illinoistreasurer.gov/app/claim-search';
        $website_key = '6Le1HyEUAAAAAGDRTDgxkrt_LOU8pIMr-574w-E3';

        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );

        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeNorthCorolina($search_data)
    {
        $url    = 'https://unclaimed.nccash.com/SWS/properties';
        $post_website_url = 'https://unclaimed.nccash.com/SWS/properties';
        $website_url = 'https://unclaimed.nccash.com/app/claim-search';
        $website_key = '6LdcRaAUAAAAALJXBUGEWb4GrXHwB9GY4M7g6574';
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,            
        );

        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }    

    public function scrapeNorthCorolina_old($search_data) {        
        $base_url = 'https://nc.unclaimedproperty.com/ucp/en/Property/SearchIndex';        
        $i = 0;
        $j = -1;
        $d = 0;
        $l = 0;
        
        $data_arr = array();
        $mainData = [];
        $context = stream_context_create([
            'http' => [
                'proxy' => $this->CI->proxy,
                'request_fulluri' => true
            ]
        ]);

        $response2 = file_get_contents('https://nc.unclaimedproperty.com/ucp/en/Property/SearchIndex?page=1&searchType=Person&lastName='.$search_data['lname'].'&firstName='.$search_data['fname'].'&propertyId=0', false, $context);
        $html = $this->CI->simple_html_dom->load($response2);
        $prependcode = '<base href = "" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        foreach ($html->find('.pagination li') as $e) {
            $l++;
        }

        for($a=1;$a<$l;$a++)
        {           
            $n = 0;
            $k = 0;
            $url = 'https://nc.unclaimedproperty.com/ucp/en/Property/SearchIndex?page='.$a.'&searchType=Person&lastName='.$search_data['lname'].'&firstName='.$search_data['fname'].'&propertyId=0';
            $response2 = file_get_contents($url, false, $context);
            $html = $this->CI->simple_html_dom->load($response2);
            $prependcode = '<base href = "" /><meta name="robots" content="noindex, nofollow,noarchive" />';
            $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
            foreach ($html->find('table[id=searchTable] tbody tr') as $e) {
                $i = 0;                 
                foreach ($e->find('td') as $input) { 
                    $data_arr[$j][$i] = $input->innertext;
                    $i++;                   
                }
                $j++;                 
            }
            $e = 0;
            $b = 2;
            $c = 0;
             
            foreach ($data_arr as $data){
                if($d >= 300)break;
                $i = 0;
                if($c== $e)
                { 
                    foreach ($data as $input) {                
                        if($i==1)
                        { 
                            $mainData[$d]['PropertyId'] = $input;
                        }
                        else if ($i == 2) 
                        {                      
                            $mainData[$d]['Name'] = $input;
                        }
                        else if ($i == 3) 
                        {                      
                            $mainData[$d]['Location'] = $input;
                        }
                        else if ($i == 4) 
                        {                      
                            $mainData[$d]['Amount'] = $input;
                            $mainData[$d]['State'] = 'NC';
                            $mainData[$d]['CoOwnerName'] = '';
                            $mainData[$d]['ReportingCompany'] = '';                        
                            $mainData[$d]['Shares'] = '';
                        }
                        $i++;
                    }
                    $e = $e + 3;
                    $d++;
                }
                else if($c== $b)
                { 
                    foreach ($data as $input) {                
                        $mainData[$d - 1]['ReportedBy'] = str_replace("Reported By:","",$input);
                    }
                    $b = $b + 3;
                }
                $c++;
            }
            if($d >= 300)break;            
        }
        $res_raw['data'] = $mainData;        
        $res_raw['scraped_html'] = '';
        return $res_raw;
    }
    
    public function scrapeMichigan($search_data)
    {
        $url    = 'https://unclaimedproperty.michigan.gov/SWS/properties';
        $post_website_url = 'https://unclaimedproperty.michigan.gov/SWS/properties';
        $website_url = 'https://unclaimedproperty.michigan.gov/app/claim-search';
        $website_key = '6LfHvGUUAAAAAKzjTe-z6q9jnnSilnvD1G0oTSFE';       
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$fname." ".$name,
            'city' => $city,
            'searchZipCode' => $zip,            
        );

        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeAlabama($search_data)
    {
        $url    = 'https://alabama.findyourunclaimedproperty.com/SWS/properties';
        $post_website_url = 'https://alabama.findyourunclaimedproperty.com/SWS/properties';
        $website_url = 'https://alabama.findyourunclaimedproperty.com/app/claim-search';
        $website_key = '6LdZXlgUAAAAADRC9PsHQsas2-Z5y1ZcgYSY6nv6';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$fname." ".$name,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );

        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    
    public function scrapeColorado($search_data)
    {
        $url    = 'https://colorado.findyourunclaimedproperty.com/SWS/properties';
        $post_website_url = 'https://colorado.findyourunclaimedproperty.com/SWS/properties';
        $website_url = 'https://colorado.findyourunclaimedproperty.com/app/claim-search';
        $website_key = '6LekaEkUAAAAAP1rbqeFo8y7oZQ4iovWP-tW3z6U';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$fname." ".$name,
            'city' => $city,
            'searchZipCode' => $zip,
        );

        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeDistrictColumbia($search_data)
    {
        $url    = 'https://dc.findyourunclaimedproperty.com/SWS/properties';
        $post_website_url = 'https://dc.findyourunclaimedproperty.com/SWS/properties';
        $website_url = 'https://dc.findyourunclaimedproperty.com/app/claim-search';
        $website_key = '6Lf3YUkUAAAAANb0BbMdJJRzRyivsOosOogF1dgY';
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeIdaho($search_data)
    {
        $post_website_url = 'https://yourmoney.idaho.gov/SWS/properties';
        $website_url = 'https://yourmoney.idaho.gov/app/claim-search';
        $website_key = '6LcDpFkUAAAAAKS5hSERgXsZFgfxtYi-bYEctKzk';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeIndiana($search_data)
    {
        $post_website_url = 'https://indianaunclaimed.gov/SWS/properties';
        $website_url = 'https://indianaunclaimed.gov/app/claim-search';
        $website_key = '6LddYVwUAAAAAEoHOSP7TvhSVLlmNmcIEStV-Q39';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeIowa($search_data)
    {
        $post_website_url = 'https://greatiowatreasurehunt.gov/SWS/properties';
        $website_url = 'https://greatiowatreasurehunt.gov/app/claim-search';
        $website_key = '6LevZVwUAAAAADfUtvbLSAF5yvsa7AtBb_NXkO2T';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeLouisiana($search_data)
    {
        $post_website_url = 'https://louisiana.findyourunclaimedproperty.com/SWS/properties';
        $website_url = 'https://louisiana.findyourunclaimedproperty.com/app/claim-search';
        $website_key = '6LdlkFgUAAAAADP6QvwA3V6YOlXReN0s9u57xkWt';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        
        //set form fields 
        $fields = array(
            'lastName' =>$name." ".$fname,
            'city' => $city,
            'searchZipCode' => $zip
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeMaine($search_data)
    {
        $post_website_url = 'https://maineunclaimedproperty.gov/SWS/properties';
        $website_url = 'https://maineunclaimedproperty.gov/app/claim-search';
        $website_key = '6LeyX0kUAAAAAPfcJi1bENlzoXORCbsktpE-pE_9';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeMassachusetts($search_data)
    {
        $post_website_url = 'https://findmassmoney.com/SWS/properties';
        $website_url = 'https://findmassmoney.com/app/claim-search';
        $website_key = '6LezZVwUAAAAAJUglhR0AfbBgbvHrhAORQB1GtQF';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }    

    public function scrapeSouthCarolina($search_data)
    {
        $post_website_url = 'https://southcarolina.findyourunclaimedproperty.com/SWS/properties';
        $website_url = 'https://southcarolina.findyourunclaimedproperty.com/app/claim-search';
        $website_key = '6LepPEAUAAAAAK-daBpxkGW7JLupPTwF_Q1D7vqq';        
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeSouthDakota($search_data)
    {
        $post_website_url = 'https://southdakota.findyourunclaimedproperty.com/SWS/properties';
        $website_url = 'https://southdakota.findyourunclaimedproperty.com/app/claim-search';
        $website_key = '6LfRZFwUAAAAAAo7au29H5Nrdn8WqfPcaXH2wTUG';
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeTexas($search_data)
    {
        $post_website_url = 'https://claimittexas.org/SWS/properties';
        $website_url = 'https://claimittexas.org/app/claim-search';
        $website_key = '6LeQLyEUAAAAAKTwLC-xVC0wGDFIqPg1q3Ofam5M';
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeUtah($search_data)
    {
        $post_website_url = 'https://mycash.utah.gov/SWS/properties';
        $website_url = 'https://mycash.utah.gov/app/claim-search';
        $website_key = '6LflR2kUAAAAAJsy97ypHgWdFQ-wROMNIMaCDMvU';
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }
    
    public function scrapeWyoming($search_data)
    {
        $post_website_url = 'https://wyoming.findyourunclaimedproperty.com/SWS/properties';
        $website_url = 'https://wyoming.findyourunclaimedproperty.com/app/claim-search';
        $website_key = '6LcZnXQUAAAAAFnuVPHKFu5CrgWJ0Hqt2TiOgEoB';
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );
        
        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);
        return $data;
    }    

    private function getData($fields,$post_website_url,$website_url,$website_key) {        
        //get recaptcha token
        $captcha_token = $this->CI->scrapcaptchaproxyless->get_recaptcha_token($website_url, $website_key);
        //call curl function and get response as html
        $cookieFile = "";
        
        //open connection
        $ch = curl_init();

        $header[] = "Accept: application/json, text/plain, */*";
        $header[] = "Content-Type:application/json";        
        $header[] = "X-SWS-Recaptcha-Token:$captcha_token";            

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL,$post_website_url);        
        //proxy suport
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);

        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);  
        curl_setopt($ch,CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($fields));
        curl_setopt($ch,CURLOPT_REFERER,$website_url);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        
        //execute post
        $result = curl_exec($ch);
        //close connection
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        //close connection

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $result;        
        $data_result = json_decode($result,true);               
        
        $data_arr = array();
        $j=0;        
        
        if(isset($data_result['properties'])){
            foreach ($data_result['properties'] as $key => $value) {

                if($j>=300) break;
                $data_arr[$j]['PropertyId']=$value['propertyID'];
                $data_arr[$j]['Name']=$value['ownerName'];
                $data_arr[$j]['State']=($value['state'] != null) ? $value['state'] : '';
                $data_arr[$j]['Location'] = trim($value['address1']." ".$value['address2']." ".$value['city']." ".$value['postalCode']);
                $data_arr[$j]['Amount'] = $value['propertyValueDescription']; 
                $data_arr[$j]['ReportingCompany'] = '';
                $data_arr[$j]['ReportedBy'] = $value['holderName'];                          
                $data_arr[$j]['CoOwnerName'] = '';
                $data_arr[$j]['Shares'] = '';                    
                $j++;
                
            }   
            
        }      
      
        $final_data_arr['data'] = $data_arr;
        $final_data_arr['scraped_html'] = '';
        return $final_data_arr;
    }
    
    public function scrapeMaryland($search_data)
    {
        $base_url = 'https://interactive.marylandtaxes.gov';        
                
        $urlLogin = "https://interactive.marylandtaxes.gov/Individuals/Unclaim/default.aspx";  
        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        
        $regs = array(); $cookieFile = ""; 

        $regexViewstate = '/__VIEWSTATE\" value=\"(.*)\"/i';
        $regexEventTarget  = '/__EVENTTARGET\" value=\"(.*)\"/i';  
        $regexEventArgument  = '/__EVENTARGUMENT\" value=\"(.*)\"/i';  
        $regexStateGenerator  = '/__VIEWSTATEGENERATOR\" value=\"(.*)\"/i';  
        $regexEventValidation  = '/__EVENTVALIDATION\" value=\"(.*)\"/i';  

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlLogin);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $data=curl_exec($ch);

        $viewstate = $this->regex_extract($data,$regexViewstate,$regs,1);
        $eventTarget = $this->regex_extract($data, $regexEventTarget,$regs,1);
        $eventArgument = $this->regex_extract($data, $regexEventArgument,$regs,1);
        $eventval = $this->regex_extract($data, $regexEventValidation,$regs,1);
        $stategen = $this->regex_extract($data, $regexStateGenerator,$regs,1);

        $postData = '__VIEWSTATE='.rawurlencode($viewstate)
          .'&__EVENTVALIDATION='.rawurlencode($eventval)
          .'&__EVENTARGUMENT='.rawurlencode($eventArgument)
          .'&__VIEWSTATEGENERATOR='.rawurlencode($stategen)
          .'&__EVENTTARGET='.rawurlencode($eventTarget)
          .'&txtLName='.$name
          .'&txtFName='.$fname
          .'&btnSearch=Search' 
          ;
        
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $urlLogin);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        
        $viewstate = $this->regex_extract($response,$regexViewstate,$regs,1);
        $eventTarget = $this->regex_extract($response, $regexEventTarget,$regs,1);
        $eventArgument = $this->regex_extract($response, $regexEventArgument,$regs,1);
        $eventval = $this->regex_extract($response, $regexEventValidation,$regs,1);
        $stategen = $this->regex_extract($response, $regexStateGenerator,$regs,1);

        $postData = '__VIEWSTATE='.rawurlencode($viewstate)
          .'&__EVENTVALIDATION='.rawurlencode($eventval)
          .'&__EVENTARGUMENT='.rawurlencode($eventArgument)
          .'&__VIEWSTATEGENERATOR='.rawurlencode($stategen)
          .'&__EVENTTARGET='.rawurlencode($eventTarget)
          .'&txtLName='.$name
          .'&txtFName='.$fname
          .'&btnSearch=Search' 
          ;

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $urlLogin);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);

        $response2 = curl_exec($ch);
        
        //load html from string
        $html = $this->CI->simple_html_dom->load($response2);

        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        
        $j = 0;
        $i = 0;
        $data_arr = array();
        foreach ($html->find('table[id=dgUnclaimedPR] tbody tr') as $e) {
            if($j > 0 && $j <=300){
                $i = 0;                 
                foreach ($e->find('td') as $input) { 
                    if($i==0)
                    { 
                        $data_arr[$j]['PropertyId'] = $input->innertext;
                    }
                    else if ($i == 1) 
                    {                      
                        $data_arr[$j]['Name'] = $input->innertext;
                    }
                    else if ($i == 2) 
                    {   
                        $data_arr[$j]['Name'] = $data_arr[$j]['Name']." ". $input->innertext;

                    }
                    else if ($i == 4) 
                    {                 
                        $data_arr[$j]['Location'] = $input->innertext;
                        $data_arr[$j]['Amount'] = '';
                        $data_arr[$j]['State'] = 'MD';
                        $data_arr[$j]['CoOwnerName'] = '';
                        $data_arr[$j]['ReportingCompany'] = '';                        
                        $data_arr[$j]['Shares'] = '';
                    }
                    $i++;                   
                }
            }
            $j++;                 
        }
        array_pop($data_arr);
        $res_raw['data'] = $data_arr;        
        $res_raw['scraped_html'] = $html;
        return $res_raw;
    }   
    
    public function scrapeOklahoma($search_data)
    {
        $base_url = 'https://apps.ok.gov/unclaimed/search.php';        
        $urlLogin = "https://apps.ok.gov/unclaimed/search.php";
        $website_url = "https://apps.ok.gov/unclaimed/search.php";
        $ch = curl_init();
        $postData = '&city='
          .'&family_business='
          .'&name=DOE'
          .'&first='
          .'&middle='
          .'&x=57'
          .'&y=14'
          ;
        
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $website_url);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);

        $response2 = curl_exec($ch);
        echo $response2;
    }
    
    public function scrapeVermont($search_data)
    {
        $base_url = 'https://secure2.vermonttreasurer.gov/unclaimed/';        
        $urlLogin = "https://secure2.vermonttreasurer.gov/unclaimed/ownerSearch.asp";
        $website_url = "https://secure2.vermonttreasurer.gov/unclaimed/ownerSearch.asp";
        //set form fields 
        $fields = array(
            'txtSearchName' =>'JOHN',
            'radSearchMode' =>'1',
            'txtSearchTown' => '',
            'cmdSearch' => 'Search'
        );
        //call curl function and get response as html
        $cookieFile = "";
        // the path to a file we can read/write; this will
        // store cookies we need for accessing secured pages
        $cookies = 'someReadableWritableFileLocation\cookie.txt';
        //open connection
        $ch = curl_init();

        $header[] = "Accept: application/json, text/plain, */*";
        $header[] = "Content-Type:application/x-www-form-urlencoded";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 3000";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: "; // browsers keep this blank. 
        $header[] = "Accept-Encoding: gzip, deflate, br";
        $header[] = "Host: icash.illinoistreasurer.gov";      
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL,$website_url);
        //proxy suport
        curl_setopt($ch, CURLOPT_PROXY, $this->CI->proxy);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->CI->proxyPort);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->CI->proxyauth);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);  
        curl_setopt($ch,CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
        curl_setopt($ch,CURLOPT_REFERER,$website_url);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);

        $response2 = curl_exec($ch);        
        echo $response2;
    }
    
    public function scrapeWestVirginia($search_data)
    {        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : ""; 
        
        $website_url = "https://apps.wvsto.com/ECLAIMS_FastTrack/property_search.aspx?".$fname."&".$name."&FIRSTNAME=".$fname."&LASTNAME=".$name."&lnkSubmit=Search&OKYFRMPOST=true";
        $cookieFile="";
        
        $postData = 'ctl00%24ContentPlaceHolder1%24RadScriptManager1=ctl00%24ContentPlaceHolder1%24ctl00%24ContentPlaceHolder1%24gv_resultsPanel%7Cctl00%24ContentPlaceHolder1%24gv_results&ContentPlaceHolder1_RadScriptManager1_TSM=%3B%3BSystem.Web.Extensions%2C%20Version%3D4.0.0.0%2C%20Culture%3Dneutral%2C%20PublicKeyToken%3D31bf3856ad364e35%3Aen-US%3A15b60a4c-f81c-45d9-aab7-826923d7ffa6%3Aea597d4b%3Ab25378d2%3BTelerik.Web.UI%2C%20Version%3D2017.2.621.45%2C%20Culture%3Dneutral%2C%20PublicKeyToken%3D121fae78165ba3d4%3Aen-US%3Af8fbf82e-0050-4b46-80b2-4fd09c29f9d6%3A16e4e7cd%3Aed16cbdc%3Af7645509%3A88144a7a%3A33715776%3A58366029%3A24ee1bba%3Af46195d3%3A2003d0b8%3Ac128760b%3A1e771326%3Aaa288e2d%3A258f1c72%3B&TreeView1_ExpandState=enn&TreeView1_SelectedNode=TreeView1n2&TreeView1_PopulateLog=&ctl00%24ContentPlaceHolder1%24txt_lastname='.$name.'&ctl00%24ContentPlaceHolder1%24txt_firstname='.$fname.'&ctl00%24ContentPlaceHolder1%24txt_city='.$city.'&ctl00%24ContentPlaceHolder1%24gv_results%24ctl00%24ctl03%24ctl01%24PageSizeComboBox=500&ctl00_ContentPlaceHolder1_gv_results_ctl00_ctl03_ctl01_PageSizeComboBox_ClientState=%7B%22logEntries%22%3A%5B%5D%2C%22value%22%3A%22%22%2C%22text%22%3A%22500%22%2C%22enabled%22%3Atrue%2C%22checkedIndices%22%3A%5B%5D%2C%22checkedItemsTextOverflows%22%3Afalse%7D&ctl00_ContentPlaceHolder1_gv_results_ClientState=&__EVENTTARGET=ctl00%24ContentPlaceHolder1%24gv_results&__EVENTARGUMENT=FireCommand%3Actl00%24ContentPlaceHolder1%24gv_results%24ctl00%3BPageSize%3B500&__LASTFOCUS=&__VIEWSTATE=%2FwEPDwULLTE3MzYxOTY4NDEPZBYCZg9kFgICAw9kFgYCAQ88KwAJAgAPFggeDU5ldmVyRXhwYW5kZWRkHgtfIURhdGFCb3VuZGceDFNlbGVjdGVkTm9kZQULVHJlZVZpZXcxbjIeCUxhc3RJbmRleAIDZAgUKwACBQMwOjAUKwACFgweDFNlbGVjdEFjdGlvbgsqLlN5c3RlbS5XZWIuVUkuV2ViQ29udHJvbHMuVHJlZU5vZGVTZWxlY3RBY3Rpb24DHghEYXRhUGF0aAUkMDViNTk5ODUtNjc2OC00ZWUwLTk0Y2YtNzczMTU0YTBhMjIyHglEYXRhQm91bmRnHhBQb3B1bGF0ZU9uRGVtYW5kaB4IRXhwYW5kZWRnHglQb3B1bGF0ZWRnFCsAAwUHMDowLDA6MRQrAAIWDB4EVGV4dAUFTG9naW4eBVZhbHVlBQVMb2dpbh4LTmF2aWdhdGVVcmwFHS9FQ0xBSU1TX0Zhc3RUcmFjay9sb2dpbi5hc3B4HwUFHS9lY2xhaW1zX2Zhc3R0cmFjay9sb2dpbi5hc3B4HwZnHwhnZBQrAAIWDh8KBQ9Qcm9wZXJ0eSBTZWFyY2gfCwUPUHJvcGVydHkgU2VhcmNoHwwFJy9FQ0xBSU1TX0Zhc3RUcmFjay9wcm9wZXJ0eV9zZWFyY2guYXNweB8FBScvZWNsYWltc19mYXN0dHJhY2svcHJvcGVydHlfc2VhcmNoLmFzcHgfBmceCFNlbGVjdGVkZx8IZ2RkAgMPFgIeD1NpdGVNYXBQcm92aWRlcgULTm90TG9nZ2VkSW5kAgcPZBYWAgcPDxYGHhVFbmFibGVFbWJlZGRlZFNjcmlwdHNnHhxFbmFibGVFbWJlZGRlZEJhc2VTdHlsZXNoZWV0Zx4XRW5hYmxlQWpheFNraW5SZW5kZXJpbmdoZGQCFQ8QDxYCHgdWaXNpYmxlaGRkFgFmZAIXDw8WAh4RVXNlU3VibWl0QmVoYXZpb3JoZGQCGQ8PFgIfCgWYAzxjZW50ZXI%2BUGFydGlhbCBOYW1lcyBBY2NlcHRlZDwvY2VudGVyPjxicj5JbiBvcmRlciB0byBtYWludGFpbiB0aGUgcHJpdmFjeSBvZiB0aGUgb3duZXJzLCBvbmx5IHRoZSBuYW1lIGFuZCBjaXR5IGFyZSBkaXNwbGF5ZWQgb24gdGhpcyBzZWFyY2guPEJSPjxCUj5TZWFyY2ggYW5kIHNlbGVjdCBwcm9wZXJ0eSB0byBjbGFpbSBlbGVjdHJvbmljYWxseS4gIEJ5IHNlbGVjdGluZyBwcm9wZXJ0eSBhbmQgY3JlYXRpbmcgYW4gYWNjb3VudCwgeW91IGNhbiBzdWJtaXQgeW91ciB1bmNsYWltZWQgcHJvcGVydHkgY2xhaW0gb25saW5lLiAgQnkgc3VibWl0dGluZyBvbmxpbmUsIHlvdSBhZ3JlZSB0byBhbGxvdyBjb21tdW5pY2F0aW9ucyB3aXRoIHlvdSB2aWEgeW91ciBvbmxpbmUgYWNjb3VudCBvciBieSBVUyBtYWlsLmRkAh0PDxYCHxJnZGQCHw8PFgIfEmdkZAIhDzwrAA4CABQrAAIPFg4fEmcfAWcfEWgfEGceC18hSXRlbUNvdW50AgEeElJlc29sdmVkUmVuZGVyTW9kZQspclRlbGVyaWsuV2ViLlVJLlJlbmRlck1vZGUsIFRlbGVyaWsuV2ViLlVJLCBWZXJzaW9uPTIwMTcuMi42MjEuNDUsIEN1bHR1cmU9bmV1dHJhbCwgUHVibGljS2V5VG9rZW49MTIxZmFlNzgxNjViYTNkNAEfD2dkFwQFE1NlbGVjdGVkQ2VsbEluZGV4ZXMWAAUPU2VsZWN0ZWRJbmRleGVzFgAFCFBhZ2VTaXplAmQFC0VkaXRJbmRleGVzFgABFgIWCw8CBhQrAAYUKwAFFgIeBG9pbmQCAmRkZAULcHJvcGVydHlfaWQUKwAFFgQfFgIDHghEYXRhVHlwZRkrAmRkZAUJbGFzdF9uYW1lFCsABRYEHxYCBB8XGSsCZGRkBQpmaXJzdF9uYW1lFCsABRYEHxYCBR8XGSsCZGRkBQRjaXR5FCsABRYEHxYCBh8XGSsCZGRkBQVzdGF0ZRQrAAUWBB8WAgcfFxkrAmRkZAUJZmlybV9uYW1lZGUUKwAACyl5VGVsZXJpay5XZWIuVUkuR3JpZENoaWxkTG9hZE1vZGUsIFRlbGVyaWsuV2ViLlVJLCBWZXJzaW9uPTIwMTcuMi42MjEuNDUsIEN1bHR1cmU9bmV1dHJhbCwgUHVibGljS2V5VG9rZW49MTIxZmFlNzgxNjViYTNkNAE8KwAHAAspdFRlbGVyaWsuV2ViLlVJLkdyaWRFZGl0TW9kZSwgVGVsZXJpay5XZWIuVUksIFZlcnNpb249MjAxNy4yLjYyMS40NSwgQ3VsdHVyZT1uZXV0cmFsLCBQdWJsaWNLZXlUb2tlbj0xMjFmYWU3ODE2NWJhM2Q0ARYCHgRfZWZzFgIeE2NzX3BvcHVwc19DbG9zZVRleHQFBUNsb3NlZBYUHgRfYWNzZx4IRGF0YUtleXMWAB4FX3FlbHQZKWdTeXN0ZW0uRGF0YS5EYXRhUm93VmlldywgU3lzdGVtLkRhdGEsIFZlcnNpb249NC4wLjAuMCwgQ3VsdHVyZT1uZXV0cmFsLCBQdWJsaWNLZXlUb2tlbj1iNzdhNWM1NjE5MzRlMDg5Hg5DdXN0b21QYWdlU2l6ZQJkHwFnHxQCZB4KRGF0YU1lbWJlcmUeBF9obG0LKwYBHgVfIUNJUxcAHhRJc0JvdW5kVG9Gb3J3YXJkT25seWhkZhYGZg8UKwADD2QWAh4Fc3R5bGUFC3dpZHRoOjEwMCU7ZGRkAgEPFgUUKwACDxYUHxpnHxsWAB8cGSsIHx0CZB8BZx8UAmQfHmUfHwsrBgEfIBcAHyFoZBcFBQZfIURTSUMC9QMFCFBhZ2VTaXplAmQFEEN1cnJlbnRQYWdlSW5kZXhmBQhfIVBDb3VudAIGBQtfIUl0ZW1Db3VudAJkFgIeA19zZRYCHgJfY2ZkFgZkZGRkZGQWAmdnFgJmD2QWlANmD2QWBGYPDxYCHxJoZBYCZg8PFgIeCkNvbHVtblNwYW4CBWQWAmYPZBYCAgEPZBYCZg9kFgZmD2QWBGYPDxYCHxNoZGQCAg8PFgIfE2hkZAICD2QWBGYPDxYCHxNoZGQCAg8PFgIfE2hkZAIDDw8WBB4IQ3NzQ2xhc3MFEHJnV3JhcCByZ0FkdlBhcnQeBF8hU0ICAmQWAgIBDxQrAAIPFhgeEUVuYWJsZUFyaWFTdXBwb3J0aB4TRW5hYmxlRW1iZWRkZWRTa2luc2ceGVJlZ2lzdGVyV2l0aFNjcmlwdE1hbmFnZXJnHwFnHh1PbkNsaWVudFNlbGVjdGVkSW5kZXhDaGFuZ2luZwUwVGVsZXJpay5XZWIuVUkuR3JpZC5DaGFuZ2luZ1BhZ2VTaXplQ29tYm9IYW5kbGVyHgxUYWJsZVN1bW1hcnkFG1BhZ2UgU2l6ZSBEcm9wIERvd24gQ29udHJvbB4TY2FjaGVkU2VsZWN0ZWRWYWx1ZWQfEGceCklucHV0VGl0bGVlHhxPbkNsaWVudFNlbGVjdGVkSW5kZXhDaGFuZ2VkBS5UZWxlcmlrLldlYi5VSS5HcmlkLkNoYW5nZVBhZ2VTaXplQ29tYm9IYW5kbGVyHgxUYWJsZUNhcHRpb24FEFBhZ2VTaXplQ29tYm9Cb3gfD2dkDxQrAAUUKwACDxYEHwoFAjEwHw1oFgIeEG93bmVyVGFibGVWaWV3SWQFKmN0bDAwX0NvbnRlbnRQbGFjZUhvbGRlcjFfZ3ZfcmVzdWx0c19jdGwwMGQUKwACDxYEHwoFAjIwHw1oFgIfMQUqY3RsMDBfQ29udGVudFBsYWNlSG9sZGVyMV9ndl9yZXN1bHRzX2N0bDAwZBQrAAIPFgQfCgUCNTAfDWgWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkFCsAAg8WBB8KBQMxMDAfDWcWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkFCsAAg8WBB8KBQM1MDAfDWgWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkDxQrAQVmZmZmZhYBBXdUZWxlcmlrLldlYi5VSS5SYWRDb21ib0JveEl0ZW0sIFRlbGVyaWsuV2ViLlVJLCBWZXJzaW9uPTIwMTcuMi42MjEuNDUsIEN1bHR1cmU9bmV1dHJhbCwgUHVibGljS2V5VG9rZW49MTIxZmFlNzgxNjViYTNkNBYOZg8PFgQfJgUJcmNiSGVhZGVyHycCAmRkAgEPDxYEHyYFCXJjYkZvb3Rlch8nAgJkZAICDw8WBB8KBQIxMB8NaBYCHzEFKmN0bDAwX0NvbnRlbnRQbGFjZUhvbGRlcjFfZ3ZfcmVzdWx0c19jdGwwMGQCAw8PFgQfCgUCMjAfDWgWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkAgQPDxYEHwoFAjUwHw1oFgIfMQUqY3RsMDBfQ29udGVudFBsYWNlSG9sZGVyMV9ndl9yZXN1bHRzX2N0bDAwZAIFDw8WBB8KBQMxMDAfDWcWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkAgYPDxYEHwoFAzUwMB8NaBYCHzEFKmN0bDAwX0NvbnRlbnRQbGFjZUhvbGRlcjFfZ3ZfcmVzdWx0c19jdGwwMGQCAQ9kFhBmDw8WBB8KBQYmbmJzcDsfEmhkZAIBDw8WBB8KBQYmbmJzcDsfEmhkZAICDw8WAh8KBQYmbmJzcDtkZAIDDw8WAh8KBRZMYXN0IE5hbWUvQ29tcGFueSBOYW1lZGQCBA8PFgIfCgUKRmlyc3QgTmFtZWRkAgUPDxYCHwoFBENpdHlkZAIGDw8WAh8KBQVTdGF0ZWRkAgcPDxYCHwoFCWZpcm1fbmFtZWRkAgEPZBYEZg9kFhBmDw8WAh8KBQYmbmJzcDtkZAIBDw8WAh8KBQYmbmJzcDtkZAICDw8WAh8KBQYmbmJzcDtkZAIDDw8WAh8KBQYmbmJzcDtkZAIEDw8WAh8KBQYmbmJzcDtkZAIFDw8WAh8KBQYmbmJzcDtkZAIGDw8WAh8KBQYmbmJzcDtkZAIHDw8WAh8KBQYmbmJzcDtkZAIBD2QWAmYPDxYCHyUCBWQWAmYPZBYCAgEPZBYCZg9kFgZmD2QWBGYPDxYCHxNoZGQCAg8PFgIfE2hkZAICD2QWBGYPDxYCHxNoZGQCAg8PFgIfE2hkZAIDDw8WBB8mBRByZ1dyYXAgcmdBZHZQYXJ0HycCAmQWAgIBDxQrAAIPFiAfEGcfKWceBFNraW4FB0RlZmF1bHQfKmcfKGgfLwUuVGVsZXJpay5XZWIuVUkuR3JpZC5DaGFuZ2VQYWdlU2l6ZUNvbWJvSGFuZGxlch8sBRtQYWdlIFNpemUgRHJvcCBEb3duIENvbnRyb2wfAWcfLmUfEWgfCgUDMTAwHzAFEFBhZ2VTaXplQ29tYm9Cb3gfFQsrBQEfD2cfLWQfKwUwVGVsZXJpay5XZWIuVUkuR3JpZC5DaGFuZ2luZ1BhZ2VTaXplQ29tYm9IYW5kbGVyZA8UKwAFFCsAAg8WBB8KBQIxMB8NaBYCHzEFKmN0bDAwX0NvbnRlbnRQbGFjZUhvbGRlcjFfZ3ZfcmVzdWx0c19jdGwwMGQUKwACDxYEHwoFAjIwHw1oFgIfMQUqY3RsMDBfQ29udGVudFBsYWNlSG9sZGVyMV9ndl9yZXN1bHRzX2N0bDAwZBQrAAIPFgQfCgUCNTAfDWgWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkFCsAAg8WBB8KBQMxMDAfDWcWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkFCsAAg8WBB8KBQM1MDAfDWgWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkDxQrAQVmZmZmZhYBBXdUZWxlcmlrLldlYi5VSS5SYWRDb21ib0JveEl0ZW0sIFRlbGVyaWsuV2ViLlVJLCBWZXJzaW9uPTIwMTcuMi42MjEuNDUsIEN1bHR1cmU9bmV1dHJhbCwgUHVibGljS2V5VG9rZW49MTIxZmFlNzgxNjViYTNkNBYOZg8PFgQfJgUJcmNiSGVhZGVyHycCAmRkAgEPDxYEHyYFCXJjYkZvb3Rlch8nAgJkZAICDw8WBB8KBQIxMB8NaBYCHzEFKmN0bDAwX0NvbnRlbnRQbGFjZUhvbGRlcjFfZ3ZfcmVzdWx0c19jdGwwMGQCAw8PFgQfCgUCMjAfDWgWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkAgQPDxYEHwoFAjUwHw1oFgIfMQUqY3RsMDBfQ29udGVudFBsYWNlSG9sZGVyMV9ndl9yZXN1bHRzX2N0bDAwZAIFDw8WBB8KBQMxMDAfDWcWAh8xBSpjdGwwMF9Db250ZW50UGxhY2VIb2xkZXIxX2d2X3Jlc3VsdHNfY3RsMDBkAgYPDxYEHwoFAzUwMB8NaBYCHzEFKmN0bDAwX0NvbnRlbnRQbGFjZUhvbGRlcjFfZ3ZfcmVzdWx0c19jdGwwMGQCAg8PFgIeBF9paWgFATBkFhBmDw8WAh8SaGQWAmYPDxYCHxNoZGQCAQ8PFgQfCgUGJm5ic3A7Hx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%2BAQ8PFgIfMwUCOTRkFhBmDw8WAh8SaGQWAmYPDxYCHxNoZGQCAQ8PFgQfCgUGJm5ic3A7HxJoZGQCAg8PFgIfCmVkFgICAQ8QDxYCHwoFBzI2NTc0NjJkZGRkAgMPDxYCHwoFA0RBWWRkAgQPDxYCHwoFB0JSQU5ET05kZAIFDw8WAh8KBQhSSURHRUxFWWRkAgYPDxYCHwoFAldWZGQCBw8PFgIfCgUGJm5ic3A7ZGQCvwEPZBYCZg8PFgIfEmhkZALAAQ8PFgIfMwUCOTVkFhBmDw8WAh8SaGQWAmYPDxYCHxNoZGQCAQ8PFgQfCgUGJm5ic3A7HxJoZGQCAg8PFgIfCmVkFgICAQ8QDxYCHwoFBjkwNDcxOGRkZGQCAw8PFgIfCgUDREFZZGQCBA8PFgIfCgUHQlJFTkRBIGRkAgUPDxYCHwoFCldJTEVZVklMTEVkZAIGDw8WAh8KBQJXVmRkAgcPDxYCHwoFBiZuYnNwO2RkAsEBD2QWAmYPDxYCHxJoZGQCwgEPDxYCHzMFAjk2ZBYQZg8PFgIfEmhkFgJmDw8WAh8TaGRkAgEPDxYEHwoFBiZuYnNwOx8SaGRkAgIPDxYCHwplZBYCAgEPEA8WAh8KBQQyNDkzZGRkZAIDDw8WAh8KBQNEQVlkZAIEDw8WAh8KBQZCUllBTiBkZAIFDw8WAh8KBQtNT1VORFNWSUxMRWRkAgYPDxYCHwoFAldWZGQCBw8PFgIfCgUGJm5ic3A7ZGQCwwEPZBYCZg8PFgIfEmhkZALEAQ8PFgIfMwUCOTdkFhBmDw8WAh8SaGQWAmYPDxYCHxNoZGQCAQ8PFgQfCgUGJm5ic3A7HxJoZGQCAg8PFgIfCmVkFgICAQ8QDxYCHwoFBzI3NjE2MTRkZGRkAgMPDxYCHwoFA0RBWWRkAgQPDxYCHwoFB0JSWUFOIFdkZAIFDw8WAh8KBQtNT1VOVCBTVE9STWRkAgYPDxYCHwoFAldWZGQCBw8PFgIfCgUGJm5ic3A7ZGQCxQEPZBYCZg8PFgIfEmhkZALGAQ8PFgIfMwUCOThkFhBmDw8WAh8SaGQWAmYPDxYCHxNoZGQCAQ8PFgQfCgUGJm5ic3A7HxJoZGQCAg8PFgIfCmVkFgICAQ8QDxYCHwoFBjk5MjEwN2RkZGQCAw8PFgIfCgUDREFZZGQCBA8PFgIfCgUWQy9PIEFMSUNFIERPV0xFUiBDQU5EWWRkAgUPDxYCHwoFC1BBUktFUlNCVVJHZGQCBg8PFgIfCgUCV1ZkZAIHDw8WAh8KBQYmbmJzcDtkZALHAQ9kFgJmDw8WAh8SaGRkAsgBDw8WAh8zBQI5OWQWEGYPDxYCHxJoZBYCZg8PFgIfE2hkZAIBDw8WBB8KBQYmbmJzcDsfEmhkZAICDw8WAh8KZWQWAgIBDxAPFgIfCgUGMzU4MjgyZGRkZAIDDw8WAh8KBQNEQVlkZAIEDw8WAh8KBQlDQUxEV0VMTCBkZAIFDw8WAh8KBQpIVU5USU5HVE9OZGQCBg8PFgIfCgUCV1ZkZAIHDw8WAh8KBQYmbmJzcDtkZALJAQ9kFgJmDw8WAh8SaGRkAgIPD2QWAh8iBQ1kaXNwbGF5Om5vbmU7FgJmDzwrAA0BAA8WBAUbVXNlQ29sdW1uSGVhZGVyc0FzU2VsZWN0b3JzaAUYVXNlUm93SGVhZGVyc0FzU2VsZWN0b3JzaA8WBB8pZx8oaGRkAiMPDxYCHxJnZGQCJQ8PZA8QFgNmAgECAhYDFgIeDlBhcmFtZXRlclZhbHVlBQNkb2UWAh80ZRYCHzRlFgNmZmZkZAInDw9kDxAWAWYWARYCHzRkFgECBWRkAikPD2QPEBYBZhYBFgIfNGQWAQIFZGQYAwUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFmcFD2N0bDAwJFRyZWVWaWV3MQUkY3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzBUdjdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsMDMkY3RsMDEkUGFnZVNpemVDb21ib0JveAU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDA0JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsMDYkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGwwOCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEwJGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsMTIkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGwxNCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE2JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsMTgkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGwyMCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDIyJGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsMjQkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGwyNiRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDI4JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsMzAkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGwzMiRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDM0JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsMzYkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGwzOCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDQwJGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsNDIkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw0NCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDQ2JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsNDgkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw1MCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDUyJGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsNTQkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw1NiRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDU4JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsNjAkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw2MiRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDY0JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsNjYkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw2OCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDcwJGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsNzIkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw3NCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDc2JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsNzgkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw4MCRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDgyJGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsODQkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw4NiRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDg4JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsOTAkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw5MiRjYl9jbGFpbQU5Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDk0JGNiX2NsYWltBTljdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGd2X3Jlc3VsdHMkY3RsMDAkY3RsOTYkY2JfY2xhaW0FOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGw5OCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEwMCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEwMiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEwNCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEwNiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEwOCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDExMCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDExMiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDExNCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDExNiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDExOCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEyMCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEyMiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEyNCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEyNiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEyOCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEzMCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEzMiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEzNCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEzNiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDEzOCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE0MCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE0MiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE0NCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE0NiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE0OCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE1MCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE1MiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE1NCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE1NiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE1OCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE2MCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE2MiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE2NCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE2NiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE2OCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE3MCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE3MiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE3NCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE3NiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE3OCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE4MCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE4MiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE4NCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE4NiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE4OCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE5MCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE5MiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE5NCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE5NiRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDE5OCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDIwMCRjYl9jbGFpbQU6Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDIwMiRjYl9jbGFpbQVHY3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRndl9yZXN1bHRzJGN0bDAwJGN0bDAzJGN0bDAxJFBhZ2VTaXplQ29tYm9Cb3gPFCsAAgUDMTAwZWQFR2N0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZ3ZfcmVzdWx0cyRjdGwwMCRjdGwwMiRjdGwwMCRQYWdlU2l6ZUNvbWJvQm94DxQrAAJlZWQANb2yid%2FrpjNE2zq5V2YyrdvcPhrC8lUVTdJww0k0sQ%3D%3D&__VIEWSTATEGENERATOR=F8EED458&__EVENTVALIDATION=%2FwEdAHRMJneA2s2CRo0GAqkBArnaqBKqMJJku8dkuCNjeqKd0mpMDAjD4e4Y3iGlQDh5gvK%2FPUi7N3qae9wuB2mDQrQ1zwxxuPUJO6p2m4SRKCcH0dJo3Tg9l5jniQ9fLKE3n31hAFFhqSC69NaBCgXsuCQ0gEl06H0g7FQRJw7FANjaA1icTiGTe8ImrPAQyBOqx0rjpCO8%2BtV18d%2BveoD5BwxdRZTua93RdWkIY8KjCo0upZXdzvLE7P4CS3Dky26kcVgXBKaws2xbhYHjnR1oQaRsHIsmGNOiaJnSvaXUYCBeb%2BavSP4a2XCSA%2Fcjolc5ygc00Z8Ob%2ByaeUwq5n9U3PtRCpSOaf5YSgS9nXco4cNr0WqHzKfVpaQw5RNLF9OO%2Bx0ZY%2FKD40gmdbWnvu8OrL%2BZrNrIXiEdUixLj1IE0nYiTytG531CzacLlMM8WVryHi20Xqb8oiCkC7Uyrz%2Bb5FE2NhD%2BpbA4djTP7H8oZLC%2FOPfHbxsw73JT1g8lG5xAPtfWVrg7i3MKJWJhrQdmLGyk4CgMPnA7tOOKBDhaGvgZlbv3D2yA7KkNGabXWXpIhN44csKle8JxshiCNR74SJlfuR2598fXvdrbeKlN%2FtLTly4XO4mjnt7RpR7%2FNoUj1adXY1tMFyfb1sCuRLVPW9kMR0L510TXdU5eTa5O6kqONkiVQxNIsMdSpGrWlVkZ5Vg%2FuPVwIV45pGlwKLy6zXL3DcWg7ZYiZogZM7GutWSBiddBpGQaVzEqNjVpEgJ19omgBu0fe0T3YWnpUBokp56wLS%2FmscQ8czlnaW%2BpiRUM4YdJygyghlBH4O8eXbNCAOZUMAY%2BWcy4Cb1V5IQ0%2BvBeRu%2BJB9retKgVOZovOH1%2B6UXtNPdFiSUuw%2BKzfcE5oeShHZ%2BlL%2Bn2RkPCc5DBhSV21mFIljLhmeU0bEBjTzAV5QElZVaIMJgMmViruEEj8pNkHm%2FvNveFafJxQOqwSxhwMcSm7EQcu9foCx7FWhW3ShecpS%2BQPJ0ZKuXaEGYEj070%2F%2B7uEiKgrE2wjgKQ5ndJ7j0LO%2BUUwtLuaYNNPqzv1HJpeAGrPQntCwDBEjAEib854F5hzsQ8KX4VBMvnaiZSOZVZCNnkkCgf5UrWc6xpH0NSF8mzWQ%2Fx%2FYR4OGItWbf7PzqYfYVyOT6V9Be5ul8VP6%2BjgzrOTEtlmVH15E3JgLlUzH1%2Bot0MHmXb2s28uvxdfCmHo3dRy9%2F7yHrn5CuLIDynKr85W5WhIjbItT5CVqmU9esDUPj6oC9EAJwc9n8D%2BzNaLuV5zjk0YAemamBiGpnNaSWD%2FZtARdu56fNHmKFrZjevCLhJOAGLkoewYzaG0xWtfLEiOyAzJYRkPCwwiHufZDZxnRPvXgi872ShHdgq6bw0R42rvKiz7f3oCiYISK%2FYoXbaJPJwlu0VqHq9Y%2Brz%2BuAgfEVqIr4g%2FyA7oXSJ3iiCWRialrs5jA6gFx8JIWZoUqgN2NUUWJoSR%2FLTDfwqKrWKCWXXuNDBmn0OW7nqxzV4NzA0v%2BE%2BAbPdgIR3hZe62AIHt%2BQ%2FWk%2BUd1XjuSOuvbkrtnmSx1lx5gZzYCtwV7EVk5ryfcrkzOmK3lykCux6QjmnP01bMollhE6TeEWlAVSALxAf4mT%2BUy6v3fchFX2926Ulnv0bNKVpmdlaoOplitnQ86a23gcBkibbSkqPJ0lSP61cMlJ1cm1GpoZHeO7LI3Px5l37d5MXnUHCvoQZqZ78925hgPPOf1wccVAKgrekQVvgTX5QO92ndUhg69CL7j3I9lIC2eNyeoB1CoCSk%2FWQVFmRV7hD5vZiIyl%2BT5iUJ%2FDICOKyiJStplSjC3UI64kvJPXJgryZ8%2Byp9rlvCnMZQemnNc53V488kUcIqNmzDNj3lptX9jmUnO3Sdp5%2FfRZsx9nnE9BpIqjx3TdlxMJo1nENoFOWw%2FVB5Gujph14dtEMpmtb%2Fq%2FTRxU7gZ6LqxKvPvKsZypMIii3Pxb9I28Wmqb3CQTdoTOSN9qHk5TDWzi2pEvioeoTwBCBFGq6YMgQplqIGaCS8kTAU0cdBiWPYwhSQFMJ2eoKF%2BdNxXk6QznQ%2Bd3eQbtEwiigYqRkeNdHFZ9Yr1svAqgF%2Fqi4nYNDKvd%2B6dB9Waja1uYHn%2Fd2nIOMpONWczyoABLHiGALU0ONybHUqmTkTMufsNe8keLjzAyfozYJgxwOI23vjNLX5cld%2FRXr9qDOFl1Hlnv6FiaWu9G6bLwHJjcF6wP01swXtoSc7jnesOFyV4kvI6N4Yol3hXpqY3q4ObWR5etW1GR1XDX5BfDtu72adoYscFJlezEwbKLSV%2BYzjO7yKdrlbvJ0zptRMlpIisk%2Fw0p3YfKSkz3%2FVwlLRWi%2B2Yi8o4PUfkKyihIVlYTjnPn8hMEBYFHoQWV0D5wOCHsuQn93l2GeQdvKG5%2FbYka2wQmvaYMi9uRHj5A7RmAFD1b7rmdkr0aRHnwrqA73Nomsr2bw3DEFYzjhKUPkhMM%2BkO5FAyeS7DF2KXVoUaIK&__ASYNCPOST=true&RadAJAXControlID=ctl00_ContentPlaceHolder1_RadAjaxManager1'
          ;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $website_url);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);

        $response2 = curl_exec($ch);
        
        $html = $this->CI->simple_html_dom->load($response2);
        $prependcode = '<base href = "" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        
        $tab = $html->find('table[id=ctl00_ContentPlaceHolder1_gv_results_ctl00] tbody tr[class=rgAltRow]');
        $tab2 = $html->find('table[id=ctl00_ContentPlaceHolder1_gv_results_ctl00] tbody tr[class=rgRow]');
       $data_arr = array();
       $j = 0;
        foreach ($tab as $tr) {
            $i = 0;
            foreach ($tr->find('td') as $input) {                
                if($j>=300) break;
                if ($i == 1) {                      
                    $data_arr[$j]['Name'] = $data_arr[$j]['Name']." ". $input->innertext;
                } else if ($i == 2) {                    
                    $data_arr[$j]['Name'] = $data_arr[$j]['Name']." ". $input->innertext;
                } else if ($i == 3) {
                    $data_arr[$j]['Location'] = isset($input->innertext) ? trim($input->innertext) : '';
                } else if ($i == 4) {
                    $data_arr[$j]['state'] = isset($input->innertext) ? trim($input->innertext) : '';
                    $data_arr[$j]['Amount'] = '';
                    $data_arr[$j]['CoOwnerName'] = '';
                    $data_arr[$j]['ReportingCompany'] = '';                        
                    $data_arr[$j]['Shares'] = '';
                }
                
                
                $i++;                 
            }
            $j++;
        }
        foreach ($tab2 as $tr) {
            $i = 0;
            foreach ($tr->find('td') as $input) {                
                if($j>=300) break;
                if ($i == 1) {                      
                    $data_arr[$j]['Name'] = $data_arr[$j]['Name']." ". $input->innertext;
                } else if ($i == 2) {                    
                    $data_arr[$j]['Name'] = $data_arr[$j]['Name']." ". $input->innertext;
                } else if ($i == 3) {
                    $data_arr[$j]['Location'] = isset($input->innertext) ? trim($input->innertext) : '';
                } else if ($i == 4) {
                    $data_arr[$j]['state'] = isset($input->innertext) ? trim($input->innertext) : '';
                    $data_arr[$j]['Amount'] = '';
                    $data_arr[$j]['CoOwnerName'] = '';
                    $data_arr[$j]['ReportingCompany'] = '';                        
                    $data_arr[$j]['Shares'] = '';
                }
                $i++;                 
            }
            $j++;
        }
        
        $res_raw['data'] = $data_arr;        
        $res_raw['scraped_html'] = $html;
        return $res_raw;        
    }
    
    public function scrapeWisconsin($search_data)
    {        
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : "";         
        $city = isset($search_data['city']) ? $search_data['city'] : "";         
        $website_url = "https://tap.revenue.wi.gov/UCPSearch/_/EventOccurred";
        $cookieFile="";        
        $postData = 'c-7=DOE&c-8=JOHN&c-9=&c-g=&LASTFOCUSFIELD__=c-8&DOC_MODAL_ID__=0&EVENT__=c-b&TYPE__=0&CLOSECONFIRMED__=false&FAST_SCRIPT_VER__=1&FAST_VERLAST__=4._._.KiL65LFoRCDVSmBOwMMVFS2o9wU1&FAST_VERLAST_SOURCE__=_%3ARecalc%3A1774127216%20%40%202019-02-20%2001%3A04%3A59.9381&FAST_CLIENT_WHEN__=1550646305247&FAST_CLIENT_WINDOW__=FWDC.WND-bcfe-6692-7ef8&FAST_CLIENT_AJAX_ID__=5&FAST_CLIENT_TRIGGER__=DocFieldLinkClick&FAST_CLIENT_SOURCE_ID__=c-b'
          ;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_URL, $website_url);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);     
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0');
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
        $response2 = curl_exec($ch);
        print_r($response2);        
    }
    
    
    public function scrapeMissingMoneyData($search_data) {        
        $base_url = 'https://www.missingmoney.com/en/Property/Search';
        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        
        $data_arr = array();
        $mainData = [];
        $context = stream_context_create([
            'http' => [
                'proxy' => $this->proxy,
                'request_fulluri' => true
            ]
        ]);
        $url = $base_url.'?searchName='.urlencode($fname.' '.$name).'&State='.urlencode($search_data['state']).'&page=';
        $response = file_get_contents($url.'1');
        $html = $this->CI->simple_html_dom->load($response);
        $prependcode = '<base href = "'.$base_url.'" /><meta name="robots" content="noindex, nofollow,noarchive" />';
        $html->find('head', 0)->innertext = $prependcode . $html->find('head', 0)->innertext;
        
        foreach ($html->find('.pagination li') as $e) {
            $l++;
        }

        $data_arr['scraped_html'] = $html;
        //declare array and variables
        $even_data_arr = array();              
        $i = 0;
        $j = 0;
        
        $data_arr['data'] = array();    

        for($a=1;$a<=$l;$a++)
        {               
            if($a > 1){                
                $page2 = file_get_contents($url.$a);
                $html = $this->CI->simple_html_dom->load($page2);    
            }

            foreach ($html->find('table.hrs-table tbody tr') as $e) {               
                foreach ($e->find('td') as $input) {                    
                    if ($i == 1) {                        
                        $even_data_arr[$j]['Name'] = strip_tags(trim($input->innertext));
                    } else if ($i == 2) {
                        $even_data_arr[$j]['State'] = strip_tags(trim($input->innertext));
                    } else if ($i == 3) {
                        $even_data_arr[$j]['Location'] = strip_tags(trim($input->innertext));
                    } else if ($i == 4) {
                        $even_data_arr[$j]['ReportedBy'] = strip_tags(trim(str_replace("Reported By:", "", $input->innertext)));
                    } else if ($i == 5) {
                        $even_data_arr[$j]['Amount'] = strip_tags(trim($input->innertext));
                        $even_data_arr[$j]['PropertyId'] = '';                        
                        $even_data_arr[$j]['CoOwnerName'] = '';
                        $even_data_arr[$j]['ReportingCompany'] = '';                        
                        $even_data_arr[$j]['Shares'] = '';
                        $i = 0;
                        $j++;
                    }
                    $i++;
                }                
                $i = 0;  
            }
        }            

        $data_arr['data'] = $even_data_arr;
        return $data_arr;
    }    
    
    public function scrapeNorthDakota($search_data) {
        $post_website_url = 'https://unclaimedproperty.nd.gov/SWS/properties';
        $website_url = 'https://unclaimedproperty.nd.gov/app/claim-search';
        $website_key = '6LcjZZkUAAAAAPFrNzyuKJNp36XVr_j2x-fPOR26';       

        $name = ($search_data['lname'] != '') ? $search_data['lname'] : $search_data['bname'];
        $fname = isset($search_data['fname']) ? $search_data['fname'] : ""; 
        $city = isset($search_data['city']) ? $search_data['city'] : "";
        $propertyId = isset($search_data['propertyId']) ? $search_data['propertyId'] : "";
        $zip = isset($search_data['zip']) ? $search_data['zip'] : "";
        //set form fields 
        $fields = array(
            'lastName' =>$name,
            'firstName' =>$fname,
            'city' => $city,
            'searchZipCode' => $zip,
            'propertyID' => $propertyId
        );

        $data = $this->getData($fields, $post_website_url, $website_url, $website_key);        
        return $data;
    }
}
