<?php

require('DBreeze/autoload.php');

$result = Users::select()->join('products', 'users.id', '=', 'products.user_id', 'LEFT')->run();

echo json_encode($result);