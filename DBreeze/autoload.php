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

require_once (__DIR__ . '/vendor/Settings/Settings.php');

require_once (__DIR__ . '/config/database/mysqli_connect.php');

require_once (__DIR__ . '/vendor/QueryBuilder/DB.php');

require_once (__DIR__ . '/vendor/QueryBuilder/BaseModel.php');

require_once (__DIR__ . '/vendor/QueryBuilder/Registry.php');

require_once (__DIR__ . '/vendor/QueryBuilder/TableRegistry.php');