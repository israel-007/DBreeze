<?php

// Load all table class files dynamically
$folder = glob(__DIR__ . $database_tables_location . '/*');

foreach ($folder as $file) {

    require_once($file);

}