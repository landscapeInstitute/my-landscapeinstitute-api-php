
# MyLI oAuth and API Class

## Introduction

This library allows Apps using client ID's and Secret or the use of personal access tokens to access the MyLI API. This library is for users who are intergrating services to MyLI. All MyLI users can access their own data by use of personal access tokens. 
Apps must be setup and approved by the Landscape Institute Directly. All API's are rate limited. 

When a user connects using an APP, they will be asked to give expressed permission for your app to use the data it has requested. All app actions are logged. 

## Setup

`require('myLI.php');`

## Scenario 1, Im using a client ID and Secret and my own app

For apps which have been issued a client ID and secret, these require the user to request a refresh token which your app can exchange for an access token. This is for basic usage, full documention on request. 

### Initialise With a Client ID and Client Secret

    $myLI = new myLI(array(
    		'client_id'=>'APP_CLIENT_ID'
    		'client_secret'=>'APP_CLIENT_SECRET'
    		'instance_url'=>'SANDBOX_OR_LIVE_INSTANCE_URL'
    
    ));
    
    /* Send User to get a refresh token for our app*/
    if(!$myLI->refresh_token_valid() ){
    	$myLI->get_refresh_token();
    }
    
    /* Refresh token valid but we do not have an access token */
    if($myLI->refresh_token_valid() && !myLISession::exists('access_token')){
    
    	$myLI->get_access_token();
        
    }
    
    /* Access token is valid */
    if($myLI->access_token_valid()){
    
    	$user_profile = $myLI->get_user_profile();
    	$user_membership = $myLI->get_user_membership();
    
    }


## Scenario 2, Im using a personal access token 

Generated from dashboard, personal access tokens are tied to a single account

### Initialise With a Personal Access Token

    $myLI = new myLI(array(
    		'access_token'=>'ACCESS_TOKEN',
    		'instance_url'=>'https://my.landscapeinstitute.org'
    ));
    		
    if($myLI->access_token_valid()){
    
    	$my_profile = $myLI->get_user_profile();
    	$my_membership = $myLI->get_membership();
    	
    }

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
