<?php
// app/models/Roles.php

require_once 'Model.php';

class Roles extends Model {
    
    protected $table = 'roles';
    protected $primaryKey = 'ID_ROLES'; // CORRECTION: ID_ROLES avec S
    
    // Champs autorisés pour création/mise à jour
    protected $fillable = [
        'DESCRIPTION_ROLE',
        'STATUT_ROLE'
    ];
    
    /**
     * Rechercher des rôles par nom ou description
     */
    public function search($query) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (DESCRIPTION_ROLE LIKE :query)
                AND STATUT_ROLE = 1
                ORDER BY DESCRIPTION_ROLE";
        
        $stmt = $this->db->prepare($sql);
        $searchQuery = "%{$query}%";
        $stmt->bindParam(':query', $searchQuery);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}