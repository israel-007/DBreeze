<?php

// Set maximum execution time to 300 seconds (5 minutes)
ini_set('max_execution_time', '300');

// Set maximum input time to 300 seconds (5 minutes)
ini_set('max_input_time', '300');

// Set memory limit to 128M
ini_set('memory_limit', '128M');

// Set maximum file upload size to 20M
ini_set('upload_max_filesize', '20M');

// Set maximum post size to 22M
ini_set('post_max_size', '22M');

require_once (__DIR__ . '/bootstrap/main.php');

require_once (__DIR__ . '/bootstrap/app.php');

require_once (__DIR__ . '/database/mysqli_connect.php');

require_once (__DIR__ . '/queryBuilder/DB.php');

require_once (__DIR__ . '/queryBuilder/BaseModel.php');

require_once (__DIR__ . '/queryBuilder/Registry.php');

require_once (__DIR__ . '/queryBuilder/TableRegistry.php');


$folder = glob(__DIR__ . '/bootstrap' . '/*');

$i = 0;

foreach ($folder as $file) {

    require_once ($file);

}

if (!APP::GetCookie('app_session') or empty(APP::GetCookie('app_session'))) {

    $app_session = APP::uniqueId(12);

    // Cookie to expire in 1 year
    APP::Cookie('app_session', $app_session, 3600 * 24 * 31 * 12);

} else {

    // Initiate cookie again with same value

    $app_session = APP::GetCookie('app_session');

    // Cookie to expire in 1 year
    APP::Cookie('app_session', $app_session, 3600 * 24 * 31 * 12);

}