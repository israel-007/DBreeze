<?php
// All your custom PHP code goes here

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception("The .env file does not exist: $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Set the environment variable
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Call the env function immediately
loadEnv(__DIR__ . '/../../config/app.env');

// Serve the settings based on HTTP_HOST
if ($_SERVER['HTTP_HOST'] == 'localhost' or $_SERVER['HTTP_HOST'] == '127.0.0.1' or $_SERVER['HTTP_HOST'] == '192.168.43.96') {

    // DATABASE SETTINGS
    $db_host = getenv('LOCAL_DB_HOST');
    $db_name = getenv('LOCAL_DB_NAME');
    $db_user = getenv('LOCAL_DB_USER');
    $db_pass = getenv('LOCAL_DB_PASS');

    // PAYSTACK SETTINGS
    $ENV_PAYSTACK = getenv('LOCAL_PAYSTACK');


}else{

    // DATABASE SETTINGS
    $db_host = getenv('LIVE_DB_HOST');
    $db_name = getenv('LIVE_DB_NAME');
    $db_user = getenv('LIVE_DB_USER');
    $db_pass = getenv('LIVE_DB_PASS');

    // PAYSTACK SETTINGS
    $ENV_PAYSTACK = getenv('LIVE_PAYSTACK');


}
