<?php
// app/models/User.php

require_once 'Model.php';

class User extends Model {
    
    protected $table = 'membres';
    protected $primaryKey = 'ID_MEMBRES';

    // 🔥 Récupérer un membre avec toutes les relations
    public function findWithDetails($id)
    {
        $sql = "SELECT 
                    m.ID_MEMBRES,
                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES,
                    m.TELEPHONE,
                    m.EMAIL_MEMBRES,
                    m.GENRE_MEMBRES,
                    m.NUMERO_IDENTITE,
                    m.ADRESSE_MEMBRE,
                    m.DATE_NAISSANCE,
                    m.LIEU_NAISSANCE,
                    m.PHOTO_PATH,
                    m.DATE_ADHESION,
                    m.DATE_MODIFICATION,
                    m.STATUT_MEMBRES,
                    m.COOPERATIVE_ID,

                    r.DESCRIPTION_ROLE,
                    c.NOM_COOPER,

                    ref.PRENOM_MEMBRES AS REFERENCE_MEMBRE,
                    fait.NOM_MEMBRES AS FAIT_PAR,
                    modi.NOM_MEMBRES AS MODIFIE_PAR

                FROM membres m
                LEFT JOIN roles r ON r.ID_ROLES = m.ROLE_ID
                LEFT JOIN cooperatives c ON c.ID_COOPERATIVE = m.COOPERATIVE_ID
                LEFT JOIN membres ref ON ref.ID_MEMBRES = m.REFERENCE_MEMBRE
                LEFT JOIN membres fait ON fait.ID_MEMBRES = m.FAIT_PAR
                LEFT JOIN membres modi ON modi.ID_MEMBRES = m.MODIFIE_PAR

                WHERE m.ID_MEMBRES = :id
                AND m.STATUT_MEMBRES = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCoopSociete($id)
    {
        $sql = "SELECT 
                    c.ID_COOPERATIVE,
                    c.NOM_COOPER,
                    c.NIF_COOPER,
                    c.RC_COOPER,
                    c.TELEPHONE_COOPER,
                    c.EMAIL_COOPER,
                    c.ADRESSE_COOPER,
                    c.LOGO_CAMPANY,
                    c.INTERET,
                    c.AMANDE_INT,
                    c.AMANDE_COTISATION,
                    c.AMANDE_ABS_REUNION,
                    c.AMANDE_RETAR_REUNION,
                    c.REPRESENTANT_COOP,
                    c.DATE_ACTION,
                    c.STATUT_COOPER
                FROM cooperatives c
                WHERE c.ID_COOPERATIVE = :id
                AND c.STATUT_COOPER = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCoopSocieteWithStats($id)
    {
        $sql = "SELECT 
                    c.ID_COOPERATIVE,
                    c.NOM_COOPER,
                    c.NIF_COOPER,
                    c.RC_COOPER,
                    c.TELEPHONE_COOPER,
                    c.EMAIL_COOPER,
                    c.ADRESSE_COOPER,
                    c.LOGO_CAMPANY,
                    c.INTERET,
                    c.AMANDE_INT,
                    c.AMANDE_COTISATION,
                    c.AMANDE_ABS_REUNION,
                    c.AMANDE_RETAR_REUNION,
                    c.REPRESENTANT_COOP,
                    c.DATE_ACTION,
                    c.STATUT_COOPER,
                    -- Statistiques
                    (SELECT COUNT(*) FROM membres WHERE COOPERATIVE_ID = c.ID_COOPERATIVE AND STATUT_MEMBRES = 1) AS total_membres,
                    (SELECT SUM(MONTANT_COTISE) FROM cotisations WHERE MEMBRE_ID IN (SELECT ID_MEMBRES FROM membres WHERE COOPERATIVE_ID = c.ID_COOPERATIVE) AND STATUT_COTISE = 1) AS total_cotisations,
                    (SELECT SUM(ASSISATANCE) FROM cotisations WHERE MEMBRE_ID IN (SELECT ID_MEMBRES FROM membres WHERE COOPERATIVE_ID = c.ID_COOPERATIVE) AND STATUT_COTISE = 1) AS total_assistances,
                    (SELECT COUNT(*) FROM cotisations WHERE MEMBRE_ID IN (SELECT ID_MEMBRES FROM membres WHERE COOPERATIVE_ID = c.ID_COOPERATIVE) AND STATUT_COTISE = 1) AS nombre_cotisations,
                    u.NOM_MEMBRES AS REPRESENTANT_NOM,
                    u.PRENOM_MEMBRES AS REPRESENTANT_PRENOM,
                    u.EMAIL_MEMBRES AS REPRESENTANT_EMAIL,
                    u.TELEPHONE AS REPRESENTANT_TELEPHONE
                FROM cooperatives c
                LEFT JOIN membres u ON u.ID_MEMBRES = c.REPRESENTANT_COOP
                WHERE c.ID_COOPERATIVE = :id
                AND c.STATUT_COOPER = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔥 Pagination + filtre coopérative
    public function paginateWithDetails($page = 1, $limit = 10, $cooperativeId = null)
    {
        $offset = ($page - 1) * $limit;

        $where = "WHERE m.STATUT_MEMBRES = 1";

        if ($cooperativeId) {
            $where .= " AND m.COOPERATIVE_ID = :cooperative_id";
        }

        $sql = "SELECT 
                   m.ID_MEMBRES,
                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES,
                    m.TELEPHONE,
                    m.EMAIL_MEMBRES,
                    m.GENRE_MEMBRES,
                    m.NUMERO_IDENTITE,
                    m.ADRESSE_MEMBRE,
                    m.DATE_NAISSANCE,
                    m.LIEU_NAISSANCE,
                    m.PHOTO_PATH,
                    m.DATE_ADHESION,
                    m.DATE_MODIFICATION,
                    m.STATUT_MEMBRES,
                    m.COOPERATIVE_ID,

                    r.DESCRIPTION_ROLE,
                    c.NOM_COOPER,

                    CONCAT(ref.NOM_MEMBRES, ' ', ref.PRENOM_MEMBRES) AS REFERENCE_MEMBRE,
                    CONCAT(fait.NOM_MEMBRES, ' ', fait.PRENOM_MEMBRES) AS FAIT_PAR,
                    CONCAT(modi.NOM_MEMBRES, ' ', modi.PRENOM_MEMBRES) AS MODIFIE_PAR

                FROM membres m
                LEFT JOIN roles r ON r.ID_ROLES = m.ROLE_ID
                LEFT JOIN cooperatives c ON c.ID_COOPERATIVE = m.COOPERATIVE_ID
                LEFT JOIN membres ref ON ref.ID_MEMBRES = m.REFERENCE_MEMBRE
                LEFT JOIN membres fait ON fait.ID_MEMBRES = m.FAIT_PAR
                LEFT JOIN membres modi ON modi.ID_MEMBRES = m.MODIFIE_PAR
                $where
                ORDER BY m.NOM_MEMBRES ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        if ($cooperativeId) {
            $stmt->bindValue(':cooperative_id', $cooperativeId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // total
        $countSql = "SELECT COUNT(*) FROM membres m $where";
        $countStmt = $this->db->prepare($countSql);

        if ($cooperativeId) {
            $countStmt->bindValue(':cooperative_id', $cooperativeId, PDO::PARAM_INT);
        }

        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        return [
            'data' => $data,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    // 🔥 Recherche optimisée
    public function searchWithCoop($query, $cooperativeId = null)
    {
        $sql = "SELECT 
                    m.ID_MEMBRES,
                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES,
                    m.TELEPHONE,
                    r.DESCRIPTION_ROLE,
                    c.NOM_COOPER
                FROM membres m
                LEFT JOIN roles r ON r.ID_ROLES = m.ROLE_ID
                LEFT JOIN cooperatives c ON c.ID_COOPERATIVE = m.COOPERATIVE_ID
                WHERE m.STATUT_MEMBRES = 1
                AND (
                    m.NOM_MEMBRES LIKE :q
                    OR m.PRENOM_MEMBRES LIKE :q
                    OR m.EMAIL_MEMBRES LIKE :q
                    OR m.TELEPHONE LIKE :q
                )";

        if ($cooperativeId) {
            $sql .= " AND m.COOPERATIVE_ID = :cooperative_id";
        }

        $stmt = $this->db->prepare($sql);

        $q = "%$query%";
        $stmt->bindValue(':q', $q);

        if ($cooperativeId) {
            $stmt->bindValue(':cooperative_id', $cooperativeId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifier les identifiants de connexion
     * UTILISE find() au lieu de findBy() si findBy n'existe pas
     */
    // public function checkCredentials($email, $password) {
    //     // Méthode 1: Utiliser la méthode findBy du parent (si elle existe maintenant)
    //     if (method_exists($this, 'findBy')) {
    //         $user = $this->find('EMAIL_MEMBRES', $email);
    //     } 
    //     // Méthode 2: Faire une requête directe
    //     else {
    //         $sql = "SELECT * FROM {$this->table} WHERE EMAIL_MEMBRES = :email LIMIT 1";
    //         $stmt = $this->db->prepare($sql);
    //         $stmt->bindValue(':email', $email);
    //         $stmt->execute();
    //         $user = $stmt->fetch(PDO::FETCH_ASSOC);
    //     }
        
    //     if ($user && password_verify($password, $user['PASSWORD'])) {
    //         // Ne pas renvoyer le mot de passe
    //         unset($user['PASSWORD']);
    //         return $user;
    //     }
        
    //     return false;
    // }

    public function checkCredentials($email, $password) {
        // Requête avec jointures pour récupérer toutes les informations
        $sql = "SELECT 
                    m.ID_MEMBRES,
                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES,
                    m.TELEPHONE,
                    m.EMAIL_MEMBRES,
                    m.GENRE_MEMBRES,
                    m.NUMERO_IDENTITE,
                    m.ADRESSE_MEMBRE,
                    m.DATE_NAISSANCE,
                    m.LIEU_NAISSANCE,
                    m.PHOTO_PATH,
                    m.DATE_ADHESION,
                    m.DATE_MODIFICATION,
                    m.STATUT_MEMBRES,
                    m.COOPERATIVE_ID,
                    m.PASSWORD,
                    m.ROLE_ID,
                    m.REFERENCE_MEMBRE,
                    m.FAIT_PAR,
                    m.MODIFIE_PAR,
                    m.USERNAME,
                    m.TYPE_IDENTITE_ID,  -- 🔥 AJOUTÉ : Type identité
                    -- Rôle
                    r.DESCRIPTION_ROLE,
                    
                    -- Coopérative
                    c.NOM_COOPER,
                    c.EMAIL_COOPER,
                    c.TELEPHONE_COOPER,
                    c.ADRESSE_COOPER,
                    c.LOGO_CAMPANY,
                    
                    -- Personne qui a fait l'action (création)
                    fait.NOM_MEMBRES AS FAIT_PAR_NOM,
                    fait.PRENOM_MEMBRES AS FAIT_PAR_PRENOM,
                    fait.EMAIL_MEMBRES AS FAIT_PAR_EMAIL,
                    
                    -- Personne qui a modifié
                    modi.NOM_MEMBRES AS MODIFIE_PAR_NOM,
                    modi.PRENOM_MEMBRES AS MODIFIE_PAR_PRENOM,
                    modi.EMAIL_MEMBRES AS MODIFIE_PAR_EMAIL,
                    
                    -- Référence (personne qui a parrainé)
                    ref.NOM_MEMBRES AS REFERENCE_MEMBRE_NOM,
                    ref.PRENOM_MEMBRES AS REFERENCE_MEMBRE_PRENOM,
                    ref.EMAIL_MEMBRES AS REFERENCE_MEMBRE_EMAIL

                FROM membres m
                LEFT JOIN roles r ON r.ID_ROLES = m.ROLE_ID AND r.STATUT_ROLE = 1
                LEFT JOIN cooperatives c ON c.ID_COOPERATIVE = m.COOPERATIVE_ID AND c.STATUT_COOPER = 1
                LEFT JOIN membres ref ON ref.ID_MEMBRES = m.REFERENCE_MEMBRE AND ref.STATUT_MEMBRES = 1
                LEFT JOIN membres fait ON fait.ID_MEMBRES = m.FAIT_PAR AND fait.STATUT_MEMBRES = 1
                LEFT JOIN membres modi ON modi.ID_MEMBRES = m.MODIFIE_PAR AND modi.STATUT_MEMBRES = 1
                WHERE m.EMAIL_MEMBRES = :email 
                AND m.STATUT_MEMBRES = 1
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier le mot de passe
        if ($user && password_verify($password, $user['PASSWORD'])) {
            // Supprimer le mot de passe avant de retourner
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