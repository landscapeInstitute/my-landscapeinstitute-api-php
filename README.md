


## MyLI oAuth and API Class

### Introduction

This library allows Apps using client ID's and Secret or the use of personal access tokens to access the MyLI API. This library is for users who are integrating services to MyLI. 
- All MyLI users can access their own data by use of personal access tokens. 
- Apps must be setup and approved by the Landscape Institute Directly. All API's are rate limited. 

When a user connects using an APP, they will be asked to give expressed permission for your app to use the data it has requested. All app actions are logged. 


## Setup and usage

You must initialise the class with given options as part of the construction of the class. These should be provided as an array using these key names
#### `access_token` *optional* but required if you are using a personal access token
#### `client_id`*optional* but required if you are requesting app access tokens
#### `client_secret` *optional* but required if you are requesting app access tokens
#### `instance_url` *required* the instance you are connecting to as a full URL
#### `debug` *optional* enables curl debug mode for API calls if is true. 



### Scenario 1 
#### I'm using a client ID and Secret and my own app

- App Access Token and oAuth Login
- Assumes your registered callback URL is login.php

### login.php

	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}	 

 	require('my-landscapeinstitute-api.php.php');
 
    $myLI = new myLI(array(
    		'client_id'=>'APP_CLIENT_ID'
    		'client_secret'=>'APP_CLIENT_SECRET'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL',
    		'access_token' => (isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null),
    ));
	
	if(!$myLI->has_access_token()){
		$_SESSION['access_token'] = $myLI->get_access_token();
	}else{
		header("Location: \profile.php");
	}


### profile.php

	
 	require('my-landscapeinstitute-api.php.php');	
		
	if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}	 

 	require('my-landscapeinstitute-api.php.php');
 
    $myLI = new myLI(array(
    		'client_id'=>'APP_CLIENT_ID'
    		'client_secret'=>'APP_CLIENT_SECRET'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL',
    		'access_token' => (isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null),
    ));
	
    /* Access token is valid */
    if($myLI->has_access_token()){
    
    	$user_profile = $myLI->get_user_profile();
    	$user_membership = $myLI->get_user_membership();
    
    }else{
		header("Location: \login.php");
	}
	
	echo $user_profile->first_name;
	echo $user_profile->last_name;
	

### Scenario 2, 
#### I'm using a personal access token from my dashboard
- You have generated a personal access token from your dashboard
- You only need to access your own data. 

### profile.php

	if (session_status() == PHP_SESSION_NONE) {
				session_start();
			}	 

 	require('my-landscapeinstitute-api.php.php');
 
    $myLI = new myLI(array(
    		'client_id'=>'APP_CLIENT_ID'
    		'client_secret'=>'APP_CLIENT_SECRET'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL',
    		'access_token' => (isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null),
    ));
	
    /* Access token is valid */
    if($myLI->has_access_token()){
    
    	$user_profile = $myLI->get_user_profile();
    	$user_membership = $myLI->get_user_membership();
    
    }
	
	echo $user_profile->first_name;
	echo $user_profile->last_name;


## Session Variables

This Library does not save anything to session. The app should handle saving the access token to session and adding back into the library on following page loads. 
The simplest way to do this is to save the response from get_access_token to session.

For example

`

if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}	
	
	$myLI = new myLI(array(
    		'client_id'=>'APP_CLIENT_ID'
    		'client_secret'=>'APP_CLIENT_SECRET'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL',
			'access_token' => (isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null),
    ));	
	
	$_SESSION['access_token'] = $myLI->get_access_token();
`
 
 You could instead save access token to a database and retreve. 

If you do not handle sessions then your access token is lost on next page load and will need to be called again.  The means the access token is retreved from the API server on every load and will result in a performance decrease on your site. 
 

## Raw API Actions

You can directly call the API for more advanced usage by using the API object

    /* Returns Profile */
    $myLI->api->me->userProfile->query();
    
    /* Returns Boolean */
    $myLI->api->me->hasPermission->query(array('permissionID'=>'A_PERMISSION'));

We recommend you check out the API explorer on the instance of MyLI you are connecting to to understand the endpoints and arguments required. 

## Extending this class

If you extend this class and your settings for example are coming from a database or other source
you should generate your config within your class constructor and pass your config array to a method called setup().

For example

    class myli_new extends myli{
    
	    function __construct(){
	    
		    $arr = array(
			    'access_token' => '12345',
		    )
		    
		    $this->setup($arr);

	    }
    }

Extending allows you to do things such as automatically saving your access token by creating your own "set_access_token" function

## Git Hooks

If making changes to the main branch be sure to use the included hooks to auto increment the version 
