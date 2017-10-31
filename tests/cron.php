<?php

/**
 IMPORTANT!!! MUST READ!!!
 In order to get the upwork access token and secret you need to do this:
 1. Run this script in the console.
 2. You will then see something like:
Visit https://www.upwork.com/services/api/auth?oauth_token=xxxxxxxxxxx
r for further authorization
 3. Visit the url above in a browser then go back to the console and paste in the oauth_token.
 4. Hit enter. You will then see something like:
> got access token info, dump: array (
'oauth_token' => 'xxxxxx',
'oauth_token_secret' => 'xxxxxxx',
).
 These are your upwork access token and access secret.
 */

require_once("../vendor/autoload.php");
require_once("../src/IJobSearchNotifier.php");
require_once("../src/JobSearchNotifier.php");

$upwork_AccessToken = '********';
$upwork_AccessSecret = '*******';

$consumerKey = "*******";
$consumerSecret = "*******";

$jsn = new \kdaviesnz\jobsearchnotifier\JobSearchNotifier(array("php", "javascript", "wordpress"), $upwork_AccessToken, $upwork_AccessSecret, $consumerKey, $consumerSecret);



$jsn->sendMail("me@example.com");
