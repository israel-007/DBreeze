<?php

require('DBreeze/autoload.php');

$result = Users::select()->run();

echo json_encode($result);


