<?php

class TableRegistry
{
    private $tableName;
    private $tableStructure;
    private $db;
    private $backupDir = 'table_backups/';

    public function __construct($tableName, $tableStructure, $db)
    {
        $this->tableName = $tableName;
        $this->tableStructure = $tableStructure;
        $this->db = $db;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    private function tableExists()
    {
        $query = "SHOW TABLES LIKE '{$this->tableName}'";
        $result = $this->db->runQuery($query);
        return $result->rowCount() > 0;
    }

    private function getCurrentTableStructure()
    {
        $query = "SHOW COLUMNS FROM {$this->tableName}";
        $stmt = $this->db->runQuery($query);
        $currentStructure = [];

        while ($row = $stmt->fetch()) {
            $currentStructure[$row['Field']] = $row['Type'];
        }

        return $currentStructure;
    }

    private function saveStructureToFile($structure)
    {
        $filePath = $this->backupDir . "{$this->tableName}_structure_backup.json";
        file_put_contents($filePath, json_encode($structure, JSON_PRETTY_PRINT));
        echo "Backup saved to {$filePath}\n";
    }

    public function create()
    {
        if (!$this->tableExists()) {
            
            $columns = [];
            foreach ($this->tableStructure as $column => $definition) {
                $columns[] = "$column $definition";
            }
            $columnsString = implode(", ", $columns);
            $createQuery = "CREATE TABLE {$this->tableName} ($columnsString)";
            $this->db->runQuery($createQuery);

            echo "Table {$this->tableName} created successfully.\n";

            $this->saveStructureToFile($this->tableStructure);
        } else {
            
            $this->update();
        }

        return $this;
    }

    public function update($newStructure = [])
    {
        
        if (!empty($newStructure)) {
            $this->tableStructure = array_merge($this->tableStructure, $newStructure);
        }

        $currentStructure = $this->getCurrentTableStructure();

        $this->saveStructureToFile($currentStructure);

        $alterStatements = [];

        foreach ($this->tableStructure as $column => $definition) {
            $isPrimaryKey = strpos(strtoupper($definition), 'PRIMARY KEY') !== false;
            $isAutoIncrement = strpos(strtoupper($definition), 'AUTO_INCREMENT') !== false;

            if (!isset($currentStructure[$column])) {
                $alterStatements[] = "ADD COLUMN $column $definition";
            } else {

                $currentDefinition = strtolower($currentStructure[$column]);
                $newDefinition = strtolower(str_replace('primary key', '', $definition));

                if ($currentDefinition !== trim($newDefinition)) {
                    if ($isPrimaryKey) {
                        $definition = str_replace('PRIMARY KEY', '', $definition);
                    }
                    $alterStatements[] = "MODIFY COLUMN $column $definition";
                }
            }
        }

        foreach ($currentStructure as $column => $definition) {
            if (!isset($this->tableStructure[$column])) {
                $alterStatements[] = "DROP COLUMN $column";
            }
        }

        if (!empty($alterStatements)) {
            $query = "ALTER TABLE {$this->tableName} " . implode(", ", $alterStatements);
            $this->db->runQuery($query);
            echo "Table {$this->tableName} updated successfully.\n";

            $this->saveStructureToFile($this->tableStructure);
        } else {
            echo "No changes detected for table {$this->tableName}.\n";
        }

        return $this;
    }

    public function repair()
    {
        echo "Repairing table {$this->tableName}\n";
        
        return $this;
    }

    public function drop()
    {
        $query = "DROP TABLE IF EXISTS {$this->tableName}";
        $this->db->runQuery($query);
        echo "Dropped table {$this->tableName}\n";
        return $this;
    }

    public function getStructure()
    {
        return $this->tableStructure;
    }
}

