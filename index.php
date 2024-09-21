<?php

require('DBreeze/autoload.php');

// Users::TableStructure()->create();

// Admins::TableStructure()->create();

// $user = Users::select()
// ->where(['id' => '> 1'])
// ->between('id', [1, 6])
// ->order()
// ->limit(5)
// ->run();

// $user = Users::update(
//     ['first_name' => 'Jane'],
//     ['id' => '4']
// )->run();

// $users = Users::update(['username' => 'John Doe'], ['id' => 1])->run();

// $users = $db->table('users')->select('*')->run();

$users = $db->table('users')->select()->where(['id' => '> 1'])->between('id', [3, 10])->order('id', 'ASC')->limit(5)->run();

// echo json_encode($users);

$valid = APP::validate([
    'oluyemi' => 'required|true, length|4'
]);




