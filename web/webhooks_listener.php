<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../get_config.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('name');
$log->pushHandler(new StreamHandler(__DIR__ . '/../log/webhooks_listener.log', Logger::DEBUG));


function verify_message($log, $headers, $body, $webhook_secret) {
    /*
    hmac_message = headers['Twitch-Eventsub-Message-Id'] + headers['Twitch-Eventsub-Message-Timestamp'] + request.body
    signature = hmac_sha256(webhook_secret, hmac_message)
    expected_signature_header = 'sha256=' + signature.hex()

    if headers['Twitch-Eventsub-Message-Signature'] != expected_signature_header:
        return 403
    */

    $hmac_message = $headers['Twitch-Eventsub-Message-Id'] . $headers['Twitch-Eventsub-Message-Timestamp'] . rtrim($body);
    $signature = hash_hmac('sha256',$hmac_message, $webhook_secret);
    $expected_signature_header = 'sha256=' . $signature;

    return ($headers['Twitch-Eventsub-Message-Signature'] == $expected_signature_header);
}

function insert_raid($log, $message_timestamp, $data, $db_connection) {
    try {
      $dbh = new PDO($db_connection);
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $dbh->beginTransaction();
      $stmt = $dbh->prepare(
          "INSERT INTO raids (message_timestamp, from_broadcaster_user_id, from_broadcaster_user_login, from_broadcaster_user_name, to_broadcaster_user_id, to_broadcaster_user_login, to_broadcaster_user_name, viewers) " . 
          "VALUES (:message_timestamp, :from_broadcaster_user_id, :from_broadcaster_user_login, :from_broadcaster_user_name, :to_broadcaster_user_id, :to_broadcaster_user_login, :to_broadcaster_user_name, :viewers)");
      $stmt->bindParam(':message_timestamp',           $message_timestamp);
      $stmt->bindParam(':from_broadcaster_user_id',    $data->event->from_broadcaster_user_id);
      $stmt->bindParam(':from_broadcaster_user_login', $data->event->from_broadcaster_user_login);
      $stmt->bindParam(':from_broadcaster_user_name',  $data->event->from_broadcaster_user_name);
      $stmt->bindParam(':to_broadcaster_user_id',      $data->event->to_broadcaster_user_id);
      $stmt->bindParam(':to_broadcaster_user_login',   $data->event->to_broadcaster_user_login);
      $stmt->bindParam(':to_broadcaster_user_name',    $data->event->to_broadcaster_user_name);
      $stmt->bindParam(':viewers',                     $data->event->viewers);

      $stmt->execute();
      $dbh->commit();
      $dbh = null;

    } catch (PDOException $e) {
      print "Error!: " . $e->getMessage() . "\n";
      $log->critical($e->getMessage());
      $dbh->rollBack();
    } catch (Exception $e) {
      print "Error!: " . $e->getMessage() . "\n";
      $log->critical($e->getMessage());
      $dbh->rollBack();
    }

}

// Get config
$config = get_config();
$webhook_secret = $config->webhook_secret;
$db_connection = $config->db_connection;

// Check for webhook_callback_verification
$headers = apache_request_headers();

$message_type = array_key_exists("Twitch-Eventsub-Message-Type", $headers) ? $headers["Twitch-Eventsub-Message-Type"] : "";
$subscription_type = array_key_exists("Twitch-Eventsub-Subscription-Type", $headers) ? $headers["Twitch-Eventsub-Subscription-Type"] : "";
$message_timestamp = array_key_exists("Twitch-Eventsub-Message-Timestamp", $headers) ? $headers["Twitch-Eventsub-Message-Timestamp"] : "";

$log->info("Request received with Twitch-Eventsub-Message-Type=" . $message_type);

if ($message_type == "webhook_callback_verification")
{
    $body = file_get_contents('php://input');
    $is_valid = verify_message($log, $headers, $body, $webhook_secret);
    $log->info("is_valid: $is_valid");
    if ($is_valid)
    {
        // Respond with the value of the challenge field
        $data = json_decode($body);
        $challenge = $data->challenge;
        print $challenge;
        $log->info("Responded with challenge=$challenge");
    } else {
        http_response_code(403);
        die();
    }
} elseif ($message_type == "notification" && $subscription_type == "channel.raid" ) {
    $body = file_get_contents('php://input');
    $is_valid = verify_message($log, $headers, $body, $webhook_secret);
    $log->info("is_valid: $is_valid");
    if ($is_valid)
    {
        $data = json_decode($body);
        // Insert into DB
        insert_raid($log, $message_timestamp, $data, $db_connection);
        $log->info("Inserted into db: message_timestamp: " . $message_timestamp . " from: " . $data->event->from_broadcaster_user_name . " to: " . $data->event->to_broadcaster_user_login);
    } else {
        http_response_code(403);
        die();
    }
} else {
    $log->info("Unprocessed request, returning 404");
    http_response_code(404);
    ?>
    <html>
      <head>
        <title>Not found</title>
      </head>
      <body>
        <h1>Not found</h1>
      </body>
    </html>
    <?php
    die();
}