# DBreeze

A lightweight and flexible PHP query builder for simplifying database operations like `SELECT`, `WHERE`, `LIMIT`, `ORDER BY`, `BETWEEN`, and more. The class allows you to build complex queries using method chaining, while maintaining SQL injection protection through prepared statements.

## Installation

1. Download the project files.
2. Extract the files to your server directory.
3. Include the `autoload.php` file located inside the `DBreeze` folder in your project.

## Database Settings

Define your Database

You can do this by editing the `app.env` file inside the `DBreeze/config` folder, you can find 2 seetings there, one for locan development and other for live development. This makes database definition easy either youre developing locally or you just host your project, you wont have to update the database settings every time. 

Once that is done, you can start development.

## Create Tables

Creating tables has also been made easy with DBreeze, you won't have to manually create tables and its structures, you can do all that withing your project

Example:

1. Determin your table name.
2. Determin the table structure.
3. Create a class to handle the table requests.

Inside `DBreeze/AppTables`, create a file with youre table name as the file name e.g `users.php` for the users table

Inside the file paste the following code

```php

<?php 

class Users extends BaseModel
{
    // Define the table name
    protected static $table = 'users';

    // Define the table structure
    protected static $tableStructure = [
        'id' => 'INT(100) AUTO_INCREMENT PRIMARY KEY',
        'username' => 'varchar(100) NOT NULL',
        'email' => 'varchar(100) NOT NULL',
        'password' => 'varchar(500) NOT NULL',
        'password_reset' => 'varchar(200) NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
}

?>

```

1. The class name should be your table name, and also assingn your table name to `$table`
2. `$tableStructure` should contain your desired table structure, this allows you to update or create the table and its structure within your project

Once the above has been done, goahead an rrun the below code in your `index.php`

```php

<?php 

    $create_users_table = Users::TableStructure()->create();

    print_r($create_users_table);

?>

```
This returns either true or false.

Others

`$create_users_table = Users::TableStructure()->update();` to update the table struture in database if you made any changes to the table structure in the table class.
`$create_users_table = Users::TableStructure()->drop();` to delete the table.


## Query Building

There are several ways to build queries using `DBreeze`

`SELECT`

```php

<?php

    // First example without using table classes
    $users = $db->table('users')->select('*')->run();

    print_r($users);
    exit();

    // Second example with table classes
    $users = Users::select('*')->run();

    print_r($users);
    exit();

?>

```

Both examples select all rows from the users table.

`INSERT`

```php

<?php

    $password = md5('password');

    // Insert into the users table
    $users = $db->table('users')->insert([
        'username' => 'John',
        'email' => 'john@gmail.com',
        'password' => $password
    ])->run();

    print_r($users);
    exit();

    // Insert into the users table
    $users = Users::insert([
        'username' => 'John',
        'email' => 'john@gmail.com',
        'password' => $password
    ])->run();

    print_r($users);
    exit();

?>

```

When inserting, you do not need to provide `created_at` and `updated_at` column values, those column get filled when inserting new records.


`UPDATING`

```php

<?php

    // Update the users table
    $users = Users::update(['username' => 'JohnDoe'], ['id' => 1])->run();

    print_r($users);
    exit();

?>

```
`update()` takes 2 arguments, 1st is the data to update, 2nd is the condition for updating.
The query would look like `UPDATE users SET username = 'John Doe' WHERE id = 1  ORDER BY id DESC`


`DELETE`

```php

<?php 

    // Delete from users table
    $user = Users::delete()->where(['id' => 1])->run();

?>

```

`FIND`

```php

<?php

    // Find id = 1 from the users table
    $user = Users::find(1)->run();

    // Find also takes an array
    $user = Users::find(['username' => 'yemi'])->run();

?>

```

`DISTINCT`

```php

<?php

    // Select distinct values
    $users = Users::select()->distinct('email')->run();

    // Query: SELECT DISTINCT email FROM users ORDER BY id DESC

?>

```

`BETWEEN`

```php

<?php

    // Between method takes a column name as first argument
    $users = Users::select()->between('age', [20, 30])->run();

?>

```

