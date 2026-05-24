<?php
/**
 * Setup Database Script for Sinar Jaya Konveksi
 * Diagnoses and fixes database issues.
 */

// Configuration
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default XAMPP password
$dbName = 'sinar_jaya_konveksi_v2';

echo "<h1>Database Setup & Diagnostic Tool</h1>";
echo "<p>Checking configuration...</p>";

try {
    // 1. Check connection to MySQL Server
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color:green'>✅ Connected to MySQL server successfully.</div>";

    // 2. Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
    if ($stmt->fetch()) {
        echo "<div style='color:green'>✅ Database '$dbName' exists.</div>";
    } else {
        echo "<div style='color:red'>❌ Database '$dbName' does NOT exist.</div>";
        echo "<p>Attempting to create database...</p>";
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<div style='color:green'>✅ Database created successfully.</div>";
    }

    // 3. Connect to the specific database
    $pdo->exec("USE `$dbName`");

    // 4. Check for key tables (just a sample)
    $tables = ['kontak', 'users', 'produk', 'kategori'];
    $missingTables = [];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "<div style='color:green'>✅ Table '$table' exists.</div>";
        } else {
            echo "<div style='color:orange'>⚠️ Table '$table' does NOT exist.</div>";
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        echo "<h2>Fixing Tables...</h2>";
        // Helper to read sql file
        $sqlFile = __DIR__ . '/database/sinar_jaya_konveksi_v2.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Determine which parts to run is hard with complex SQL file containing delimiters.
            // But basic source handling might work for XAMPP default config if no funny delimiters.
            // Actually, PDO cannot run multiple queries at once easily without configuration.
            // But we can try splitting by ';'.
            
            echo "<p>Reading SQL file from: $sqlFile</p>";
            
            // Basic split by semicolon (naive but works for simple dumps)
            // Note: This might break on semicolons inside strings/comments, but for this specific dump it should be mostly fine.
            // Better to use a robust importer if available, but for now we try basic.
            
            // Remove comments
            $lines = file($sqlFile);
            $cleanSql = '';
            foreach ($lines as $line) {
                if (strpos(trim($line), '--') === 0 || strpos(trim($line), '/*') === 0) continue;
                $cleanSql .= $line;
            }
            
            $queries = explode(';', $cleanSql);
            $success = 0;
            $fail = 0;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                
                try {
                    $pdo->exec($query);
                    $success++;
                } catch (PDOException $e) {
                    $fail++;
                     // Ignore expected errors like "table already exists" if we didn't use DROP
                    // echo "<div style='color:gray; font-size:0.8em'>Query failed: " . htmlspecialchars(substr($query, 0, 50)) . "... (" . $e->getMessage() . ")</div>";
                }
            }
            
            echo "<div style='color:green'>✅ Import completed. Executed: $success queries. Failed/Skipped: $fail.</div>";
            echo "<p>Please refresh the admin page to see if it works now.</p>";
            
        } else {
            echo "<div style='color:red'>❌ SQL file not found at $sqlFile</div>";
        }
    } else {
        echo "<h2>✅ All checks passed! System should be ready.</h2>";
    }

} catch (PDOException $e) {
    echo "<div style='color:red'>❌ CONNECTION ERROR: " . $e->getMessage() . "</div>";
    echo "<p>Please ensure XAMPP MySQL is running.</p>";
}
?>
