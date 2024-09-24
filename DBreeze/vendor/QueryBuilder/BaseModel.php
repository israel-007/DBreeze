<?php

class BaseModel
{
    protected static $table;
    protected static $tableStructure;
    protected static $db;

    public static function setDBInstance($db)
    {
        self::$db = $db;
    }

    protected static function table()
    {
        return self::$db->table(static::$table);
    }

    public static function TableStructure()
    {
        return new TableRegistry(static::$table, static::$tableStructure, self::$db);
    }

    public static function update($data, $conditions)
    {
        return self::table()->update($data, $conditions);
    }

    public static function select($columns = '*')
    {
        return self::table()->select($columns);
    }

    public static function delete($conditions)
    {
        return self::table()->delete($conditions);
    }

    public static function insert($data)
    {
        return self::table()->insert($data);
    }

    public static function __callStatic($name, $arguments)
    {
        
        return call_user_func_array([self::table(), $name], $arguments);
    }
}

BaseModel::setDBInstance($db);