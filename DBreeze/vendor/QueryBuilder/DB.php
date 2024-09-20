<?php

class DB
{
    private $debug = false;
    private $pdo;
    private $table;
    private $query;
    private $params = [];
    private $limitClause = '';
    private $limit;
    private $order;
    private $queryType; // To store the type of query being built (select, insert, delete, etc.)
    private $logFile = 'error_log.txt'; // Path to error log file

    public function __construct($host, $dbname, $username, $password)
    {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->logError("Connection Error", $e->getMessage(), null);
            die("Connection failed: " . $e->getMessage());
        }
    }

    // Set the table we're working with
    public function table($table)
    {
        $this->table = $table;
        $this->resetQuery();
        return $this;
    }

    // Set the columns for select or distinct
    public function select($columns = '*')
    {
        $this->queryType = 'select';

        if (is_array($columns)) {
            $columns = implode(', ', $columns);
        }
        $this->query = "SELECT $columns FROM {$this->table}";
        return $this;

    }

    public function find($conditions)
    {
        // If the input is an integer, assume it's an ID and search by ID
        if (is_int($conditions)) {
            $this->query = "SELECT * FROM {$this->table} WHERE id = :id";
            $this->params['id'] = $conditions;
        }
        // If the input is an array, use it for the WHERE conditions
        elseif (is_array($conditions)) {
            $this->query = "SELECT * FROM {$this->table} WHERE ";
            $clauses = [];
            foreach ($conditions as $column => $value) {
                $clauses[] = "$column = :$column";
                $this->params[$column] = $value;
            }
            $this->query .= implode(" AND ", $clauses);
        }

        return $this;
    }

    public function distinct($column)
    {
        $this->queryType = 'select';
        $this->query = "SELECT DISTINCT $column FROM {$this->table}";
        return $this;
    }

    // Set the WHERE conditions
    public function where($conditions = [])
    {
        if (!strpos($this->query, 'WHERE')) {
            $this->query .= " WHERE ";  // Add WHERE clause if not already present
        } else {
            $this->query .= " AND ";  // Chain additional conditions with AND
        }

        $clauses = [];
        foreach ($conditions as $column => $value) {
            // Check for LIKE operations combined with OR (||)
            if (strpos($value, '||') !== false && strpos($value, '%') !== false) {
                // Split the value by '||' and create multiple LIKE conditions
                $orConditions = explode('||', $value);
                $orClauses = [];
                foreach ($orConditions as $index => $orValue) {
                    $param = $column . '_or_' . $index;
                    $orClauses[] = "$column LIKE :$param";
                    $this->params[$param] = trim($orValue);  // Bind the value with LIKE
                }
                $clauses[] = '(' . implode(' OR ', $orClauses) . ')';  // Join OR conditions
            }
            // Check for simple LIKE conditions
            elseif (strpos($value, '%') !== false) {
                $clauses[] = "$column LIKE :$column";
                $this->params[$column] = $value;
            }
            // Check for standard conditions with comparison operators
            elseif (preg_match('/(>=|<=|>|<|!=|=)/', $value, $matches)) {
                $operator = $matches[0];
                $realValue = trim(str_replace($operator, '', $value));
                $clauses[] = "$column $operator :$column";
                $this->params[$column] = $realValue;
            }
            // Handle simple equality
            else {
                $clauses[] = "$column = :$column";
                $this->params[$column] = $value;
            }
        }

        $this->query .= implode(" AND ", $clauses);
        return $this;
    }

    // Set the limit for the number of rows to retrieve
    public function limit($start, $end = null)
    {
        if ($end !== null) {
            $this->limitClause = " LIMIT $start, $end";  // Store the LIMIT clause
        } else {
            $this->limitClause = " LIMIT $start";
        }

        return $this;
    }

    // Set the order of the results
    public function order($column = 'id', $direction = 'DESC')
    {
        $this->query .= " ORDER BY $column $direction";
        return $this;
    }

    // Set the count query
    public function count($conditions = [])
    {
        $this->queryType = 'select';
        $this->query = "SELECT COUNT(*) as total FROM {$this->table}";
        $this->where($conditions);
        return $this;
    }

    // Set the sum query
    public function sum($column, $conditions = [])
    {
        $this->queryType = 'select';
        $this->query = "SELECT SUM($column) as total FROM {$this->table}";
        $this->where($conditions);
        return $this;
    }

    // Insert data into the table, query is executed in run()
    public function insert($data)
    {
        $this->queryType = 'insert';
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $this->query = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $this->params = $data;
        return $this;
    }

    // Update data in the table, query is executed in run()
    public function update($data, $conditions)
    {
        $this->queryType = 'update';

        // Automatically update the 'updated_at' column if it exists
        if ($this->columnExists('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        } else {
            // If the updated_at column doesn't exist, create it, then update
            $this->addUpdatedAtColumn();
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $setPart = implode(", ", array_map(function ($key) {
            return "$key = :$key";
        }, array_keys($data)));

        $wherePart = implode(" AND ", array_map(function ($key) {
            return "$key = :cond_$key";
        }, array_keys($conditions)));

        $this->query = "UPDATE {$this->table} SET $setPart WHERE $wherePart";

        // Merge data and conditions for binding
        $this->params = array_merge($data, array_combine(array_map(function ($key) {
            return "cond_$key";
        }, array_keys($conditions)), array_values($conditions)));
        return $this;
    }

    // Delete data from the table, query is executed in run()
    public function delete(array $conditions = [])
    {
        $this->query = "DELETE FROM {$this->table}";

        // If conditions are passed directly to delete(), handle them here
        if (!empty($conditions)) {
            $this->where($conditions);  // Use the where method to process conditions
        }

        return $this;  // Allow chaining
    }

    // IN condition method
    public function in($column, $values = [])
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->query .= " WHERE $column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    // NOT IN condition method
    public function notIn($column, $values = [])
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->query .= " WHERE $column NOT IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    // BETWEEN condition method
    public function between($column, $range = [])
    {
        if (count($range) !== 2) {
            throw new Exception("The between() method requires an array with exactly two values.");
        }

        // Check if a WHERE clause already exists, append using AND if it does
        if (strpos($this->query, 'WHERE') !== false) {
            $this->query .= " AND $column BETWEEN :{$column}_min AND :{$column}_max";
        } else {
            $this->query .= " WHERE $column BETWEEN :{$column}_min AND :{$column}_max";
        }

        // Bind the parameters for BETWEEN
        $this->params["{$column}_min"] = $range[0];
        $this->params["{$column}_max"] = $range[1];

        return $this;
    }

    // IS NULL condition method
    public function null($column)
    {
        $this->query .= " WHERE $column IS NULL";
        return $this;
    }

    // IS NOT NULL condition method
    public function notNull($column)
    {
        $this->query .= " WHERE $column IS NOT NULL";
        return $this;
    }

    // Raw SQL method (e.g., for complex conditions)
    public function raw($sql)
    {
        $this->query .= " WHERE $sql";
        return $this;
    }

    // Create table dynamically, adding created_at and updated_at if they are missing
    public function createTable($tableName, $columns)
    {
        $this->queryType = 'createTable';

        // Add created_at and updated_at if not provided
        if (!isset($columns['created_at'])) {
            $columns['created_at'] = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        }
        if (!isset($columns['updated_at'])) {
            $columns['updated_at'] = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        }

        $columnDefs = [];
        foreach ($columns as $name => $type) {
            $columnDefs[] = "$name $type";
        }

        $columnDefsString = implode(", ", $columnDefs);

        $this->query = "CREATE TABLE IF NOT EXISTS $tableName ($columnDefsString)";
        return $this;
    }

    // Check if a column exists in the current table
    private function columnExists($columnName)
    {
        $query = "SHOW COLUMNS FROM {$this->table} LIKE :column";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['column' => $columnName]);
        return $stmt->fetch() !== false;
    }

    // Add updated_at column to the current table if it doesn't exist
    private function addUpdatedAtColumn()
    {
        $query = "ALTER TABLE {$this->table} ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $this->pdo->exec($query); // Directly execute the query
    }

    // Method to log errors in a file
    private function logError($query, $errorMessage, $params = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] Error: $errorMessage\n";
        $logMessage .= "Query: $query\n";
        if (!empty($params)) {
            $logMessage .= "Parameters: " . json_encode($params) . "\n";
        }
        $logMessage .= "Possible Issue: There might be a syntax error, missing table, or incorrect data types.\n";
        $logMessage .= "Possible Solution: Please check the query structure, table existence, and data types.\n";
        $logMessage .= "---------------------------\n";

        // Append the log message to the file, create the file if it doesn't exist
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    // Final method to execute the query after it's built
    public function run()
    {
        // If no ORDER BY clause has been added, set a default order
        if (stripos($this->query, 'ORDER BY') === false) {
            $this->order('id', 'DESC');  // Default ordering by id DESC
        }

        // Append the LIMIT clause at the end, if it exists
        if (!empty($this->limitClause)) {
            $this->query .= $this->limitClause;
        }

        // If debugging is enabled, log the query
        if ($this->debug) {
            $logFile = __DIR__ . '/query_log.txt';  // Log file path

            // Construct the log message
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Executing Query: " . $this->query . PHP_EOL;
            $logMessage .= "With Params: " . json_encode($this->params) . PHP_EOL;

            // Log the message to the file
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }

        $stmt = $this->pdo->prepare($this->query);
        $stmt->execute($this->params);

        // Handle different query types
        if (stripos($this->query, 'SELECT') === 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (stripos($this->query, 'COUNT') !== false) {
            return $stmt->fetchColumn();
        } elseif (stripos($this->query, 'DELETE') === 0 || stripos($this->query, 'UPDATE') === 0 || stripos($this->query, 'INSERT') === 0) {
            return $stmt->rowCount() > 0;  // Return true if rows were affected
        }

        return false;
    }

    // Helper to reset query state
    private function resetQuery()
    {
        $this->query = '';
        $this->params = [];
        $this->limit = null;
        $this->order = 'id DESC'; // Default order by id in descending order
        $this->queryType = ''; // Reset the query type
    }

    public function runQuery($query)
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
}

// Initialize the database connection
$db = new DB($db_host, $db_name, $db_user, $db_pass);
