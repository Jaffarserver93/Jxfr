<?php
// Include your DB connection here if not already done
require_once '_config.php'; // Make sure this sets up $conn

function importDatabaseIfMissing($conn, $sqlFilePath) {
    // List one or more essential tables to check for
    $requiredTables = ['users', 'comments'];

    // Check if at least one required table exists
    $existingTables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_row()) {
            $existingTables[] = $row[0];
        }
    }

    // Check if any required table is missing
    $missing = false;
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            $missing = true;
            break;
        }
    }

    // If missing, import the SQL file
    if ($missing) {
        if (!file_exists($sqlFilePath)) {
            die("SQL file not found: " . htmlspecialchars($sqlFilePath));
        }

        $sql = file_get_contents($sqlFilePath);
        if ($conn->multi_query($sql)) {
            do {
                // Flush multi_query results
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());

            echo "✅ Database imported successfully from {$sqlFilePath}";
        } else {
            die("❌ Error importing database: " . $conn->error);
        }
    } else {
        echo "✔️ Database already initialized.";
    }
}

// Call it (adjust path if needed)
importDatabaseIfMissing($conn, __DIR__ . '/database.sql');
