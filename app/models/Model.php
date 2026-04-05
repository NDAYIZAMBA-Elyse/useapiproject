<?php
class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // SET TABLE
    public function setTable($table) {
        $this->table = $table;
        return $this;
    }
    
    // CREATE
    public function create($data) {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        return $stmt->execute() ? $this->db->lastInsertId() : false;
    }

      /**
     * Mettre à jour avec clé primaire par défaut
     */
    public function update($id, $data, $idField = null) {
        $field = $idField ?: $this->primaryKey;
        
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$field} = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    // DELETE (soft delete)
    public function delete($id, $idField = 'id') {
        return $this->update($id, ['deleted' => 1], $idField);
    }
    
    // HARD DELETE
    public function hardDelete($id, $idField = 'id') {
        $sql = "DELETE FROM {$this->table} WHERE {$idField} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
    
    // GET ALL (avec filtres)
    public function getAll($conditions = [], $orderBy = null, $limit = null) {
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $sql = "SELECT * FROM {$this->table} {$whereClause}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // GET ONE
    public function getOne($conditions) {
        $whereParts = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $whereParts[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereParts) . " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer par un champ spécifique (générique)
     */
     public function find($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]); // Méthode plus simple avec execute()
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // COUNT
    public function count($conditions = []) {
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    // EXISTS
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    // CUSTOM QUERY
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // CUSTOM QUERY SINGLE
    public function queryOne($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // PAGINATION
    public function paginate($page = 1, $perPage = 10, $conditions = [], $orderBy = 'id DESC') {
        $offset = ($page - 1) * $perPage;
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        // Total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Data
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    // Exemple de méthode spécifique
    public function suspendre($id) {
        return $this->update($id, ['statut' => 1]);
    }
}