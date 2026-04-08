<?php
// app/models/Epargne.php

require_once 'Model.php';

class Epargne extends Model {

    protected $table = 'cotisations';
    protected $primaryKey = 'ID_COTISATION';

    // ===============================
    // 🔥 DETAIL AVEC RELATIONS
    // ===============================
    public function findWithDetails($id)
    {
        $sql = "SELECT 
                    c.ID_COTISATION,
                    c.MONTANT_COTISE,
                    c.ASSISATANCE,
                    c.DATE_COTISE,
                    c.CUMURE_COTISATION,
                    c.CUMURE_ASSISTANCE,
                    c.COMMENT,

                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES,

                    fait.NOM_MEMBRES AS FAIT_PAR

                FROM cotisations c

                LEFT JOIN membres m ON m.ID_MEMBRES = c.MEMBRE_ID
                LEFT JOIN membres fait ON fait.ID_MEMBRES = c.FAIT_PAR

                WHERE c.ID_COTISATION = :id
                AND c.STATUT_COTISE = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ===============================
    // 🔥 PAGINATION + SEARCH
    // ===============================
    public function paginateWithDetails($page = 1, $limit = 10, $search = null)
    {
        $page  = max((int)$page, 1);
        $limit = max((int)$limit, 1);
        $offset = ($page - 1) * $limit;

        $where = "WHERE c.STATUT_COTISE = 1";
        $params = [];

        // 🔍 SEARCH (nom ou prenom)
        if (!empty($search)) {
            $where .= " AND (m.NOM_MEMBRES LIKE :search OR m.PRENOM_MEMBRES LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // 🔥 DATA
        $sql = "SELECT 
                    c.ID_COTISATION,
                    c.MONTANT_COTISE,
                    c.ASSISATANCE,
                    c.DATE_COTISE,
                    c.CUMURE_COTISATION,
                    c.CUMURE_ASSISTANCE,
                    c.COMMENT,

                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES,

                    fait.NOM_MEMBRES AS FAIT_PAR

                FROM cotisations c

                LEFT JOIN membres m ON m.ID_MEMBRES = c.MEMBRE_ID
                LEFT JOIN membres fait ON fait.ID_MEMBRES = c.FAIT_PAR

                $where
                ORDER BY c.DATE_COTISE DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 🔥 COUNT (IMPORTANT: sans JOIN inutile)
        $countSql = "SELECT COUNT(*) FROM cotisations c WHERE c.STATUT_COTISE = 1";
        $countStmt = $this->db->prepare($countSql);

        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'epargnes' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    // ===============================
    // 🔥 SEARCH SIMPLE
    // ===============================
    public function search($query)
    {
        $sql = "SELECT 
                    c.ID_COTISATION,
                    c.MONTANT_COTISE,
                    c.DATE_COTISE,
                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES
                FROM cotisations c
                LEFT JOIN membres m ON m.ID_MEMBRES = c.MEMBRE_ID
                WHERE (m.NOM_MEMBRES LIKE :query OR m.PRENOM_MEMBRES LIKE :query)
                AND c.STATUT_COTISE = 1
                ORDER BY c.DATE_COTISE DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', "%$query%");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===============================
    // 🔥 GET CUMULS BY MEMBRE (NOUVEAU)
    // ===============================
    
    /**
     * Récupère les cumuls (cotisation et assistance) d'un membre
     * 
     * @param int $membre_id
     * @return array ['CUMURE_COTISATION' => xxx, 'CUMURE_ASSISTANCE' => xxx]
     */
    public function getCumulsByMembre($membre_id)
    {
        $sql = "SELECT 
                    COALESCE(SUM(MONTANT_COTISE), 0) AS CUMURE_COTISATION, 
                    COALESCE(SUM(ASSISATANCE), 0) AS CUMURE_ASSISTANCE 
                FROM cotisations 
                WHERE MEMBRE_ID = :membre_id 
                AND STATUT_COTISE = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':membre_id', $membre_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'CUMURE_COTISATION' => (float)($result['CUMURE_COTISATION'] ?? 0),
            'CUMURE_ASSISTANCE' => (float)($result['CUMURE_ASSISTANCE'] ?? 0)
        ];
    }

    // ===============================
    // 🔥 GET CUMULS WITH DETAILS (AVEC DÉTAILS DU MEMBRE)
    // ===============================
    
    /**
     * Récupère les cumuls d'un membre avec ses informations
     * 
     * @param int $membre_id
     * @return array
     */
    public function getCumulsWithDetails($membre_id)
    {
        $sql = "SELECT 
                    m.ID_MEMBRES,
                    m.NOM_MEMBRES,
                    m.PRENOM_MEMBRES,
                    m.TELEPHONE,
                    m.EMAIL_MEMBRES,
                    COALESCE(SUM(c.MONTANT_COTISE), 0) AS CUMURE_COTISATION,
                    COALESCE(SUM(c.ASSISATANCE), 0) AS CUMURE_ASSISTANCE,
                    COUNT(c.ID_COTISATION) AS NOMBRE_COTISATIONS
                FROM membres m
                LEFT JOIN cotisations c ON c.MEMBRE_ID = m.ID_MEMBRES AND c.STATUT_COTISE = 1
                WHERE m.ID_MEMBRES = :membre_id
                AND m.STATUT_MEMBRES = 1
                GROUP BY m.ID_MEMBRES
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':membre_id', $membre_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ===============================
    // 🔥 CREATE AVEC MISE À JOUR DES CUMULS
    // ===============================
    
    /**
     * Crée une cotisation et met à jour les cumuls automatiquement
     * 
     * @param array $data
     * @return int|false
     */
    public function createWithCumuls($data)
    {
        // Récupérer les cumuls actuels du membre
        $cumuls = $this->getCumulsByMembre($data['MEMBRE_ID']);
        
        // Calculer les nouveaux cumuls
        $data['CUMURE_COTISATION'] = $cumuls['CUMURE_COTISATION'] + ($data['MONTANT_COTISE'] ?? 0);
        $data['CUMURE_ASSISTANCE'] = $cumuls['CUMURE_ASSISTANCE'] + ($data['ASSISATANCE'] ?? 0);
        
        // Insérer la nouvelle cotisation
        return $this->create($data);
    }

    // ===============================
    // 🔥 GET STATS PAR MEMBRE
    // ===============================
    
    /**
     * Récupère les statistiques détaillées d'un membre
     * 
     * @param int $membre_id
     * @return array
     */
    public function getStatsByMembre($membre_id)
    {
        $sql = "SELECT 
                    COUNT(*) AS total_cotisations,
                    SUM(MONTANT_COTISE) AS total_montant,
                    SUM(ASSISATANCE) AS total_assistance,
                    MAX(DATE_COTISE) AS derniere_cotisation,
                    MIN(DATE_COTISE) AS premiere_cotisation,
                    AVG(MONTANT_COTISE) AS moyenne_cotisation
                FROM cotisations 
                WHERE MEMBRE_ID = :membre_id 
                AND STATUT_COTISE = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':membre_id', $membre_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

     
/**
 * Retourne la liste des MEMBRE_ID ayant au moins
 * une cotisation enregistrée à la date donnée.
 *
 * @param  string $date  Format Y-m-d  ex: "2025-04-08"
 * @return array         ex: [1, 4, 7]
 */
public function getMembresIdsByDate(string $date): array
{
    $sql = "SELECT DISTINCT MEMBRE_ID
            FROM cotisations
            WHERE DATE(DATE_COTISE) = :date
              AND STATUT_COTISE = 1";
 
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':date' => $date]);
 
    // Retourner un tableau plat d'entiers
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0));
}
}