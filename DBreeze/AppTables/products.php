<?php

class Products extends BaseModel
{
    // Define the table name
    protected static $table = 'products';

    // Define the table structure
    protected static $tableStructure = [
        'id' => 'INT(100) AUTO_INCREMENT PRIMARY KEY',
        'user_id' => 'varchar(100) NOT NULL',
        'product' => 'varchar(100) NOT NULL',
        'quantity' => 'varchar(100) NOT NULL',
        'type' => 'varchar(100) NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
}