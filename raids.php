<?php

$test_data = '
{
    "subscription": {
        "id": "f1c2a387-161a-49f9-a165-0f21d7a4e1c4",
        "type": "channel.raid",
        "version": "1",
        "status": "enabled",
        "cost": 0,
        "condition": {
            "to_broadcaster_user_id": "1337"
        },
         "transport": {
            "method": "webhook",
            "callback": "https://example.com/webhooks/callback"
        },
        "created_at": "2019-11-16T10:11:12.123Z"
    },
    "event": {
        "from_broadcaster_user_id": "1234",
        "from_broadcaster_user_login": "cool_user",
        "from_broadcaster_user_name": "Cool_User",
        "to_broadcaster_user_id": "1337",
        "to_broadcaster_user_login": "cooler_user",
        "to_broadcaster_user_name": "Cooler_User",
        "viewers": 9001
    }
}
';

$data = json_decode($test_data);

$created_at = $data->subscription->created_at;
$from = $data->event->from_broadcaster_user_login;

//print "at: $created_at from:  $from\n";

$secret = "s3cRe7";

$user_id = 85829556;

/*
hmac_message = headers['Twitch-Eventsub-Message-Id'] + headers['Twitch-Eventsub-Message-Timestamp'] + request.body
signature = hmac_sha256(webhook_secret, hmac_message)
expected_signature_header = 'sha256=' + signature.hex()

if headers['Twitch-Eventsub-Message-Signature'] != expected_signature_header:
    return 403
*/

$headers = apache_request_headers();

foreach ($headers as $header => $value) {
    echo "$header: $value <br />\n";
}