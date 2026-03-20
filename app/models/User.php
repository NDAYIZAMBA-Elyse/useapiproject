<?php
// app/models/User.php

require_once 'Model.php';

class User extends Model {
    
    protected $table = 'membres';
    protected $primaryKey = 'ID_MEMBRES';
    
    // Champs autorisés pour création/mise à jour
    protected $fillable = [
        'NOM_MEMBRES',
        'PRENOM_MEMBRES',
        'TELEPHONE',
        'GENRE_MEMBRES',
        'EMAIL_MEMBRES',
        'TYPE_IDENTITE_ID',
        'NUMERO_IDENTITE',
        'ADRESSE_MEMBRE',
        'DATE_NAISSANCE',
        'LIEU_NAISSANCE',
        'USERNAME',
        'PASSWORD',
        'ROLE_ID',
        'COOPERATIVE_ID',
        'PHOTO_PATH',
        'DATE_ADHESION',
        'DATE_MODIFICATION',
        'REFERENCE_MEMBRE',
        'FAIT_PAR',
        'STATUT_MEMBRES'
    ];
    
    /**
     * Vérifier les identifiants de connexion
     * UTILISE find() au lieu de findBy() si findBy n'existe pas
     */
    public function checkCredentials($email, $password) {
        // Méthode 1: Utiliser la méthode findBy du parent (si elle existe maintenant)
        if (method_exists($this, 'findBy')) {
            $user = $this->find('EMAIL_MEMBRES', $email);
        } 
        // Méthode 2: Faire une requête directe
        else {
            $sql = "SELECT * FROM {$this->table} WHERE EMAIL_MEMBRES = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($user && password_verify($password, $user['PASSWORD'])) {
            // Ne pas renvoyer le mot de passe
            unset($user['PASSWORD']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Rechercher des membres
     */
    public function search($query) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (NOM_MEMBRES LIKE :query 
                OR PRENOM_MEMBRES LIKE :query 
                OR EMAIL_MEMBRES LIKE :query 
                OR TELEPHONE LIKE :query
                OR REFERENCE_MEMBRE LIKE :query)
                AND STATUT_MEMBRES = 1
                ORDER BY NOM_MEMBRES, PRENOM_MEMBRES";
        
        $stmt = $this->db->prepare($sql);
        $searchQuery = "%{$query}%";
        $stmt->bindParam(':query', $searchQuery);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer avec les relations
     */
    public function withRelations($id) {
        $sql = "SELECT m.*, r.NOM_ROLE as ROLE_NAME, c.NOM_COOPERATIVE as COOPERATIVE_NAME
                FROM {$this->table} m
                LEFT JOIN roles r ON m.ROLE_ID = r.ID_ROLE
                LEFT JOIN cooperative c ON m.COOPERATIVE_ID = c.ID_COOPERATIVE
                WHERE m.ID_MEMBRES = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            unset($result['PASSWORD']);
        }
        
        return $result;
    }
    
    /**
     * Récupérer par email (alias pour findBy)
     */
    public function findByEmail($email) {
        return $this->findBy('EMAIL_MEMBRES', $email);
    }
}