<?php

function get_config() {
  // Get data from config file (if it exists)
  $file_config = [];
  if (file_exists(__DIR__ . "/config.json")) {
    $file_config = json_decode(file_get_contents(__DIR__ . "/config.json"), true);
  }

  // Get any data from environment variables (overrides any from config file)
  $env_config = [];
  $env_keys = [
    "client_id",
    "client_secret",
    "webhook_secret",
    "webhook_callback_url",
    "user_id",
    "db_connection"
  ];
  foreach ($env_keys as $key) {
    if (isset($_ENV[$key])) $env_config[$key] = $_ENV[$key];
  }

  $config = array_merge($file_config, $env_config);

  return (object)$config;
}