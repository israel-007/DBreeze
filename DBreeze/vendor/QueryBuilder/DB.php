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
    private $queryType;
    private $logFile = 'error_log.txt';

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

    public function table($table)
    {
        $this->table = $table;
        $this->resetQuery();
        return $this;
    }

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
        
        if (is_int($conditions)) {
            $this->query = "SELECT * FROM {$this->table} WHERE id = :id";
            $this->params['id'] = $conditions;
        }
        
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

    public function where($conditions = [])
    {
        if (!strpos($this->query, 'WHERE')) {
            $this->query .= " WHERE ";
        } else {
            $this->query .= " AND ";
        }

        $clauses = [];
        foreach ($conditions as $column => $value) {
            
            if (strpos($value, '||') !== false) {
                $orConditions = explode('||', $value);
                $orClauses = [];
                foreach ($orConditions as $index => $orValue) {
                    $param = "{$column}_or_{$index}";
                    if (strpos($orValue, '%') !== false) {
                        
                        $orClauses[] = "$column LIKE :$param";
                    } else {
                        
                        $orClauses[] = "$column = :$param";
                    }
                    $this->params[$param] = trim($orValue);
                }
                $clauses[] = '(' . implode(' OR ', $orClauses) . ')';

            } elseif (strpos($value, '%') !== false) {
                
                $clauses[] = "$column LIKE :$column";
                $this->params[$column] = $value;

            } elseif (preg_match('/(>=|<=|>|<|!=|=)/', $value, $matches)) {
                
                $operator = $matches[0];
                $realValue = trim(str_replace($operator, '', $value));
                $clauses[] = "$column $operator :$column";
                $this->params[$column] = $realValue;

            } else {
                
                $clauses[] = "$column = :$column";
                $this->params[$column] = $value;
            }
        }

        $this->query .= implode(" AND ", $clauses);
        return $this;
    }

    public function limit($start, $end = null)
    {
        if ($end !== null) {
            $this->limitClause = " LIMIT $start, $end";
        } else {
            $this->limitClause = " LIMIT $start";
        }

        return $this;
    }

    public function order($column = 'id', $direction = 'DESC')
    {
        $this->query .= " ORDER BY $column $direction";
        return $this;
    }

    public function count($conditions = [])
    {
        $this->queryType = 'select';
        $this->query = "SELECT COUNT(*) as total FROM {$this->table}";
        $this->where($conditions);
        return $this;
    }

    public function sum($column, $conditions = [])
    {
        $this->queryType = 'select';
        $this->query = "SELECT SUM($column) as total FROM {$this->table}";
        $this->where($conditions);
        return $this;
    }

    public function insert($data)
    {
        $this->queryType = 'insert';
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $this->query = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $this->params = $data;
        return $this;
    }

    public function update($data, $conditions)
    {
        $this->queryType = 'update';

        if ($this->columnExists('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        } else {
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

        $this->params = array_merge($data, array_combine(array_map(function ($key) {
            return "cond_$key";
        }, array_keys($conditions)), array_values($conditions)));
        return $this;
    }

    public function delete(array $conditions = [])
    {
        $this->query = "DELETE FROM {$this->table}";

        if (!empty($conditions)) {
            $this->where($conditions);
        }

        return $this;
    }

    public function in($column, $values = [])
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->query .= " WHERE $column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function notIn($column, $values = [])
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->query .= " WHERE $column NOT IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function between($column, $range = [])
    {
        if (count($range) !== 2) {
            throw new Exception("The between() method requires an array with exactly two values.");
        }

        if (strpos($this->query, 'WHERE') !== false) {
            $this->query .= " AND $column BETWEEN :{$column}_min AND :{$column}_max";
        } else {
            $this->query .= " WHERE $column BETWEEN :{$column}_min AND :{$column}_max";
        }

        $this->params["{$column}_min"] = $range[0];
        $this->params["{$column}_max"] = $range[1];

        return $this;
    }

    public function null($column)
    {
        $this->query .= " WHERE $column IS NULL";
        return $this;
    }

    public function notNull($column)
    {
        $this->query .= " WHERE $column IS NOT NULL";
        return $this;
    }

    public function createTable($tableName, $columns)
    {
        $this->queryType = 'createTable';

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

    private function columnExists($columnName)
    {
        $query = "SHOW COLUMNS FROM {$this->table} LIKE :column";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['column' => $columnName]);
        return $stmt->fetch() !== false;
    }

    private function addUpdatedAtColumn()
    {
        $query = "ALTER TABLE {$this->table} ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $this->pdo->exec($query);
    }

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

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    public function run()
    {
        if (stripos($this->query, 'ORDER BY') === false) {
            $this->order('id', 'DESC');
        }

        if (!empty($this->limitClause)) {
            $this->query .= $this->limitClause;
        }

        if ($this->debug) {
            $logFile = __DIR__ . '/query_log.txt';

            $logMessage = "[" . date('Y-m-d H:i:s') . "] Executing Query: " . $this->query . PHP_EOL;
            $logMessage .= "With Params: " . json_encode($this->params) . PHP_EOL;

            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }

        $stmt = $this->pdo->prepare($this->query);
        $success = $stmt->execute($this->params);

        if (stripos(trim($this->query), 'SELECT') === 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        elseif (preg_match('/^SELECT\s+COUNT/i', trim($this->query))) {
            return $stmt->fetchColumn();
        }
        
        elseif (stripos(trim($this->query), 'DELETE') === 0 || stripos(trim($this->query), 'UPDATE') === 0) {
            return $stmt->rowCount() > 0;
        }
        
        elseif (stripos(trim($this->query), 'INSERT') === 0) {
            return $success ? $this->pdo->lastInsertId() : false;
        }

        return false;
    }

    private function resetQuery()
    {
        $this->query = '';
        $this->params = [];
        $this->limit = null;
        $this->order = 'id DESC';
        $this->queryType = '';
    }

    public function runQuery($query)
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function raw($sql)
    {
        $this->query .= " WHERE $sql";
        return $this;
    }
    
}

$db = new DB($db_host, $db_name, $db_user, $db_pass);
