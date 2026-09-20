<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private string $host = '127.0.0.1';
    private string $dbName = 'datacenter_inspection';
    private string $username = 'root';
    private string $password = '';
    private string $charset = 'utf8mb4';
    private int $port = 3306;

    private function __construct() {
        $host = getenv('DB_HOST') ?: $this->host;
        $port = getenv('DB_PORT') ?: $this->port;
        $dbName = getenv('DB_NAME') ?: $this->dbName;
        $username = getenv('DB_USER') ?: $this->username;
        $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $this->password;
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        try {
            if ($driver === 'sqlite') {
                $sqlitePath = getenv('DB_SQLITE_PATH') ?: __DIR__ . '/../../storage/database.sqlite';
                $dsn = "sqlite:" . $sqlitePath;
                $this->connection = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->connection = new PDO($dsn, $username, $password, $options);
            }
        } catch (PDOException $e) {
            // Log connection error silently or throw runtime exception
            error_log("Database Connection Error: " . $e->getMessage());
            $this->connection = null;
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): ?PDO {
        return $this->connection;
    }

    public function isConnected(): bool {
        return $this->connection !== null;
    }
}
