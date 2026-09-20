<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class PasswordReset {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    /**
     * Auto-ensure password_resets table exists without needing manual migrations
     */
    private function ensureTableExists(): void {
        if (!$this->db) return;
        try {
            $sql = "CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->exec($sql);
        } catch (\PDOException $e) {
            // For SQLite or non-MySQL fallback
            try {
                $sql = "CREATE TABLE IF NOT EXISTS password_resets (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT NOT NULL,
                    token TEXT NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )";
                $this->db->exec($sql);
            } catch (\PDOException $e2) {
                error_log("Failed to create password_resets table: " . $e2->getMessage());
            }
        }
    }

    /**
     * Generate and store a secure reset token for an email address (valid for 60 minutes)
     */
    public function createToken(string $email): string {
        if (!$this->db) return '';
        try {
            // Remove previous tokens for this email to avoid duplicates
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
            $stmt->execute(['email' => $email]);

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

            $stmt = $this->db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
            $stmt->execute([
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);

            return $token;
        } catch (\PDOException $e) {
            error_log("PasswordReset createToken error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Verify that a token exists for this email and has not expired
     */
    public function verifyToken(string $email, string $token): ?array {
        if (!$this->db || empty($email) || empty($token)) return null;
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE email = :email AND token = :token AND expires_at > :now LIMIT 1");
            $stmt->execute([
                'email' => $email,
                'token' => $token,
                'now' => $now
            ]);
            $record = $stmt->fetch();
            return $record ?: null;
        } catch (\PDOException $e) {
            error_log("PasswordReset verifyToken error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete tokens for an email once reset is successfully finished
     */
    public function deleteToken(string $email): bool {
        if (!$this->db) return false;
        try {
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = :email");
            return $stmt->execute(['email' => $email]);
        } catch (\PDOException $e) {
            error_log("PasswordReset deleteToken error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean expired tokens
     */
    public function cleanExpired(): void {
        if (!$this->db) return;
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE expires_at <= :now");
            $stmt->execute(['now' => $now]);
        } catch (\PDOException $e) {
            error_log("PasswordReset cleanExpired error: " . $e->getMessage());
        }
    }
}
