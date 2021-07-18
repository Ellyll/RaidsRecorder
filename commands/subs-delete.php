<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../get_config.php';

if ( !isset($argv[1]) )
{
    echo "Needs subscription ID as parameter!\n";
    die();
}

$subscription_id = $argv[1];


$config = json_decode(file_get_contents(__DIR__ . "/../config.json"));
$twitch_client_id = $config->client_id;
$twitch_client_secret = $config->client_secret;
$twitch_scopes = '';

$helixGuzzleClient = new \NewTwitchApi\HelixGuzzleClient($twitch_client_id);
$newTwitchApi = new \NewTwitchApi\NewTwitchApi($helixGuzzleClient, $twitch_client_id, $twitch_client_secret);
$oauth = $newTwitchApi->getOauthApi();

try {
    $token = $oauth->getAppAccessToken($twitch_scopes ?? '');
    $data = json_decode($token->getBody()->getContents());

    // Your bearer token
    $twitch_access_token = $data->access_token ?? null;

    $response = $newTwitchApi->getEventSubApi()->deleteEventSubSubscription($twitch_access_token, $subscription_id);

    $responseContent = $response->getBody()->getContents();

    print("$responseContent\n");

} catch (Exception $e) {
    print "ERROR:\n$e\n";
}
