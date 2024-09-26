<?php

require('DBreeze/autoload.php');

$result = Users::select()->run();

print_r($result);