<?php

require('DBreeze/autoload.php');

$result = Users::select()
->join('products', 'users.id', '=', 'products.user_id')
->join('admins', 'users.id', '=', 'admins.user_id')
->run();

echo json_encode($result);


