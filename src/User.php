<?php

namespace App;

class User
{
    private $db;

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function getAllUsers($sortBy = 'id', $sortOrder = 'ASC', $limit = null, $offset = null)
    {
        try {
            $validSortColumns = ['id', 'username', 'email', 'created_at', 'updated_at'];
            $sortBy = in_array($sortBy, $validSortColumns) ? $sortBy : 'id';
            $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

            $query = "SELECT * FROM users ORDER BY {$sortBy} {$sortOrder}";

            if ($limit !== null && $offset !== null) {
                $query .= " LIMIT :limit OFFSET :offset";
            }

            $stmt = $this->db->getConnection()->prepare($query);

            if ($limit !== null && $offset !== null) {
                $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception('Error fetching users: ' . $e->getMessage());
        }
    }

    public function getUserById($id)
    {
        try {
            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception('Error fetching user: ' . $e->getMessage());
        }
    }

    public function createUser($data)
    {
        try {
            $query = "INSERT INTO users (username, email, password, created_at, updated_at) 
                     VALUES (:username, :email, :password, NOW(), NOW())";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', password_hash($data['password'], PASSWORD_BCRYPT));
            $stmt->execute();
            return $this->db->getConnection()->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception('Error creating user: ' . $e->getMessage());
        }
    }

    public function updateUser($id, $data)
    {
        try {
            $updates = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, ['username', 'email', 'password'])) {
                    $updates[] = "{$key} = :{$key}";
                    if ($key === 'password') {
                        $params[":{$key}"] = password_hash($value, PASSWORD_BCRYPT);
                    } else {
                        $params[":{$key}"] = $value;
                    }
                }
            }

            if (empty($updates)) {
                throw new \Exception('No valid fields to update');
            }

            $updates[] = "updated_at = NOW()";
            $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception('Error updating user: ' . $e->getMessage());
        }
    }

    public function deleteUser($id)
    {
        try {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception('Error deleting user: ' . $e->getMessage());
        }
    }
}
