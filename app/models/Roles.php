<?php
// app/models/Roles.php

require_once 'Model.php';

class Roles extends Model {
    
    protected $table = 'roles';
    protected $primaryKey = 'ID_ROLES'; // CORRECTION: ID_ROLES avec S
    
    // Champs autorisés pour création/mise à jour
    // protected $fillable = [
    //     'DESCRIPTION_ROLE',
    //     'STATUT_ROLE'
    // ];

     // 🔥 Récupérer un roles avec toutes les relations
    public function findWithDetails($id)
    {
        $sql = "SELECT 
                    r.ID_ROLES,
                    r.DESCRIPTION_ROLE,
                    r.STATUT_ROLE
                FROM roles r
                WHERE r.ID_ROLES = :id
                AND r.STATUT_ROLE = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔥 Récupérer la liste des rôles
        public function getRolesList()
        {
            $sql = "SELECT 
                        r.ID_ROLES,
                        r.DESCRIPTION_ROLE,
                        r.STATUT_ROLE
                    FROM roles r
                    WHERE r.STATUT_ROLE = 1
                    ORDER BY r.ID_ROLES ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    // 🔥 Pagination 
    public function paginateWithDetails($page = 1, $limit = 10, $search = null)
    {
        $page  = max((int)$page, 1);
        $limit = max((int)$limit, 1);
        $offset = ($page - 1) * $limit;

        $where = "WHERE r.STATUT_ROLE = 1";
        $params = [];

        // 🔍 Recherche
        if (!empty($search)) {
            $where .= " AND r.DESCRIPTION_ROLE LIKE :search";
            $params[':search'] = "%$search%";
        }

        // 🔥 DATA
        $sql = "SELECT 
                    r.ID_ROLES,
                    r.DESCRIPTION_ROLE,
                    r.STATUT_ROLE
                FROM roles r
                $where
                ORDER BY r.DESCRIPTION_ROLE ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // bind dynamique
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 🔥 TOTAL
        $countSql = "SELECT COUNT(*) FROM roles r $where";
        $countStmt = $this->db->prepare($countSql);

        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }

        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'roles' => $roles,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Rechercher des rôles par nom ou description
     */
    public function search($query)
    {
        $sql = "SELECT 
                    ID_ROLES,
                    DESCRIPTION_ROLE,
                    STATUT_ROLE
                FROM {$this->table}
                WHERE DESCRIPTION_ROLE LIKE :query
                AND STATUT_ROLE = 1
                ORDER BY DESCRIPTION_ROLE ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', "%$query%");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // public function index() {    
}