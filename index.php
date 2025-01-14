<?php

require('DBreeze/autoload.php');

$result = Users::select()->find(13)->run();

echo json_encode($result);


