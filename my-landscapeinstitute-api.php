<?php
/**
* MyLI API and oAuth Class
*
* @author     Louis Varley <louisvarley@googlemail.com>
* @copyright  2019 Landscape Institute
* @license    http://www.php.net/license/3_1.txt  PHP License 3.1
*/

class myLIHelper{
	
	public static function current_url(){
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";	
	}
	
}

class myLISession{
	
	public static function save($key,$v){
		if(session_status() == PHP_SESSION_NONE) session_start();
			$_SESSION['myli_' . $key] = $v; 
	}
	
	public static function load($key){
		if(self::exists($key))
			return $_SESSION['myli_' . $key];
	}
	
	public static function exists($key){
		if(!empty($_SESSION['myli_' . $key])) return true;
	}
	
	public static function kill_all(){

		foreach($_SESSION as $key => $val){
			
			if(strpos($key,'myli_')  !== false){
				unset($_SESSION[$key]);
			}
		}
		
		
	}
	
}

class myLI{
	
	public function __construct($arr){

		/*
		
		@arr options:
		access_token: If a token is already available, such as a personal access token
		client_id: Provided client ID for your app, if not using a personal access token
		client_secret: Provided client secret for your app, if not using a personal access token
		instance_url: the full URL to the root of the MyLI Instance you are connecting to. 
		
		*/
		
		/* Pull Access Token from either ARR or session is available */
		$this->access_token = (isset($arr['access_token']) ? $arr['access_token'] : myLISession::load('access_token'));	
		
		/* Pull Auth Code from either ARR or session is available */		
		$this->auth_code = (isset($arr['auth_code']) ? $arr['auth_code'] : myLISession::load('auth_code'));	
		
		/* Check Access Token is valid, log out if not */
		if(!$this->access_token_valid($this->access_token)){
			$this->logout();
		}
		
		/* Instance are we accessing */
		$this->instance_url = (isset($arr['instance_url']) ? rtrim($arr['instance_url'],"/") : null);
		
		/* Required for App Access Token Generation */
		$this->client_id = (isset($arr['client_id']) ? $arr['client_id'] : null);
		$this->client_secret = (isset($arr['client_secret']) ? $arr['client_secret'] : null);
		
		/* Generate these from Instance URL */
 		$this->json_file = $this->instance_url . '/api/swagger.json';
		$this->oauth_url = $this->instance_url . '/oauth';
	
		/* If access token is provided, then load the API, saves to session to keep loading times down */
		if($this->access_token){
			
			if(myLISession::exists('api')){
				$this->api = myLISession::load('api');
			}else{
				$this->api = new myLIAPI($this->json_file, $this->access_token, $this->debug);
				myLISession::save('api',$api);
			}
			
		}
		
	}
	
	/* Send to get Auth Code, Optional Redirect which is returned to your call back set when app was registered */
	private function get_auth_code($redirect=null){
		
		if(empty($redirect)){
			$redirect = myLIHelper::current_url();
		}
		
		if(empty($this->client_id)){
			return false;
		}
		
		header("Location: " . $this->oauth_url . '/auth/' . '?redirect=' . urlencode($redirect) . '&client_id=' . $this->client_id);
		die();
		
	}

	/* Get Access Token */
	private function get_access_token(){
		
		if(empty($this->auth_code) || empty($this->client_id) || empty($this->client_secret)){
			return false;
		}
		
		$authURL = $this->oauth_url . '/token/' . '?code=' . $this->auth_code . '&client_id=' . $this->client_id . '&client_secret=' . $this->client_secret;
		$this->set_access_token(file_get_contents($authURL));
		
	}
	
	/* Set Auth Code if was returned, call within CallBack and is in the URL params */
	public function set_auth_code(){
		
		if(isset($_GET['code'])){
			$this->set_auth_code($_GET['code']);
		}
	}
	
	/* Log Out */
	function logout(){
		myLISession::kill_all();
	}
	
	/* Log In */
	function login(){
		
		if($this->access_token_valid){
			return true;
		}elseif(isset($this->auth_code)
			$this->get_access_token();
			return true;
		{
			$this->get_auth_code();
		}
	}	
		
	/* Set the access token */
	public function set_access_token($access_token){
		
		myLISession::save('access_token',$access_token);
		$this->access_token = $access_token;
		$this->api->access_token = $access_token;
    
	}
			
	/* Check the current access token is valid */
	function access_token_valid(){
		
		if(isset($this->access_token) && $this->api->oAuth->isaccesstokenvalid->query(array('accessToken'=>$this->access_token))){
			return true;
		}else{
			return false;
		}
		
	}	
	
	/* Pulls access token owners basic profile */
	function get_user_profile(){

		if(!myLISession::exists('user_profile')){
			$this->user_profile = $this->api->me->userprofile->query();
            myLISession::save('user_profile',$this->user_profile );
		}
		$this->user_profile = myLISession::load('user_profile');
		return $this->user_profile;
		
	}
 
	/* Pulls access token owners account basic profile */ 
    function get_account_profile(){
        
        if(!myLISession::exists('account_profile')){
			$this->account_profile = $this->api->me->accountprofile->query();
            myLISession::save('account_profile',$this->accountprofile );
		}
		$this->account_profile = myLISession::load('account_profile');
		return $this->account_profile;
        
    }
 
	/* Pulls access token owners current account membership details */ 
    function get_account_membership(){

		if(!myLISession::exists('account_membership')){
			$this->account_membership = $this->api->me->accountmembership->query();
			myLISession::save('account_membership',$this->account_membership );
		}
	
		$this->account_membership = myLISession::load('account_membership');
		return $this->account_membership;
		
	}	
	
	/* Pulls access token owners current membership details */
	function get_user_membership(){

		if(!myLISession::exists('user_membership')){
			$this->user_membership = $this->api->me->usermembership->query();
			myLISession::save('user_membership',$this->user_membership );
		}
	
		$this->user_membership = myLISession::load('user_membership');
		return $this->user_membership;
		
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

class myLIAPI {
	
	public function __construct($json_file,$access_token=null,$debug=false){

		$this->json_file = $json_file;
		$this->access_token = $access_token;
		$this->debug = $debug;
		$this->get_api_resources();
	
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

    public function __set(string $var, $value) {
        
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
	
			if(!myLISession::exists('json')){
	
				$json = file_get_contents($this->json_file);
				myLISession::save('json',$json);
			}
			
			$obj = json_decode(myLISession::load('json'));
					
			if(!property_exists($obj,'openapi'))
				return false;
			
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
        $baseURL = parse_url($this->json_file);	
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

?>
