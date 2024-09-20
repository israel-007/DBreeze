<?php

// Load all table class files dynamically
$folder = glob(__DIR__ . '/tables' . '/*');

foreach ($folder as $file) {

    require_once($file);

}