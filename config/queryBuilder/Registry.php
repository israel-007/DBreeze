<?php

class TableRegistry
{
    private $tableName;
    private $tableStructure;
    private $db;
    private $backupDir = 'table_backups/'; // Directory where backups are saved

    public function __construct($tableName, $tableStructure, $db)
    {
        $this->tableName = $tableName;
        $this->tableStructure = $tableStructure;
        $this->db = $db;

        // Ensure the backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    // Check if a table exists in the database
    private function tableExists()
    {
        $query = "SHOW TABLES LIKE '{$this->tableName}'";
        $result = $this->db->runQuery($query);
        return $result->rowCount() > 0;
    }

    // Get the current structure of the table from the database
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

    // Save the current table structure to a file (as a backup)
    private function saveStructureToFile($structure)
    {
        $filePath = $this->backupDir . "{$this->tableName}_structure_backup.json";
        file_put_contents($filePath, json_encode($structure, JSON_PRETTY_PRINT));
        echo "Backup saved to {$filePath}\n";
    }

    // Insert should create the table if it does not exist, else call update
    public function create()
    {
        if (!$this->tableExists()) {
            // Create the table based on the provided structure
            $columns = [];
            foreach ($this->tableStructure as $column => $definition) {
                $columns[] = "$column $definition";
            }
            $columnsString = implode(", ", $columns);
            $createQuery = "CREATE TABLE {$this->tableName} ($columnsString)";
            $this->db->runQuery($createQuery);

            echo "Table {$this->tableName} created successfully.\n";

            // Save the newly created table structure to a file
            $this->saveStructureToFile($this->tableStructure);
        } else {
            // Table exists, so call update to check for structure changes
            $this->update();
        }

        return $this;
    }

    // Update the table structure by comparing it to the current structure
    public function update($newStructure = [])
    {
        // If a new structure is provided, merge it with the existing one
        if (!empty($newStructure)) {
            $this->tableStructure = array_merge($this->tableStructure, $newStructure);
        }

        // Get the current table structure from the database
        $currentStructure = $this->getCurrentTableStructure();

        // Save the current structure to a file (before making changes)
        $this->saveStructureToFile($currentStructure);

        $alterStatements = [];

        // Check for missing or altered columns
        foreach ($this->tableStructure as $column => $definition) {
            $isPrimaryKey = strpos(strtoupper($definition), 'PRIMARY KEY') !== false;
            $isAutoIncrement = strpos(strtoupper($definition), 'AUTO_INCREMENT') !== false;

            if (!isset($currentStructure[$column])) {
                // Column doesn't exist, so we need to add it
                $alterStatements[] = "ADD COLUMN $column $definition";
            } else {
                // Column exists, check for differences in the type, ignoring primary key definition
                $currentDefinition = strtolower($currentStructure[$column]);
                $newDefinition = strtolower(str_replace('primary key', '', $definition));

                if ($currentDefinition !== trim($newDefinition)) {
                    // If the column is a primary key, do not include 'PRIMARY KEY' in the MODIFY statement
                    if ($isPrimaryKey) {
                        $definition = str_replace('PRIMARY KEY', '', $definition); // Remove 'PRIMARY KEY' keyword
                    }
                    $alterStatements[] = "MODIFY COLUMN $column $definition";
                }
            }
        }

        // Check for columns that exist in the database but are not in the defined structure
        foreach ($currentStructure as $column => $definition) {
            if (!isset($this->tableStructure[$column])) {
                // Column is in the database but not in the new structure, so we drop it
                $alterStatements[] = "DROP COLUMN $column";
            }
        }

        // If there are any alter statements, execute the ALTER TABLE query
        if (!empty($alterStatements)) {
            $query = "ALTER TABLE {$this->tableName} " . implode(", ", $alterStatements);
            $this->db->runQuery($query);
            echo "Table {$this->tableName} updated successfully.\n";

            // Save the new structure to a file after the update
            $this->saveStructureToFile($this->tableStructure);
        } else {
            echo "No changes detected for table {$this->tableName}.\n";
        }

        return $this;
    }


    // Repair the table (for example purposes)
    public function repair()
    {
        echo "Repairing table {$this->tableName}\n";
        // Add repair logic here
        return $this;
    }

    // Drop the table
    public function drop()
    {
        $query = "DROP TABLE IF EXISTS {$this->tableName}";
        $this->db->runQuery($query);
        echo "Dropped table {$this->tableName}\n";
        return $this;
    }

    // Get current table structure
    public function getStructure()
    {
        return $this->tableStructure;
    }
}

