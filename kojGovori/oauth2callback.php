<?php
session_start();
require_once '../../vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('CONSOLEAPIFILE.json');
$client->setAccessType("offline");        // offline access
$client->setApprovalPrompt('force');
$client->setRedirectUri('http://nima.bg/darik/kojGovori/oauth2callback.php');
$client->addScope(Google_Service_Blogger::BLOGGER);

if (! isset($_GET['code'])) {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
  $client->authenticate($_GET['code']);
  $demo = $client->getRefreshToken();
  // get the REFRESH TOKEN and later use it
  // echo "<pre>";
  // print_r($demo);
  // echo "</pre>";
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/googletest.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
