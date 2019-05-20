# MyLI oAuth and API Class

## Usage

require('myLI.php');

## Using with a client ID and Secret

`
$myLI = new myLI(array(
		'client_id'=>'OPTIONAL_APP_CLIENT_ID'
		'client_secret'=>'OPTIONAL_APP_CLIENT_SECRET'
		'instance_url'=>'https://my.landscapeinstitute.org'

));
`

## Using With a Personal Access Token

`
$myLI = new myLI(array(
		'access_token'=>'OPTIONAL_ACCESS_TOKEN',
		'instance_url'=>'https://my.landscapeinstitute.org'
));
`

		
