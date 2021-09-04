<?php

$attributes = [
    "client_id",
    "client_secret",
    "webhook_secret",
    "webhook_callback_url",
    "user_id",
    "db_connection"
];

$config = [];

foreach ($attributes as $attribute) {
    $config[$attribute] = getenv($attribute);
};

$config_file = __DIR__ . "/../config.json";
file_put_contents($config_file, json_encode($config));
