<?php

class Users extends BaseModel
{
    // Define the table name
    protected static $table = 'users';

    // Define the table structure
    protected static $tableStructure = [
        'id' => 'INT(100) AUTO_INCREMENT PRIMARY KEY',
        'first_name' => 'varchar(100) NOT NULL',
        'last_name' => 'varchar(100) NOT NULL',
        'email' => 'varchar(100) NOT NULL',
        'password' => 'varchar(500) NOT NULL',
        'password_reset' => 'varchar(200) NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
}