<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('../../vendor/json/client_secret_549188524259-29t4od85do83qu7t20h042uaagaguhek.apps.googleusercontent.com.json');
$client->setAccessType("offline");        // offline access
$client->setApprovalPrompt('force');
$client->setRedirectUri('http://nima.bg/darik/kojGovori/oauth2callback.php');
$client->addScope(Google_Service_Blogger::BLOGGER);

if ($client->isAccessTokenExpired()) {
    $client->refreshToken(""); // TODO: ADD REFRESH TOKEN HERE
    $_SESSION['access_token'] = $client->getAccessToken();
    $client->setAccessToken($_SESSION['access_token']);
}

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	// create the Google client
	$client = new Google_Service_Blogger($client);
} else {
	$redirect_uri = 'http://nima.bg/darik/kojGovori/oauth2callback.php';
	header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

// get only the first post so you can see if others posts from darik.bg and add who are missing
$posts = $client->posts->listPosts("", array("maxResults" => 1))->getItems(); //TODO: ADD BLOG ID

// GET DATA FROM DARIK-RADIO PAGE
// Get cURL resource
$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_VERBOSE => 1,
    CURLOPT_HEADER => 1,
    CURLOPT_URL => 'http://darikradio.bg/audio.list.ajax.php?showId=14&page=0'
));
// Send the request & save response to $resp
$resp = curl_exec($curl);

$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
$header = substr($resp, 0, $header_size);
$body = substr($resp, $header_size);

// echo "<pre>";
// print_r(htmlspecialchars($body));
// echo "</pre>";

$response = $body;

// Close request to clear up some resources
curl_close($curl);

$re = '/onclick=\"dl\.url\(\'(\S+)\'\)">/';

preg_match_all($re, $response, $urls);

// store all posts so when added mainten
$newPosts = [];
foreach ($urls[1] as $url) {
	// Get cURL resource
	$curl = curl_init();
	// Set some options - we are passing in a useragent too here
	curl_setopt_array($curl, array(
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_VERBOSE => 1,
	    CURLOPT_HEADER => 1,
	    CURLOPT_URL => $url
	));
	// Send the request & save response to $resp
	$resp = curl_exec($curl);

	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	$header = substr($resp, 0, $header_size);
	$body = substr($resp, $header_size);

	// echo "<pre>";
	// print_r(htmlspecialchars($body));
	// echo "</pre>";

	$response = $body;

	// Close request to clear up some resources
	curl_close($curl);

	$findTitle = '/<title>(.+)<\/title>/';
	$findAuthor = '/<a class="author".*>(.*)<\/a>/';
	$findImage = '/<img src="(\/media\/.+)" data-src=/';
	$findContent = '/<div class="rte">(.*<\/p>)<\/div>/s';
	$findmp3 = '/<audio src="(.+)"><\/audio>/';

	preg_match_all($findTitle, $response, $title);
	preg_match_all($findAuthor, $response, $author);
	preg_match_all($findImage, $response, $image);
	preg_match_all($findContent, $response, $content);
	preg_match_all($findmp3, $response, $mp3);

	$srcTitle = $title[1][0];
	$srcAuthor = $author[1][0];
	$srcMP3 = "http://darikradio.bg" . $mp3[1][0];
	$srcImage = "http://darikradio.bg" . $image[1][0];


	$content = str_replace("<p><strong>Чуйте:</strong></p>", "", $content[1][0]);
	$srcContent = "<div class='separator' style='clear: both; text-align: center;'><img border='0' src='" . $srcImage . "' height='180' width='320' /></div>" . $content . "<p>Всички права запазени Дарик Радио. <a href='" . $url . "'>Оригинален линк към поста</a></p><p>Взимането на информацията от страницата на Дарик Радио и конвертирането й в подкяст е осъществено от <a href='https://github.com/bulgariamitko/koj-govori'>Димитър Клатуров</a></p>";

	if (!empty($posts) && $srcTitle == $posts[0]->title) {
		break;
	}

	// create author
	$blogAuthor = new Google_Service_Blogger_PostAuthor();
	$blogAuthor->setDisplayName($srcAuthor);

	// create post
	$blogPost = new Google_Service_Blogger_Post();
    $blogPost->setTitle($srcTitle);
    $blogPost->setContent($srcContent);
    $blogPost->setTitleLink($srcMP3);
	$blogPost->setAuthor($blogAuthor);
	$blogPost->setImages($srcImage);

	$newPosts[] = $blogPost;

}

// add posts in order
for ($i=count($newPosts) - 1; $i >= 0; $i--) { 
	$add = $client->posts->insert("", $newPosts[$i]); //TODO: ADD BLOG ID

	// echo "<pre>";
	// print_r($add);
	// echo "</pre>";
}


echo "<h1>Posts updated...</h1>";
