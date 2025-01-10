<?php

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        exit("The .env file does not exist: $filePath");
    } else {

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../../app.env');

if ($_SERVER['HTTP_HOST'] == 'localhost' or $_SERVER['HTTP_HOST'] == '127.0.0.1' or $_SERVER['HTTP_HOST'] == '192.168.43.96') {

    // DATABASE SETTINGS
    $db_host = getenv('LOCAL_DB_HOST');
    $db_name = getenv('LOCAL_DB_NAME');
    $db_user = getenv('LOCAL_DB_USER');
    $db_pass = getenv('LOCAL_DB_PASS');

}else{

    // DATABASE SETTINGS
    $db_host = getenv('LIVE_DB_HOST');
    $db_name = getenv('LIVE_DB_NAME');
    $db_user = getenv('LIVE_DB_USER');
    $db_pass = getenv('LIVE_DB_PASS');

}

// Declare other env properties

$database_tables_location = getenv('DATABASE_TABLES_LOCATION');