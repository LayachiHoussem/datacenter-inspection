<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class User {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT id, name, email, role, status, avatar, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function all(): array {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT id, name, email, role, status, avatar, created_at FROM users ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function create(array $data): int|bool {
        if (!$this->db) return false;
        try {
            $sql = "INSERT INTO users (name, email, password, role, status, avatar) 
                    VALUES (:name, :email, :password, :role, :status, :avatar)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'role' => $data['role'] ?? ROLE_INSPECTOR,
                'status' => $data['status'] ?? 'active',
                'avatar' => $data['avatar'] ?? null
            ]);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("User create error: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool {
        if (!$this->db) return false;
        try {
            $fields = ["name = :name", "email = :email", "role = :role", "status = :status"];
            $params = [
                'id' => $id,
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'status' => $data['status']
            ];

            if (!empty($data['password'])) {
                $fields[] = "password = :password";
                $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update user password securely by email address
     */
    public function updatePasswordByEmail(string $email, string $newPassword): bool {
        if (!$this->db) return false;
        try {
            $stmt = $this->db->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE email = :email");
            return $stmt->execute([
                'email' => $email,
                'password' => password_hash($newPassword, PASSWORD_BCRYPT)
            ]);
        } catch (\PDOException $e) {
            error_log("User updatePasswordByEmail error: " . $e->getMessage());
            return false;
        }
    }
}
