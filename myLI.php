<?php
/**
* MyLI API and oAuth Class
*
* @author     Louis Varley <louisvarley@googlemail.com>
* @copyright  2019 Landscape Institute
* @license    http://www.php.net/license/3_1.txt  PHP License 3.1
*/

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
	
}

class myLI{
	
	public function __construct($arr){

		$this->client_id = (isset($arr['client_id']) ? $arr['client_id'] : null);
		$this->client_secret = (isset($arr['client_secret']) ? $arr['client_secret'] : null);
		$this->instance_url = (isset($arr['instance_url']) ? $arr['instance_url'] : null);
 		$this->access_token = (isset($arr['access_token']) ? $arr['access_token'] : myLISession::load('access_token'));
        
		$this->oAuth_url = $this->instance_url . '/oauth/' . $this->client_id;
		$this->json_file = $this->instance_url . '/api/swagger.json';
		$this->refresh_token = myLISession::load('refresh_token');
        
        if(myLISession::exists('api')){
            $this->api = myLISession::load('api');
        }else{
            $this->api = new myLIAPI($this->json_file);
            myLISession::save('api',$api);
        }
		
			
	}
	
	/* is the user authenticated */
	function is_authenticated(){
        		
		if($this->access_token_valid()){
			return true;
		}else{
			return false;
		}
		
	}
	
	/* What the refresh token */
	function set_refresh_token($refresh_token){
		
		myLISession::save('refresh_token',$refresh_token);
		$this->refresh_token = $refresh_token;
		
	}
	
	/* Set the access token */
	function set_access_token($access_token){
		
		myLISession::save('access_token',$access_token);
		$this->access_token = $access_token;
		$this->api->access_token = $access_token;
    
	}
			
	/* Is this refresh token valid */
	function refresh_token_valid(){
    
		if(isset($this->refresh_token) && $this->api->oAuth->isrefreshtokenvalid->query(array('refreshToken'=>$this->refresh_token))){
            return true;
		}else{
			return false;
		}
		
	}	

	/* Is this access Token Valid */
	function access_token_valid(){

		if(isset($this->access_token) && $this->api->oAuth->isaccesstokenvalid->query(array('accessToken'=>$this->access_token))){
			return true;
		}else{
			return false;
		}
		
	}	
	
	/* Get an Access Token */
	function get_access_token(){
        
       
 
		if(!empty($this->access_token)){		
			
			if($this->access_token_valid()){
				return $this->access_token;
			}
		}    


      
        if(!empty($this->refresh_token) && $this->refresh_token_valid()){
            
            $this->access_token = $this->api->oAuth->generateaccesstoken->query(array('clientID'=>$this->client_id,'clientSecret'=>$this->client_secret,'refreshToken'=>$this->refresh_token))->Token;
            $this->set_access_token($this->access_token);
            return $this->access_token;
            
        }else{

            $this->get_refresh_token();
        }
    
    
		
	}
	
	/* Get refresh token */
	function get_refresh_token(){
                                
		$origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $destination = $this->oAuth_url . '?origin=' . urlencode($origin);
            
		header("Location: " . $destination);
		die();
		
	}
	
	/* Pulls access token owners basic profile */
	function get_user_profile(){
		
		if(!myLISession::exists('user_profile')){
     var_dump($this->api->me->userprofile->query());
     die();
			$this->user_profile = $this->api->me->UserProfile;
            myLISession::save('user_profile',$this->user_profile );
		}
		$this->user_profile = myLISession::load('user_profile');
		return $this->user_profile;
		
	}
	
	/* Pulls access token owners current membership details */
	function get_membership(){
		
		if(!myLISession::exists('user_membership')){
			$this->user_membership = $this->api->me->getMembership->query();
			myLISession::save('user_membership',$this->user_membership );
		}
		
		$this->user_membership = myLISession::load('user_membership');
		return $this->user_membership;
		
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

        $this_method = new myLIAPIMethod();	
        
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
			'accessToken: ' . $this->accessToken,
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

class myLIAPIMethod extends myLIAPI{
	
	public $methodName;
	
	public function __construct(){
		
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
		
		$this->call($this->url, $urlQuery, $requestBody, $this->method, $this->type);
		
		try{
		

			$this->results = $this->execute();
			
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