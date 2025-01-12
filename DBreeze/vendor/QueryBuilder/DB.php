<?php

class DB
{
    private $debug;
    private $pdo;
    private $table;
    public $query;
    private $params = [];
    private $limitClause = '';
    private $limit;
    private $order;
    private $queryType;
    private $hasJoin = false;
    private $joinClauses = [];
    private $joinedTables = [];
    private $selectedColumns = '*';
    private $logFile = 'error_log.txt';

    public function __construct($host, $dbname, $username, $password, $debug_dbreeze)
    {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->debug = filter_var($debug_dbreeze, FILTER_VALIDATE_BOOLEAN);
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
        $this->selectedColumns = $columns;
        return $this;

    }

    public function buildSelect()
    {
        if ($this->queryType === 'select') {
            $columns = $this->selectedColumns;

            if ($columns === '*') {
                // Handle select all columns
                if ($this->hasJoin) {
                    $columns = [];
                    $columns = array_merge($columns, $this->getPrefixedColumns($this->table));
                    foreach ($this->joinedTables as $table) {
                        $columns = array_merge($columns, $this->getPrefixedColumns($table));
                    }
                    $columns = implode(', ', $columns);
                } else {
                    $columns = '*';  // No join, use simple wildcard
                }
            } elseif (is_array($columns)) {
                if ($this->hasJoin) {
                    $columns = array_map(function ($column) {
                        return strpos($column, '.') === false ? "{$this->table}.$column" : $column;
                    }, $columns);
                }
                $columns = implode(', ', $columns);
            }

            // Build the SELECT clause
            $this->query = "SELECT $columns FROM {$this->table}";

            // Append JOIN clauses if any
            foreach ($this->joinClauses as $joinClause) {
                $this->query .= " " . $joinClause;
            }
        }
    }

    private function getPrefixedColumns($table)
    {
        $stmt = $this->pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_map(function ($column) use ($table) {
            return "$table.$column AS `{$table}_$column`";
        }, $columns);
    }

