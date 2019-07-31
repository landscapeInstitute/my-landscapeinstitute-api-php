
## MyLI oAuth and API Class

### Introduction

This library allows Apps using client ID's and Secret or the use of personal access tokens to access the MyLI API. This library is for users who are integrating services to MyLI. 
- All MyLI users can access their own data by use of personal access tokens. 
- Apps must be setup and approved by the Landscape Institute Directly. All API's are rate limited. 

When a user connects using an APP, they will be asked to give expressed permission for your app to use the data it has requested. All app actions are logged. 


### Scenario 1 
#### I'm using a client ID and Secret and my own app

- App Access Token and oAuth Login
- Assumes your registered callback URL is login.php

### login.php
 
 	require('my-landscapeinstitute-api.php.php');
 
    $myLI = new myLI(array(
    		'client_id'=>'APP_CLIENT_ID'
    		'client_secret'=>'APP_CLIENT_SECRET'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL'
    ));
	
	if(!$myLI->has_access_token()){
		$myLI->get_access_token();
	}else{
		header("Location: \profile.php");
	}


### profile.php

	
 	require('my-landscapeinstitute-api.php.php');	
	
	$myLI = new myLI(array(
    		'client_id'=>'APP_CLIENT_ID'
    		'client_secret'=>'APP_CLIENT_SECRET'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL'
    ));	
	
	$myLI->get_access_token();
	
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

 	require('my-landscapeinstitute-api.php.php');	
	
	$myLI = new myLI(array(
    		'access_token'=>'PERSONAL_ACCESS_TOKEN'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL'
    ));	
	
    /* Access token is valid */
    if($myLI->has_access_token()){
    
    	$user_profile = $myLI->get_user_profile();
    	$user_membership = $myLI->get_user_membership();
    
    }
	
	echo $user_profile->first_name;
	echo $user_profile->last_name;


## Session Variables

By default, tokens once set will persist across sessions as the class saves them to session. Multiple instances of the class with different tokens will interfere with each other. 

Complete all actions in one instance before begining and using another. 

## Raw API Actions

You can directly call the API for more advanced usage by using the API object

    /* Returns Profile */
    $myLI->api->me->userProfile->query();
    
    /* Returns Boolean */
    $myLI->api->me->hasPermission->query(array('permissionID'=>'A_PERMISSION'));

We recommend you checkout the API explorer on the instance of MyLI you are connecting to to understand the endpoints and arguements required. 

## Extending this class

If you extend this class and your settings for example are coming from a database or other source
Uou should generate your config within your class constructor and pass your config array to a method called init(). 
