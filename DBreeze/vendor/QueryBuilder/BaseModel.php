<?php

class BaseModel
{
    protected static $table;
    protected static $tableStructure;
    protected static $db;

    // Inject DB instance
    public static function setDBInstance($db)
    {
        self::$db = $db;
    }

    // Ensure we set the correct table in every method
    protected static function table()
    {
        return self::$db->table(static::$table); // Use static:: to refer to the correct table name in child classes
    }

    // Get table structure operations (e.g., repair, drop, update)
    public static function TableStructure()
    {
        return new TableRegistry(static::$table, static::$tableStructure, self::$db);
    }

    // Forward update calls to DB
    public static function update($data, $conditions)
    {
        return self::table()->update($data, $conditions);
    }

    // Forward select calls to DB
    public static function select($columns = '*')
    {
        return self::table()->select($columns);
    }

    // Forward delete calls to DB
    public static function delete($conditions)
    {
        return self::table()->delete($conditions);
    }

    // Forward insert calls to DB
    public static function insert($data)
    {
        return self::table()->insert($data);
    }

    // Allow other DB methods like where(), limit(), etc.
    public static function __callStatic($name, $arguments)
    {
        // Forward any other methods to the DB instance, ensuring we set the correct table first
        return call_user_func_array([self::table(), $name], $arguments);
    }
}

// Set the DB instance for the BaseModel (all dynamically created models will use this DB instance)
BaseModel::setDBInstance($db);