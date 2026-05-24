<?php
/**
 * Database Configuration
 * SINAR JAYA KONVEKSI
 * 
 * File ini berisi konfigurasi koneksi database
 */

// Prevent direct access
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__, 2));
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sinar_jaya_konveksi');
define('DB_USER', 'root');
define('DB_PASS', ''); // Ganti dengan password database Anda
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'SINAR JAYA KONVEKSI');
define('APP_URL', 'http://localhost/sinar-jaya'); // Sesuaikan dengan URL Anda
define('APP_DEBUG', true); // Set false di production

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (set 0 di production)
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Database Connection Class using PDO
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Terjadi kesalahan koneksi database. Silakan hubungi administrator.");
            }
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database connection
 */
function db() {
    return Database::getInstance()->getConnection();
}

/**
 * Execute query and return all results
 */
function dbQuery($sql, $params = []) {
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw $e;
        }
        return false;
    }
}

/**
 * Execute query and return single row
 */
function dbQueryOne($sql, $params = []) {
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw $e;
        }
        return false;
    }
}

/**
 * Execute insert/update/delete query
 */
function dbExecute($sql, $params = []) {
    try {
        $stmt = db()->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw $e;
        }
        return false;
    }
}

/**
 * Get last insert ID
 */
function dbLastInsertId() {
    return db()->lastInsertId();
}

/**
 * Begin transaction
 */
function dbBeginTransaction() {
    return db()->beginTransaction();
}

/**
 * Commit transaction
 */
function dbCommit() {
    return db()->commit();
}

/**
 * Rollback transaction
 */
function dbRollback() {
    return db()->rollBack();
}
