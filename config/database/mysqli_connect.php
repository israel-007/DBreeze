<?php 
{
    DEFINE('DB_HOST', $db_host);
    DEFINE('DB_NAME', $db_name);
    DEFINE('DB_USER', $db_user);
    DEFINE('DB_PASSWORD', $db_pass);
}

$dbcon = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)
    or die('Could not connect to MySQL: ' . mysqli_connect_error());

mysqli_set_charset($dbcon, 'utf8');

date_default_timezone_set('Africa/Lagos');

// session_start();

ob_start();