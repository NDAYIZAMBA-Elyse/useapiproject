<?php
require_once 'Controller.php';

class EpargneController extends Controller {

    // ===============================
    // 🔥 LISTE (READ ALL + PAGINATION)
    // ===============================
    
    public function index()
    {
        $model = $this->model('Epargne');

        $page  = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $cooperativeId = $_GET['cooperative_id'] ?? null;

        $result = $model->paginateWithDetails($page, $limit, $cooperativeId);

        $this->success($result, 'Liste des cotisations');
    }

    // ===============================
    // 🔥 DETAIL (READ ONE)
    // ===============================

    public function show($id)
    {
        $model = $this->model('Epargne');

        $data = $model->getOne([
            'ID_COTISATION' => (int)$id,
            'STATUT_COTISE' => 1
        ]);

        if ($data) {
            $this->success($data, 'Cotisation trouvée');
        } else {
            $this->error('Cotisation non trouvée', 404);
        }
    }

    // ===============================
    // 🔥 CREATE AMÉLIORÉ (avec calcul auto des cumuls)
    // ===============================
    public function store()
    {
        $input = $this->getInput();

        // Validation
        $errors = $this->validate($input, [
            'MEMBRE_ID' => 'required|numeric',
            'MONTANT_COTISE' => 'required|numeric',
            'DATE_COTISE' => 'required',
            'PRESENCE_ID' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            return $this->error($errors, 422);
        }

        $model = $this->model('Epargne');
        
        // valeurs par défaut
        $input['ASSISATANCE'] = $input['ASSISATANCE'] ?? 0;
        $input['COMMENT'] = $input['COMMENT'] ?? null;
        $input['DATE_ACTION'] =date('Y-m-d H:i:s');

        // Utiliser la nouvelle méthode qui calcule automatiquement les cumuls
        $id = $model->create($input);

        if ($id) {
            $this->success([
                'ID_COTISATION' => $id
            ], 'Cotisation créée avec succès');
        } else {
            $this->error('Erreur lors de la création');
        }
    }

    // ===============================
    // 🔥 UPDATE
    // ===============================
    public function update($id)
    {
        $input = $this->getInput();
        $model = $this->model('Epargne');

        // vérifier existence
        $exist = $model->getOne(['ID_COTISATION' => (int)$id]);

        if (!$exist) {
            return $this->error('Cotisation non trouvée', 404);
        }

        $success = $model->update($id, $input);

        if ($success) {
            $this->success(null, 'Cotisation mise à jour');
        } else {
            $this->error('Erreur lors de la mise à jour');
        }
    }

    // ===============================
    // 🔥 DELETE (SOFT DELETE)
    // ===============================
    public function destroy($id)
    {
        $model = $this->model('Epargne');

        $exist = $model->getOne(['ID_COTISATION' => (int)$id]);

        if (!$exist) {
            return $this->error('Cotisation non trouvée', 404);
        }

        $model->delete($id);

        $this->success(null, 'Cotisation supprimée');
    }

    // ===============================
    // 🔥 SEARCH
    // ===============================
    public function search()
    {
        $query = $_GET['q'] ?? '';

        if (empty($query)) {
            return $this->error('Mot clé requis', 400);
        }

        $model = $this->model('Epargne');
        $data = $model->search($query);

        $this->success($data, 'Résultat recherche');
    }

    // ===============================
    // 🔥 STATS PAR MEMBRE (NOUVEAU)
    // ===============================
    public function stats($id)
    {
        $model = $this->model('Epargne');
        $stats = $model->getStatsByMembre((int)$id);
        
        if ($stats) {
            $this->success($stats, 'Statistiques du membre');
        } else {
            $this->error('Aucune donnée trouvée', 404);
        }
    }

    // ===============================
    // 🔥 GET CUMULS BY MEMBRE (NOUVEAU)
    // ===============================
    
    /**
     * Récupère les cumuls (cotisation et assistance) d'un membre
     * Supporte deux formats d'URL:
     * - GET/POST /epargnes/cumuls/membre?MEMBRE_ID=123
     * - GET /membres/{id}/cumuls (si vous utilisez l'autre route)
     * 
     * @return json
     */

    // ===============================
    // 🔥 GET CUMULS BY MEMBRE (AMÉLIORÉ)
    // ===============================
    public function getCumulsByMembre($id = null)
    {
        // Récupérer l'ID du membre
        $membre_id = null;
        
        if ($id !== null) {
            $membre_id = (int)$id;
        }
        
        if (!$membre_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = $this->getInput();
            $membre_id = $input['MEMBRE_ID'] ?? $_POST['MEMBRE_ID'] ?? null;
        }
        
        if (!$membre_id) {
            $membre_id = $_GET['MEMBRE_ID'] ?? null;
        }
        
        if (!$membre_id) {
            return $this->error('Membre non spécifié', 400);
        }
        
        if (!is_numeric($membre_id)) {
            return $this->error('ID du membre invalide', 400);
        }
        
        $model = $this->model('Epargne');
        
        // Option 1: Juste les cumuls
        $cumuls = $model->getCumulsByMembre((int)$membre_id);
        
        // Option 2: Cumuls avec détails du membre (décommentez si besoin)
        // $cumuls = $model->getCumulsWithDetails((int)$membre_id);
        
        $this->success($cumuls, 'Cumuls récupérés avec succès');
    }

    
    // ===============================
    // 🔥 MEMBRES AYANT DÉJÀ COTISÉ AUJOURD'HUI
    // ===============================
    /**
     * Retourne la liste des IDs des membres qui ont déjà
     * enregistré au moins une cotisation à la date du jour.
     * 
     * GET /epargnes/cotises/today
     * 
     * Réponse :
     * {
     *   "success": true,
     *   "data": { "membres_ids": [1, 4, 7, ...] },
     *   "message": "Membres ayant cotisé aujourd'hui"
     * }
     */
    public function getMembresCoticesToday()
    {
        $model = $this->model('Epargne');
        $today = date('Y-m-d');

        $ids = $model->getMembresIdsByDate($today);

        $this->success(
            ['membres_ids' => $ids],
            'Membres ayant cotisé aujourd\'hui'
        );
    }
}