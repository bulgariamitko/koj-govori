<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// require 'vendor/autoload.php'; // run this from command line | cronjob
require 'vendor/autoload.php'; // run this from web


$client = new Google_Client();
// run this from command line | cronjob
// $client->setAuthConfig('vendor/json/client_secret_549188524259-29t4od85do83qu7t20h042uaagaguhek.apps.googleusercontent.com.json');
// run this from web
$client->setAuthConfig('../../vendor/json/client_secret_549188524259-29t4od85do83qu7t20h042uaagaguhek.apps.googleusercontent.com.json');
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