    public function find($conditions)
    {
        $this->buildSelect();
        if ($this->queryType !== 'select') {
            $this->queryType = 'select';
        }

        if (is_int($conditions)) {
            // Assume conditions as primary table's ID
            $column = "{$this->table}.id";
            $this->query .= " WHERE $column = :id";
            $this->params['id'] = $conditions;
        } elseif (is_array($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                $bindingColumn = str_replace('.', '_', $column);
                $clauses[] = "$column = :$bindingColumn";
                $this->params[$bindingColumn] = $value;
            }
            $this->query .= " WHERE " . implode(" AND ", $clauses);
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

        $this->buildSelect();

        if (!strpos($this->query, 'WHERE')) {
            $this->query .= " WHERE ";
        } else {
            $this->query .= " AND ";
        }

        $clauses = [];
        foreach ($conditions as $column => $value) {

            $bidingColumn = str_replace('.', '_', $column);
            
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
                
                $clauses[] = "$column LIKE :$bidingColumn";
                $this->params[$bidingColumn] = $value;

            } elseif (preg_match('/(>=|<=|>|<|!=|=)/', $value, $matches)) {
                
                $operator = $matches[0];
                $realValue = trim(str_replace($operator, '', $value));
                $clauses[] = "$column $operator :$bidingColumn";
                $this->params[$bidingColumn] = $realValue;

            } else {
                
                $clauses[] = "$column = :$bidingColumn";
                $this->params[$bidingColumn] = $value;
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
        $this->buildSelect();

        $bindingColumn = str_replace('.', '_', $column);

        if (count($range) !== 2) {
            throw new Exception("The between() method requires an array with exactly two values.");
        }

        if (strpos($this->query, 'WHERE') !== false) {
            $this->query .= " AND $column BETWEEN :{$bindingColumn}_min AND :{$bindingColumn}_max";
        } else {
            $this->query .= " WHERE $column BETWEEN :{$bindingColumn}_min AND :{$bindingColumn}_max";
        }

        $this->params["{$bindingColumn}_min"] = $range[0];
        $this->params["{$bindingColumn}_max"] = $range[1];

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

    public function join($table, $firstColumn, $operator, $secondColumn, $type = 'INNER')
    {
        $this->hasJoin = true;
        $this->joinedTables[] = $table;  // Track joined table name
        $this->joinClauses[] = "$type JOIN $table ON $firstColumn $operator $secondColumn";
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
    private function groupJoinResults(array $result)
    {
        if (empty($result)) {
            return $result;
        }

        $groupedResult = [];
        $mainTableAlias = str_replace('.', '_', $this->table);
        $joinedTableAliases = array_map(function ($table) {
            return str_replace('.', '_', $table);
        }, $this->joinedTables);

        $primaryKey = "{$mainTableAlias}_id";

        foreach ($result as $row) {
            $userId = $row[$primaryKey] ?? null;

            if ($userId === null) {
                continue; // Skip if no primary key found for main table
            }

            if (!isset($groupedResult[$userId])) {
                $groupedResult[$userId] = $this->filterAndRenameColumns($row, $mainTableAlias);
                foreach ($joinedTableAliases as $alias) {
                    $groupedResult[$userId][$alias] = [];  // Initialize empty arrays for joined tables
                }
            }

            foreach ($joinedTableAliases as $alias) {
                $nestedData = $this->filterAndRenameColumns($row, $alias);
                if (!empty($nestedData) && !in_array($nestedData, $groupedResult[$userId][$alias], true)) {
                    $groupedResult[$userId][$alias][] = $nestedData;
                }
            }
        }

        return array_values($groupedResult);
    }

    private function filterAndRenameColumns(array $row, string $alias)
    {
        $filtered = array_filter($row, function ($key) use ($alias) {
            return strpos($key, "{$alias}_") === 0;
        }, ARRAY_FILTER_USE_KEY);

        return array_combine(
            array_map(function ($key) use ($alias) {
                return substr($key, strlen($alias) + 1);
            }, array_keys($filtered)),
            $filtered
        );
    }

    private function prepareQuery()
    {
        if (stripos($this->query, 'WHERE') === false) {
            $this->buildSelect();
        }
    }

    private function applyOrderBy()
    {
        if ($this->hasJoin && stripos($this->query, 'ORDER BY') === false) {
            $this->order($this->table . '.id', 'DESC');
        }

        if (stripos($this->query, 'ORDER BY') === false) {
            $this->order('id', 'DESC');
        }
    }

    private function applyLimit()
    {
        if (!empty($this->limitClause)) {
            $this->query .= $this->limitClause;
        }
    }

    private function logQuery()
    {
        if ($this->debug) {
            $logFile = __DIR__ . '../../../../query_log.txt';
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Executing Query: " . $this->query . PHP_EOL;
            $logMessage .= "With Params: " . json_encode($this->params) . PHP_EOL.'';
            $logMessage .= "\n---------------------------\n\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }

    private function processResult($stmt, $success)
    {
        $query = trim($this->query);

        if (stripos($query, 'SELECT') === 0) {
            return $this->hasJoin ? $this->groupJoinResults($stmt->fetchAll(PDO::FETCH_ASSOC)) : $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (preg_match('/^SELECT\s+COUNT/i', $query)) {
            return $stmt->fetchColumn();
        } elseif (stripos($query, 'DELETE') === 0 || stripos($query, 'UPDATE') === 0) {
            return $stmt->rowCount() > 0;
        } elseif (stripos($query, 'INSERT') === 0) {
            return $success ? $this->pdo->lastInsertId() : false;
        }
        return false;
    }

    public function run()
    {
        $this->prepareQuery();
        $this->applyOrderBy();
        $this->applyLimit();

        $this->logQuery();

        $stmt = $this->pdo->prepare($this->query);
        $success = $stmt->execute($this->params);

        return $this->processResult($stmt, $success);
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
        $this->query .= $sql;
        return $this;
    }
    
}

$db = new DB($db_host, $db_name, $db_user, $db_pass, $debug_dbreeze);
