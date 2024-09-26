<?php

// Load all table class files dynamically
$folder = glob(__DIR__ . '/../../AppTables' . '/*');

foreach ($folder as $file) {

    require_once($file);

}