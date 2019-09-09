<?php

/*
Name: MyLI PHP API Class
Version: 3.0
Author: Louis Varley
Author URI: http://www.landscapeinstitute.org
*/

class myLI{
	
	/* If Extending the MyLI Class this info can come from a database for example */
	public function __construct( $arr ){		
		$this->setup( $arr );			
	}
	
	public function setup( $arr ){
		
		/* Access Token if provided */
		$this->access_token = (isset($arr['access_token']) ? $arr['access_token'] : null);	

		/* Instance are we accessing */
		$this->instance_url = (isset($arr['instance_url']) ? rtrim($arr['instance_url'],"/") : null);
		
		/* No Instance Provided */
		if(empty($this->instance_url)) die('No Instance URL Provided');		
		
		/* Client ID */
		$this->client_id = (isset($arr['client_id']) ? $arr['client_id'] : null);
		
		/* Client Secret */
		$this->client_secret = (isset($arr['client_secret']) ? $arr['client_secret'] : null);
		
		/* Generate JSON URL from Instance URL */
 		$this->json_url = $this->instance_url . '/api/swagger.json';
		
		/* Generate oAuth URL from Instance URL */
		$this->oauth_url = $this->instance_url . '/oauth';
		
		/* Sets any pending Auth Codes to this instance */
		$this->set_auth_code();
		
		/* Is Debug Enabled */
		$this->debug = (isset($arr['debug']) ? $arr['debug'] : false);
	
		/* Instance of the API */
		$this->api = new myLIAPI(array(
			'access_token' => $this->access_token,
			'debug' => $this->debug,
			'json_url' => $this->json_url,
		));	
		
	}
	
	/* Get Authcode and return to callback URL */
	private function get_auth_code($redirect=null){
		
		if(empty($this->client_id)){
			return new myLIException("No Client ID provided");
		}
		
		if(empty($redirect)){
			$redirect = myLIHelper::current_url();
		}
		
		header("Location: " . $this->oauth_url . '/auth/' . '?redirect=' . urlencode($redirect) . '&client_id=' . $this->client_id);
		die();
		
	}

	/* Is the users access token set and valid? */
	public function has_access_token(){
		
		if(!isset($this->access_token) || !$this->access_token_valid($this->access_token)){
			return false;
		}else{
			return true;
		}
		
	}

	/* Get Access Token */
	public function get_access_token(){
		
		if(empty($this->auth_code)){
			$this->get_auth_code();
		}
		
		if(empty($this->client_id) || empty($this->client_secret)){
			return new myLIException("No Client ID or Client Secret provided");
		}
		
		$params = array(		
			'client_id' => urlencode($this->client_id),
			'client_secret' => urlencode($this->client_secret),
			'code' => urlencode($this->auth_code),
		);
		
		$authURL = $this->oauth_url . '/token?' . http_build_query($params);
		
		$ch = curl_init();
 
		if ($ch === false) {
			return new myLIException("Unable to initialise CURL");
		}
		
		$authURL = urlencode($authURL);
		$authURL = urldecode($authURL);

		curl_setopt($ch, CURLOPT_URL, $authURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		
		$content = curl_exec($ch);

		if ($content === false) {
			return new myLIException("No response from $authURL");
		}

		curl_close($ch);
		
		$token = json_decode($content);
		
		if(empty($token)){
			return new myLIException("No token was returned from $authURL");
		}
		
		$this->set_access_token($token->Token);
		
		return $this->access_token;
		
	}
	
	/* Set Auth Code if was returned, call within CallBack and is in the URL params */
	private function set_auth_code(){
		
		if(isset($_GET['code'])){
			$this->auth_code = ($_GET['code']);
		}
	}
	
	/* The Origin / Redirect passed back when fetching an Auth Code */
	public function get_origin(){
		
		if(isset($_GET['redirect'])) return urldecode($_GET['redirect']);
		if(isset($_GET['origin'])) return urldecode($_GET['origin']);	
		return false;
	}
	
	/* Log Out */
	function logout( $redirect=null ){
		
		$redirect = empty($redirect) ? myLIHelper::current_url() : $redirect;
		$logout = $this->api->app->getlogouturl->query(array('redirect'=>$redirect ));
		header("Location: " . $logout);
		die();	
		
	}
	
	/* Log In - Alias for get Access Token */
	function login( $redirect=null ){
			
		if(empty($this->auth_code)){
			$this->get_auth_code($redirect);
		}	
			
		$this->get_access_token();
	}	
	
	/* Set the access token */
	function set_access_token($access_token){

		$this->access_token = $access_token;
		$this->api->access_token = $access_token;
    
	}
			
	/* Check the current access token is valid */
	private function access_token_valid(){
		
		if(isset($this->access_token) && $this->api->oAuth->isaccesstokenvalid->query(array('accessToken'=>$this->access_token))){
			return true;
		}else{
			return false;
		}
		
	}	
	
	/* Pulls access token owners basic profile */
	function get_user_profile(){
		
		return $this->api->me->userprofile->query();
		
	}
 
	/* Pulls access token owners account basic profile */ 
    function get_account_profile(){

		return $this->api->me->accountprofile->query();        
        
    }
 
	/* Pulls access token owners current account membership details */ 
    function get_account_membership(){

		return $this->api->me->accountmembership->query();
		
	}	
	
	/* Pulls access token owners current membership details */
	function get_user_membership(){

		return $this->api->me->usermembership->query();
		
	}	

	/* Call Any Other Endpoint */
	function call($endpoint,$method,$args){
		
		if(isset($this->api->${strtolower($endpoint)})){
			if(isset($this->api->${strtolower($endpoint)}->${strtolower($method)})){			
				if(isset($this->api->${strtolower($endpoint)}->${strtolower($method)}->query)){	
					return $this->api->${strtolower($endpoint)}->${strtolower($method)}->query($args);
				}
			}
		}
	}
}

class myLIHelper{
	