`LIMIT`

```php

<?php

    // Limit the results been fetched
    $users = Users::select()->limit(5)->run();

    // Limit with offset. Retrieve 5 records starting from the 10th record (offset of 10).
    $users = Users::select()->limit(10, 5)->run();


?>

```


`ORDER`

```php

<?php

    // Default order uses ORDER BY id DESC
    $users = Users::select('*')->run();

    // Specify a order using order()
    $users = Users::select()->order('username', 'ASC')->run();

    $users = Users::select()->order('username', 'DESC')->run();

?>

```

`RAW`

```php

<?php

    // Use raw SQL for more advanced queries.
    $users = Users::select()->raw("id > 5 AND username = 'yemi'")->run();

?>

```

`COUNT`

```php

<?php

    // count() takes a parameter as a condition to perform the count
    $users = Users::count(['id' => '> 1'])->run();

    // Query: SELECT COUNT(*) as total FROM users WHERE id > 1 ORDER BY id DESC

?>

```

`SUM`

```php

<?php

    // sum() takes 2 parameters, the column to sum and the condition
    $users = Users::sum('id', ['id' => '> 1'])->run();

    // Query = SELECT SUM(id) as total FROM users WHERE id > 1 ORDER BY id DESC

?>

```

`COMPARISON OPERATORS` like `>=`, `<=`, `>`, `<`, `!=`, `=`, `%value%`

```php

<?php

    // '>'
    $users = Users::select()->where(['id' => '> 4'])->run();

    // Query: SELECT * FROM users WHERE id > 4 ORDER BY id DESC

    // ------------------------------------------------------------------

    // '<'
    $users = Users::select()->where(['id' => '< 4'])->run();

    // Query: SELECT * FROM users WHERE id < 4 ORDER BY id DESC

    // ------------------------------------------------------------------

    // '<='
    $users = Users::select()->where(['id' => '<= 4'])->run();

    // Query: SELECT * FROM users WHERE id <= 4 ORDER BY id DESC

    // ------------------------------------------------------------------

    // '>='
    $users = Users::select()->where(['id' => '>= 4'])->run();

    // Query: SELECT * FROM users WHERE id >= 4 ORDER BY id DESC

    // ------------------------------------------------------------------

    // '!='
    $users = Users::select()->where(['id' => '!= 4'])->run();

    // Query: SELECT * FROM users WHERE id != 4 ORDER BY id DESC

    // ------------------------------------------------------------------

    // '='
    $users = Users::select()->where(['id' => '= 4'])->run();

    // Query: SELECT * FROM users WHERE id = 4 ORDER BY id DESC

    // ------------------------------------------------------------------

    // '%value%' => LIKE
    $users = Users::select()->where(['username' => '%yemi%'])->run();

    // Query: SELECT * FROM users WHERE user_name LIKE %yemi% ORDER BY id DESC


?>

```

From all the examples above, we can see DBreeze allows method chaining which can come in very handy and saves a lot of time when making a query and you also dont have to worry about SQL injection

`More elaborate query`

```php

<?php

    $users = Users::select()
        ->where(['id' => '> 1'])
        ->between('id', [3, 10])
        ->order('id', 'ASC')
        ->limit(5)
        ->run();

    // Query: SELECT * FROM users WHERE id > 1 AND id BETWEEN 3 AND 5 ORDER BY id ASC LIMIT 5

    $users = $db->table('users')
        ->select()
        ->where(['id' => '> 1'])
        ->between('id', [3, 10])
        ->order('id', 'ASC')
        ->limit(5)
        ->run();

    // Query: SELECT * FROM users WHERE id > 1 AND id BETWEEN 3 AND 5 ORDER BY id ASC LIMIT 5

?>

```

## Contribution

Contributions to DBreeze are welcomed! Feel free to submit pull requests or open issues if you encounter any problems or have suggestions for improvement.

## Dependencies

This project does not rely on any external dependencies, making it easy to set up and use.

## License

This project is licensed under the [MIT License](LICENSE).


# DBreeze JsonQuery

Coming soon

# DBreeze Helper Functions

Coming soon