<?php

require('DBreeze/autoload.php');

$result = Users::select()
->join('products', 'users.id', '=', 'products.user_id')
->between('users.id', [2,4])
->run();

echo json_encode($result);


