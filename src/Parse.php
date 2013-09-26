<?php

class Parse {

    private $_appid = '';
    private $_masterkey = '';
    private $_restkey = '';
    private $_parseurl = '';
    
    protected $_count = 1;
    protected $_limit = false;
    protected $_skip  = false;
    protected $_order = false;
    protected $_useMasterKey = false;
    protected $_sessionToken = false;
    
    public $data;
    public $requestUrl = '';
    public $returnData = '';

    public function __construct(){
        $parseConfig = new \Parse\Config;
        $this->_appid = $parseConfig::APPID;
        $this->_masterkey = $parseConfig::MASTERKEY;
        $this->_restkey = $parseConfig::RESTKEY;
        $this->_parseurl = $parseConfig::PARSEURL;

        if(empty($this->_appid) || empty($this->_restkey) || empty($this->_masterkey)){
            $this->throwError('You must set your Application ID, Master Key and REST API Key');
        }

        $version = curl_version();
        $ssl_supported = ( $version['features'] & CURL_VERSION_SSL );

        if(!$ssl_supported){
            $this->throwError('CURL ssl support not found');	
        }

    }

    /*
     * All requests go through this function
     * 
     *
     */	
    public function request($args){
        $isFile = false;
        
        $url = $this->_parseurl . $args['requestUrl'];
        
        $c = curl_init();
        curl_setopt($c, CURLOPT_VERBOSE, TRUE);
        curl_setopt($c, CURLOPT_TIMEOUT, 30);
        curl_setopt($c, CURLOPT_USERAGENT, 'parse.com-php-library/2.0');
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLINFO_HEADER_OUT, true);
        
        $headers = array(
            'X-Parse-Application-Id: '.$this->_appid,
        );
        
        // Check if a file request, otherwise set the REST API Key.
        if(substr($args['requestUrl'],0,5) == 'files'){
            $headers[] = 'Content-Type: '.$args['contentType'];
            $isFile = true;
        } else {
            $headers[] ='X-Parse-REST-API-Key: '.$this->_restkey;
            $headers[] ='Content-Type: application/json';
        }
        
        if ($this->_useMasterKey) {
            $headers[] = 'X-Parse-Master-Key: '.$this->_masterkey;
        }
        if ($this->_sessionToken) {
            $headers[] = 'X-Parse-Session-Token: ' . $this->_sessionToken;
        }
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        
        
        if($args['method'] == 'PUT' || $args['method'] == 'POST'){
            if($isFile){
                $postData = $args['data'];
            }
            else{
                $postData = json_encode($args['data']);
            }

            curl_setopt($c, CURLOPT_POSTFIELDS, $postData );
        } else if ($args['method'] == 'GET') {
            $query = array();
            
            if (array_key_exists('where', $args)) {
                $query['where'] = json_encode($args['where']);
            }
            if (array_key_exists('include', $args)) {
                $query['include'] = $args['include'];
                
            }
            
            $fields = array('limit', 'skip', 'count', 'order');
            foreach ($fields as $field) {
                $prop = '_' . $field;
                if ($this->$prop) {
                    $query[$field] = $this->$prop;
                }
            }

            
            $new_url = array('query' => http_build_query($query));
            $url = http_build_url($url, $new_url, HTTP_URL_JOIN_QUERY);;
        }
        
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, $args['method']);
        
        if($args['requestUrl'] == 'login'){
            $urlParams = http_build_query($args['data'], '', '&');
            $url = http_build_url($url, array('query' => $urlParams), HTTP_URL_JOIN_QUERY);
        }
        if(array_key_exists('urlParams',$args)){
            $urlParams = http_build_query($args['urlParams'], '', '&');
            $url = http_build_url($url, array('query' => $urlParams), HTTP_URL_JOIN_QUERY);
        }

        curl_setopt($c, CURLOPT_URL, $url);

        $response = curl_exec($c);
        $responseCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        $expectedCode = array('200');
        if($args['method'] == 'POST' && substr($args['requestUrl'],0,4) != 'push'){
            // checking if it is not cloud code - it returns code 200
            if(substr($args['requestUrl'],0,9) != 'functions'){
                $expectedCode = array('200','201');
            }
        }

        //BELOW HELPS WITH DEBUGGING		
                /*
                if(!in_array($responseCode,$expectedCode)){
                        //print_r($response);
                        //print_r($args);		
                }
                 */
        return $this->checkResponse($response,$responseCode,$expectedCode);
    }

    public function dataType($type, $params){
        if($type != ''){
            switch($type){
            case 'date':
                $return = array(
                    "__type" => "Date",
                    "iso" => date("c", strtotime($params))
                );
                break;
            case 'bytes':
                $return = array(
                    "__type" => "Bytes",
                    "base64" => base64_encode($params)
                );			
                break;
            case 'pointer':
                $return = array(
                    "__type" => "Pointer",
                    "className" => $params[0],
                    "objectId" => $params[1]
                );			
                break;
            case 'geopoint':
                $return = array(
                    "__type" => "GeoPoint",
                    "latitude" => floatval($params[0]),
                    "longitude" => floatval($params[1])
                );			
                break;
            case 'file':
                $return = array(
                    "__type" => "File",
                    "name" => $params[0],
                );			
                break;
            case 'increment':
                $return = array(
                    "__op" => "Increment",
                    "amount" => $params[0]
                );
                break;
            case 'decrement':
                $return = array(
                    "__op" => "Decrement",
                    "amount" => $params[0]
                );
                break;
            default:
                $return = false;
                break;	
            }

            return $return;
        }	
    }

    public function throwError($msg,$code=0){
        throw new Exception($msg,$code);
    }
    
    public function limit($int) {
        $this->_limit = $int;
    }

    public function skip($int) {
        $this->_skip = $int;
    }

    public function order($int) {
        $this->_order = $int;
    }
    public function setSessionToken($token) {
        $this->_sessionToken = $token;
    }
    public function useMasterKey() {
        $this->_useMasterKey = true;
    }

    private function checkResponse($response,$responseCode,$expectedCode){
        //TODO: Need to also check for response for a correct result from parse.com
        if(!in_array($responseCode,$expectedCode)){
            $error = json_decode($response);
            if (is_object($error)) {
                throw new Exception($error->error, $error->code);
            } else {
                throw new Exception("Data server responded with: $responseCode, $response");
            }
        }
        else{
            //check for empty return
            if($response == '{}'){
                return true;
            }
            else{
                return json_decode($response);
            }
        }
    }
}


class ParseLibraryException extends Exception{
    public $message, $code;
    public function __construct($message, $code = 0, Exception $previous = null) {
        //codes are only set by a parse.com error
        if($code != 0){
            $message = "parse.com error: ".$message;
        }

        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
