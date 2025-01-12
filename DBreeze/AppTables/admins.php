<?php

class Admins extends BaseModel
{
    // Define the table name
    protected static $table = 'admins';

    // Define the table structure
    protected static $tableStructure = [
        'id' => 'INT(100) AUTO_INCREMENT PRIMARY KEY',
        'user_id' => 'varchar(100) NOT NULL',
        'username' => 'varchar(100) NOT NULL',
        'email' => 'varchar(100) NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
}