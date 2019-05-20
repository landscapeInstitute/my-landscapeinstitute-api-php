# MyLI oAuth and API Class

## Setup

`require('myLI.php');`

## Scenario 1, Im using a client ID and Secret and my own app

For apps which have been issued a client ID and secret, these require the user to request a refresh token which your app can exchange for an access token. This is for basic usage, full documention on request. 

`
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

`

## Scenario 2, Im using a personal access token 

Generated from dashboard, personal access tokens are tied to a single account

## Initialise With a Personal Access Token

`
$myLI = new myLI(array(
		'access_token'=>'ACCESS_TOKEN',
		'instance_url'=>'https://my.landscapeinstitute.org'
));
		
if($myLI->access_token_valid()){

	$my_profile = $myLI->get_user_profile();
	$my_membership = $myLI->get_membership();
	
}
`
