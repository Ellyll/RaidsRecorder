<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../get_config.php';

$config = get_config();
$twitch_client_id = $config->client_id;
$twitch_client_secret = $config->client_secret;
$twitch_scopes = '';

$webhook_secret = $config->webhook_secret;
$webhook_callback_url = $config->webhook_callback_url;
$user_id = $config->user_id;

$helixGuzzleClient = new \NewTwitchApi\HelixGuzzleClient($twitch_client_id);
$newTwitchApi = new \NewTwitchApi\NewTwitchApi($helixGuzzleClient, $twitch_client_id, $twitch_client_secret);
$oauth = $newTwitchApi->getOauthApi();

try {
    $token = $oauth->getAppAccessToken($twitch_scopes ?? '');
    $data = json_decode($token->getBody()->getContents());

    // Your bearer token
    $twitch_access_token = $data->access_token ?? null;

    // public function createEventSubSubscription(string $bearer, string $secret, string $callback, string $type, string $version, array $condition): ResponseInterface
    $responseForTo = $newTwitchApi->getEventSubApi()->createEventSubSubscription($twitch_access_token, $webhook_secret, $webhook_callback_url, "channel.raid", "1", ["to_broadcaster_user_id" => $user_id]);
    $responseContentForTo = $responseForTo->getBody()->getContents();
    $responseForFrom = $newTwitchApi->getEventSubApi()->createEventSubSubscription($twitch_access_token, $webhook_secret, $webhook_callback_url, "channel.raid", "1", ["from_broadcaster_user_id" => $user_id]);
    $responseContentForFrom = $responseForFrom->getBody()->getContents();

    print("TO:\n$responseContentForTo\n");
    print("FROM:\n$responseContentForFrom\n");

} catch (Exception $e) {
    print "ERROR!\n$e\n";
}