	public static function current_url(){
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";	
	}
	
}


class myLIAPI {
	
	/* Returns an Instance of the MyLI API or if failed, returns false */
	public function __construct($arr){
		
		if(!empty($arr['json_url']))$this->json_url = $arr['json_url'];
		if(!empty($arr['access_token']))$this->access_token = $arr['access_token'];
		if(!empty($arr['debug']))$this->debug = $arr['debug'];

		if(empty($this->json_url)){
			return false;
		}
		
		if(!$this->get_api_resources()){
			return false;
		};
	
	}
    
    public function __get($var) {
        
        if (property_exists($this, $var)) {
            return $this->$var;
        }
        
        if (property_exists($this, strtolower($var))) {
            $var = strtolower($var);
            return $this->$var;
        }       
        
        if (property_exists($this, lcfirst($var))) {
            $var = lcfirst($var);
            return $this->$var;
        }      
        
        if (property_exists($this, ucfirst($var))) {
            $var = ucfirst($var);
            return $this->$var;
        }       

        return null;
 
    }    

    public function __set($var, $value) {
        
        if (property_exists($this, $var)) {
            $this->$var = $value;
        }
        
        if (property_exists($this, strtolower($var))) {
            $var = strtolower($var);
            $this->$var = $value;
        }       
        
        if (property_exists($this, lcfirst($var))) {
            $var = lcfirst($var);
            $this->$var = $value;
        }  
        
        $this->$var = $value;
        return;
       
    }

	public function get_api_resources(){
		
		$this->json_url = str_replace('local','staging',$this->json_url);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->json_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		
		$json = curl_exec($ch);

		curl_close($ch);
		
		$obj = json_decode($json);
		
		$paths = $obj->paths;
		
		foreach($paths as $path => $pathObj){
			$baseName = strtolower(explode("/",$path)[2]);
			$methodName = strtolower(explode("/",$path)[3]);
			$this->addBase($methodName, $baseName, $path, $pathObj);
		}	
			
				
	}
    
    public function addBase($methodName,$baseName,$path,$pathObj){
              
        if(empty($this->$baseName)){
            $this->$baseName = new myLIAPIBase();
            $this->$baseName->name = $baseName;
        }
        
        if(property_exists($pathObj,'get'))
            $methodType = 'get';
           
        if(property_exists($pathObj,'post'))
            $methodType = 'post';

        if(property_exists($pathObj,'patch'))
            $methodType = 'put';

        if(property_exists($pathObj,'put'))
            $methodType = 'put';

        if(property_exists($pathObj,'delete'))
            $methodType = 'delete';

        $this_method = new myLIAPIMethod($this);	
        
        if(isset($pathObj->$methodType->parameters)){
            
            $this_method->params = [];
            
            foreach($pathObj->$methodType->parameters as $param){
                
                $this_param = new myLIAPIParam();
                $this_param->name = (isset($param->name) ? $param->name : null);
                $this_param->in = (isset($param->in) ? $param->in : null);
                $this_param->required = (isset($param->required) ? $param->required : null);
                $this_param->type = (isset($param->type) ? $param->type : null);
                $this_param->format = (isset($param->format) ? $param->format : null);
                $this_method->params[$param->name] = $this_param;
            }
            
        }
        

        $this_method->methodName = $methodName;
        $this_method->method = $methodType;
        $this->$baseName->$methodName = $this_method;    
        $baseURL = parse_url($this->json_url);	
        $this->$baseName->$methodName->default_url = $baseURL['scheme'] . '://' . $baseURL['host'] . $path;   
        $this->hasResources = true;
        
    }
	
	public function call($url, $url_arguments, $body = null, $method = "GET", $type="default"){
		
		$this->method = $method;
		$this->url = $url;
		$this->url_arguments = $url_arguments;
		$this->type = $type;
		$this->body = $body;
		
	}
	
	public function execute(){

		$curl = curl_init();
		
		switch ($this->method)
		{
			case "post": 
		
				curl_setopt($curl, CURLOPT_POST, true);	

				if(!empty($this->url_arguments))
					$this->url = $this->url . '?' . $this->url_arguments;

				if(!empty($this->body))				
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->body);
				
				if($this->type=='default'){
					$content_type = 'application/json';
				}else{
					$content_type = $this->type;
				}
			
				
				break;
				
			case "get": 
	
				curl_setopt($curl, CURLOPT_HTTPGET, true);
				
				if(preg_match('/{(.*)}/',$this->url)){
					
					$this->url = preg_replace('/{.*?}/', '', $this->url);
				}
				
				if(!empty($this->url_arguments))
					$this->url = $this->url . '?' . $this->url_arguments;

				if(!empty($this->body))				
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->body);
				
				if($this->type=='default'){
					$content_type = 'application/x-www-form-urlencoded';
				}else{
					$content_type = $this->type;
				}
				
				
								
				break;
				
		}		
		
		curl_setopt($curl, CURLOPT_URL, $this->url);
			
		$headers = array(
			'Accept: application/json',
			'Content-Type: ' . $content_type,			
			'accessToken: ' . $this->access_token,
			'Content-Length: ' . strlen($this->body),
		);
		
		curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_USERAGENT, 'MyLI');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);

		if($this->debug)
			curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
		
		
		$results = curl_exec($curl);

		if($this->debug){

			$post_data = json_decode($this->url_arguments);
			$version = curl_version();
			$headers_data = implode( ', ', $headers );
		
			extract(curl_getinfo($curl));

			$metrics = "			
				API Method.. : $this->method
				API URL....: '$url'
				Called URL : $this->url
				URL Arguments : $this->url_arguments
				Return Code...: $http_code ($redirect_count redirect(s) in $redirect_time secs)
				Content: $content_type
				Size: $download_content_length (Own: $size_download) Filetime: $filetime
				Time...: $total_time Start @ $starttransfer_time (DNS: $namelookup_time Connect: $connect_time Request: $pretransfer_time)
				Speed..: Down: $speed_download (avg.) Up: $speed_upload (avg.)
				Headers..: $headers_data;
				Body: $this->body 
				";		
				var_dump($metrics);
				
			
		}
	
		$http_response = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		if($http_response == 200){
		
			if($this->isJson($results)){
				$this->results = json_decode($results);
			}
				
			if($results=="true")
				$this->results = true;
				
			if($results=="false")
				$this->results = false;
			
			if(!$this->isJson($results))
				$this->results = $results;

			return $this->results;
		
		} else {
			
			return false;
			
		}
		
	
	} 

	function isJson($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

}

class myLIAPIMethod{
	
	public $methodName;
	
	public function __construct($api){
		$this->api = $api;
	}
	
	public function __get($var) {

        if (property_exists($this, $var)) {
            return $this->$var;
        }
        
        if (property_exists($this, strtolower($var))) {
            $var = strtolower($var);
            return $this->$var;
        }       
        
        if (property_exists($this, lcfirst($var))) {
            $var = lcfirst($var);
            return $this->$var;
        }      
        
        if (property_exists($this, ucfirst($var))) {
            $var = ucfirst($var);
            return $this->$var;
        }       

        return null;
 
    }    	

	public function query($args=null,$type="default"){
		
		if(!$args){$args=array();}
		if(empty($this->params)){$this->params=array();}
		
		$this->url = $this->default_url;
		$this->type = $type;
		$urlQuery = '';
		$requestBody = '';		

		if(is_array($args)){
			
			foreach($this->params as $param_key=>$param){
				
				if($param->required == false && empty($args[$param->name])){
					continue;
				}
				
				if(is_bool($args[$param_key]))
					$args[$param_key] = ($args[$param_key]) ? 'true' : 'false';			
			
				if($param->required == true && 
						 !array_key_exists($param->name,$args)){

						if(_DEBUG){
							echo "Params Information:\n<pre>$this->methodName required param $param->name not provided or missing from API Call</pre>\n";	
						}
						
						return false;		
				} 
							
				if($param->in == 'query'){
					$urlQuery .= $param_key . '=' . $args[$param_key] . '&';
				}

				if($param->in == 'path'){
					$this->url = str_replace('{' . $param_key . '}', $args[$param_key], $this->url);		
				}
				
				if($param->in == 'body'){
					$requestBody .= $args[$param_key] . ',';
				}	
				
			}
		} 

		$urlQuery = rtrim($urlQuery,'&');
		$requestBody = rtrim($requestBody,',');
		
		$this->api->call($this->url, $urlQuery, $requestBody, $this->method, $this->type);
		
		try{
		

			$this->results = $this->api->execute();
			
			if(!is_array($args))	
				return ($this->results);
			
			if(empty($args))
				return ($this->results);
			
			if(!empty($args) && is_array($args))
				return ($this->results);
			
		}
		
		catch (Exception $e) {
			
			return $e;
		}
		
	}
	
	
	
}

class myLIAPIParam{
	
	public $name;
	public $in;
	public $required;
	public $type;
	public $format;
	public $this_param;
}

class myLIAPIBase {
	
}

class myLIException{
	
	function __construct($error="undefined",$code=2){
		
		$this->code = $code;
		$this->error = $error;
	}
	
}


?>
