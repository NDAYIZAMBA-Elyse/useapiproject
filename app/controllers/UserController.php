<?php
// app/controllers/UserController.php

require_once 'Controller.php';
require_once __DIR__ . '/../helpers/TokenHelper.php';

class UserController extends Controller {
    
public function index()
{
    $currentUser = TokenHelper::getUser();

    if (!$currentUser) {
        return $this->error('Non authentifié', 401);
    }

    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;

    $cooperativeId = ($currentUser['role'] == 1)
        ? null
        : $currentUser['cooperative'];

    $result = $this->model('User')
        ->paginateWithDetails($page, $limit, $cooperativeId);

    return $this->success([
        'membres' => $result['data'],
        'pagination' => $result['pagination']
    ]);
}
    // public function index() {
    //     // Vérifier l'authentification via token
    //     $currentUser = TokenHelper::getUser();
        
    //     if (!$currentUser) {
    //         return $this->error('Non authentifié', 401);
    //     }
        
    //     $userModel = $this->model('User');
        
    //     // Conditions de base : membres actifs
    //     $conditions = ['STATUT_MEMBRES' => 1];
        
    //     // Si l'utilisateur n'est pas admin, filtrer par sa coopérative
    //     if ($currentUser['role'] != 1 && $currentUser['cooperative']) {
    //         $conditions['COOPERATIVE_ID'] = $currentUser['cooperative'];
    //     }
        
    //     // Ajouter des informations de logging
    //     $this->logAction('VIEW_ALL', 'Consultation de tous les membres', $currentUser['id'], $currentUser['cooperative']);
        
    //     $membres = $userModel->getAll($conditions, 'NOM_MEMBRES ASC');

    //     $this->success([
    //         'data' => $membres,
    //         'count' => count($membres),
    //         'requested_by' => [
    //             'id' => $currentUser['id'],
    //             'nom' => $currentUser['nom'],
    //             'prenom' => $currentUser['prenom'],
    //             'cooperative_id' => $currentUser['cooperative']
    //         ],
    //         'filters_applied' => $conditions
    //     ]);
    // }

    public function show($id)
{
    $currentUser = TokenHelper::getUser();

    if (!$currentUser) {
        return $this->error('Non authentifié', 401);
    }

    $membre = $this->model('User')->findWithDetails($id);

    if (!$membre) {
        return $this->error('Membre introuvable', 404);
    }

    if (
        $currentUser['role'] != 1 &&
        $membre['COOPERATIVE_ID'] != $currentUser['cooperative']
    ) {
        return $this->error('Accès interdit', 403);
    }

    return $this->success([
        'membre' => $membre
    ]);
}

    // public function show($id) {
    //     // Vérifier l'authentification
    //     $currentUser = TokenHelper::getUser();
        
    //     if (!$currentUser) {
    //         return $this->error('Non authentifié', 401);
    //     }
        
    //     $userModel = $this->model('User');
    //     $membre = $userModel->getOne(['ID_MEMBRES' => $id]);
        
    //     if (!$membre) {
    //         return $this->error('Membre not found', 404);
    //     }
        
    //     // Vérifier les permissions : admin ou même coopérative
    //     if ($currentUser['role'] != 1 && $membre['COOPERATIVE_ID'] != $currentUser['cooperative']) {
    //         return $this->error('Accès interdit', 403);
    //     }
        
    //     // Log l'action
    //     $this->logAction('VIEW', "Consultation du membre ID: $id", $currentUser['id'], $currentUser['cooperative']);
        
    //     unset($membre['PASSWORD']);
    //     $this->success([
    //         'data' => $membre,
    //         'requested_by' => [
    //             'id' => $currentUser['id'],
    //             'nom' => $currentUser['nom'],
    //             'prenom' => $currentUser['prenom']
    //         ]
    //     ]);
    // }
    
    public function store() {
        // Vérifier l'authentification
        $currentUser = TokenHelper::getUser();
        
        if (!$currentUser) {
            return $this->error('Non authentifié', 401);
        }
        
        $input = $this->getInput();
        
        // Validation
        $errors = $this->validate($input, [
            'NOM_MEMBRES' => 'required|min:3|max:100',
            'PRENOM_MEMBRES' => 'required|min:2|max:100',
            'EMAIL_MEMBRES' => 'required|email',
            'ROLE_ID' => 'required|integer',
            'TELEPHONE' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error($errors, 422);
        }
        
        $userModel = $this->model('User');
        
        // Vérifier si l'email existe déjà
        if ($userModel->exists(['EMAIL_MEMBRES' => $input['EMAIL_MEMBRES']])) {
            return $this->error('Email already exists', 409);
        }
        
        // Vérifier si le téléphone existe déjà
        if ($userModel->exists(['TELEPHONE' => $input['TELEPHONE']])) {
            return $this->error('Phone number already exists', 409);
        }
        
        // Si l'utilisateur n'est pas admin, forcer sa coopérative
        if ($currentUser['role'] != 1) {
            $input['COOPERATIVE_ID'] = $currentUser['cooperative'];
            
            // Par défaut, rôle membre (3) pour les non-admins
            if (!isset($input['ROLE_ID'])) {
                $input['ROLE_ID'] = 3; // Rôle membre
            }
        }
        
        // S'assurer que ROLE_ID et COOPERATIVE_ID sont définis
        if (!isset($input['ROLE_ID'])) {
            return $this->error('ROLE_ID est requis', 422);
        }
        
        if (!isset($input['COOPERATIVE_ID'])) {
            return $this->error('COOPERATIVE_ID est requis', 422);
        }
        
        // Hasher le mot de passe
        $input['PASSWORD'] = password_hash(0000, PASSWORD_DEFAULT);
        $input['USERNAME'] = $input['EMAIL_MEMBRES'];

        // $input['TYPE_IDENTITE_ID'] = $input['TYPE_IDENTITE_ID'];
        // $input['NUMERO_IDENTITE'] = $input['NUMERO_IDENTITE'];
        // $input['ADRESSE_MEMBRE'] = $input['ADRESSE_MEMBRE'];
        // $input['DATE_NAISSANCE'] = $input['DATE_NAISSANCE'];
        // $input['LIEU_NAISSANCE'] = $input['LIEU_NAISSANCE'];

        $input['DATE_ADHESION'] = date('Y-m-d H:i:s');
        $input['DATE_MODIFICATION'] = date('Y-m-d H:i:s');
        $input['STATUT_MEMBRES'] = 1;
        $input['FAIT_PAR'] = $currentUser['id'];
        $input['MODIFIE_PAR'] = $currentUser['id'];
        
        // Créer le membre
        $membreId = $userModel->create($input);
        
        if ($membreId) {
            // Log de l'action
            $this->logAction('CREATE', "Création du membre ID: $membreId", $currentUser['id'], $currentUser['cooperative'], $membreId);
            
            $this->success([
                'ID_MEMBRES' => $membreId,
                'REFERENCE_MEMBRE' => $input['REFERENCE_MEMBRE'],
                'created_by' => [
                    'id' => $currentUser['id'],
                    'nom' => $currentUser['nom'],
                    'prenom' => $currentUser['prenom'],
                    'cooperative_id' => $currentUser['cooperative']
                ],
                'message' => 'Membre created successfully'
            ]);
        } else {
            $this->error('Failed to create membre');
        }
    }
    
    public function update($id) {
        // Vérifier l'authentification
        $currentUser = TokenHelper::getUser();
        
        if (!$currentUser) {
            return $this->error('Non authentifié', 401);
        }
        
        $input = $this->getInput();
        
        $userModel = $this->model('User');
        $membre = $userModel->getOne(['ID_MEMBRES' => $id]);
        
        if (!$membre) {
            return $this->error('Membre not found', 404);
        }
        
        // Vérifier les permissions
        if ($currentUser['role'] != 1 && $membre['COOPERATIVE_ID'] != $currentUser['cooperative']) {
            return $this->error('Accès interdit', 403);
        }
        
        // Si l'utilisateur n'est pas admin, on ne peut pas changer la coopérative
        if ($currentUser['role'] != 1 && isset($input['COOPERATIVE_ID']) && $input['COOPERATIVE_ID'] != $currentUser['cooperative']) {
            return $this->error('Vous ne pouvez pas changer la coopérative', 403);
        }
        
        // Si l'utilisateur n'est pas admin, vérifier les rôles
        if ($currentUser['role'] != 1 && isset($input['ROLE_ID'])) {
            // Un non-admin ne peut pas donner des rôles élevés
            if ($input['ROLE_ID'] == 1) { // Admin
                return $this->error('Vous ne pouvez pas attribuer le rôle admin', 403);
            }
        }
        
        // Si on met à jour l'email, vérifier qu'il n'existe pas déjà
        if (isset($input['EMAIL_MEMBRES']) && $input['EMAIL_MEMBRES'] !== $membre['EMAIL_MEMBRES']) {
            if ($userModel->exists(['EMAIL_MEMBRES' => $input['EMAIL_MEMBRES']])) {
                return $this->error('Email already exists', 409);
            }
        }
        
        // Si on met à jour le téléphone, vérifier qu'il n'existe pas déjà
        if (isset($input['TELEPHONE']) && $input['TELEPHONE'] !== $membre['TELEPHONE']) {
            if ($userModel->exists(['TELEPHONE' => $input['TELEPHONE']])) {
                return $this->error('Phone number already exists', 409);
            }
        }
        
        // Hasher le mot de passe si fourni
        if (isset($input['PASSWORD'])) {
            $input['PASSWORD'] = password_hash($input['PASSWORD'], PASSWORD_DEFAULT);
        }
        
        // Mettre à jour la date de modification
        $input['DATE_MODIFICATION'] = date('Y-m-d H:i:s');
        
        // Qui a modifié
        $input['MODIFIE_PAR'] = $currentUser['id'];
        
        // Mettre à jour
        $success = $userModel->update($id, $input, 'ID_MEMBRES');
        
        if ($success) {
            // Log de l'action
            $this->logAction('UPDATE', "Mise à jour du membre ID: $id", $currentUser['id'], $currentUser['cooperative'], $id);
            
            $this->success([
                'updated_by' => [
                    'id' => $currentUser['id'],
                    'nom' => $currentUser['nom'],
                    'prenom' => $currentUser['prenom'],
                    'cooperative_id' => $currentUser['cooperative']
                ],
                'message' => 'Membre updated successfully'
            ]);
        } else {
            $this->error('Failed to update membre');
        }
    }
    
    public function destroy($id) {
        // Vérifier l'authentification
        $currentUser = TokenHelper::getUser();
        
        if (!$currentUser) {
            return $this->error('Non authentifié', 401);
        }
        
        $userModel = $this->model('User');
        $membre = $userModel->getOne(['ID_MEMBRES' => $id]);
        
        if (!$membre) {
            return $this->error('Membre not found', 404);
        }
        
        // Vérifier les permissions
        if ($currentUser['role'] != 1 && $membre['COOPERATIVE_ID'] != $currentUser['cooperative']) {
            return $this->error('Accès interdit', 403);
        }
        
        // Soft delete : mettre le statut à 0
        $success = $userModel->update($id, [
            'STATUT_MEMBRES' => 0,
            'DATE_MODIFICATION' => date('Y-m-d H:i:s'),
            'SUPPRIME_PAR' => $currentUser['id']
        ], 'ID_MEMBRES');
        
        if ($success) {
            // Log de l'action
            $this->logAction('DELETE', "Suppression du membre ID: $id", $currentUser['id'], $currentUser['cooperative'], $id);
            
            $this->success([
                'deleted_by' => [
                    'id' => $currentUser['id'],
                    'nom' => $currentUser['nom'],
                    'prenom' => $currentUser['prenom'],
                    'cooperative_id' => $currentUser['cooperative']
                ],
                'message' => 'Membre deleted successfully'
            ]);
        } else {
            $this->error('Failed to delete membre');
        }
    }
    
    public function profile() {
        // Récupérer le profil du membre connecté via token
        $currentUser = TokenHelper::getUser();
        
        if (!$currentUser) {
            return $this->error('Unauthorized', 401);
        }
        
        $userModel = $this->model('User');
        $membre = $userModel->getOne(['ID_MEMBRES' => $currentUser['id']]);
        
        if ($membre) {
            unset($membre['PASSWORD']);
            
            // Log de l'action
            $this->logAction('VIEW_PROFILE', "Consultation de son profil", $currentUser['id'], $currentUser['cooperative']);
            
            $this->success([
                'data' => $membre,
                'current_user' => [
                    'id' => $currentUser['id'],
                    'nom' => $currentUser['nom'],
                    'prenom' => $currentUser['prenom'],
                    'cooperative_id' => $currentUser['cooperative']
                ]
            ]);
        } else {
            $this->error('Membre not found', 404);
        }
    }
    
    public function updateProfile() {
        // Mettre à jour son propre profil
        $currentUser = TokenHelper::getUser();
        
        if (!$currentUser) {
            return $this->error('Unauthorized', 401);
        }
        
        $input = $this->getInput();
        
        // Empêcher la modification de certains champs
        unset($input['ROLE_ID']);
        unset($input['COOPERATIVE_ID']);
        unset($input['STATUT_MEMBRES']);
        unset($input['REFERENCE_MEMBRE']);
        
        $userModel = $this->model('User');
        $membre = $userModel->getOne(['ID_MEMBRES' => $currentUser['id']]);
        
        if (!$membre) {
            return $this->error('Membre not found', 404);
        }
        
        // Si on met à jour l'email, vérifier qu'il n'existe pas déjà
        if (isset($input['EMAIL_MEMBRES']) && $input['EMAIL_MEMBRES'] !== $membre['EMAIL_MEMBRES']) {
            if ($userModel->exists(['EMAIL_MEMBRES' => $input['EMAIL_MEMBRES']])) {
                return $this->error('Email already exists', 409);
            }
        }
        
        // Si on met à jour le téléphone, vérifier qu'il n'existe pas déjà
        if (isset($input['TELEPHONE']) && $input['TELEPHONE'] !== $membre['TELEPHONE']) {
            if ($userModel->exists(['TELEPHONE' => $input['TELEPHONE']])) {
                return $this->error('Phone number already exists', 409);
            }
        }
        
        // Hasher le mot de passe si fourni
        if (isset($input['PASSWORD'])) {
            $input['PASSWORD'] = password_hash($input['PASSWORD'], PASSWORD_DEFAULT);
        }
        
        $input['DATE_MODIFICATION'] = date('Y-m-d H:i:s');
        
        $success = $userModel->update($currentUser['id'], $input, 'ID_MEMBRES');
        
        if ($success) {
            $this->logAction('UPDATE_PROFILE', "Mise à jour de son profil", $currentUser['id'], $currentUser['cooperative']);
            
            $this->success([
                'updated_by' => [
                    'id' => $currentUser['id'],
                    'nom' => $currentUser['nom'],
                    'prenom' => $currentUser['prenom']
                ],
                'message' => 'Profil updated successfully'
            ]);
        } else {
            $this->error('Failed to update profile');
        }
    }

    public function search()
{
    $currentUser = TokenHelper::getUser();

    if (!$currentUser) {
        return $this->error('Non authentifié', 401);
    }

    $query = $_GET['q'] ?? '';

    if (!$query) {
        return $this->error('Query requise', 400);
    }

    $cooperativeId = ($currentUser['role'] == 1)
        ? null
        : $currentUser['cooperative'];

    $data = $this->model('User')
        ->searchWithCoop($query, $cooperativeId);

    return $this->success([
        'data' => $data
    ]);
}
    
    // public function search() {
    //     // Vérifier l'authentification
    //     $currentUser = TokenHelper::getUser();
        
    //     if (!$currentUser) {
    //         return $this->error('Non authentifié', 401);
    //     }
        
    //     $query = isset($_GET['q']) ? $_GET['q'] : '';
        
    //     if (empty($query)) {
    //         return $this->error('Search query is required', 400);
    //     }
        
    //     $userModel = $this->model('User');
    //     $membres = $userModel->search($query);
        
    //     // Filtrer par coopérative si l'utilisateur n'est pas admin
    //     if ($currentUser['role'] != 1) {
    //         $membres = array_filter($membres, function($membre) use ($currentUser) {
    //             return $membre['COOPERATIVE_ID'] == $currentUser['cooperative'];
    //         });
    //         $membres = array_values($membres); // Réindexer
    //     }
        
    //     $this->logAction('SEARCH', "Recherche: $query", $currentUser['id'], $currentUser['cooperative']);
        
    //     $this->success([
    //         'data' => $membres,
    //         'count' => count($membres),
    //         'search_query' => $query,
    //         'searched_by' => [
    //             'id' => $currentUser['id'],
    //             'nom' => $currentUser['nom'],
    //             'prenom' => $currentUser['prenom'],
    //             'cooperative_id' => $currentUser['cooperative']
    //         ]
    //     ]);
    // }

    public function byCooperative($cooperativeId)
{
    $currentUser = TokenHelper::getUser();

    if (!$currentUser) {
        return $this->error('Non authentifié', 401);
    }

    if ($currentUser['role'] != 1 && $cooperativeId != $currentUser['cooperative']) {
        return $this->error('Accès interdit', 403);
    }

    $data = $this->model('User')
        ->paginateWithDetails(1, 100, $cooperativeId);

    return $this->success($data);
}
    
    // public function byCooperative($cooperativeId) {
    //     // Vérifier l'authentification
    //     $currentUser = TokenHelper::getUser();
        
    //     if (!$currentUser) {
    //         return $this->error('Non authentifié', 401);
    //     }
        
    //     // Vérifier les permissions
    //     if ($currentUser['role'] != 1 && $cooperativeId != $currentUser['cooperative']) {
    //         return $this->error('Accès interdit', 403);
    //     }
        
    //     $userModel = $this->model('User');
    //     $membres = $userModel->getAll([
    //         'COOPERATIVE_ID' => $cooperativeId,
    //         'STATUT_MEMBRES' => 1
    //     ], 'NOM_MEMBRES ASC');
        
    //     $this->logAction('VIEW_BY_COOP', "Consultation membres coopérative ID: $cooperativeId", $currentUser['id'], $currentUser['cooperative']);
        
    //     $this->success([
    //         'data' => $membres,
    //         'count' => count($membres),
    //         'cooperative_id' => $cooperativeId,
    //         'requested_by' => [
    //             'id' => $currentUser['id'],
    //             'nom' => $currentUser['nom'],
    //             'prenom' => $currentUser['prenom'],
    //             'cooperative_id' => $currentUser['cooperative']
    //         ]
    //     ]);
    // }
    
    public function stats() {
        // Vérifier l'authentification
        $currentUser = TokenHelper::getUser();
        
        if (!$currentUser) {
            return $this->error('Non authentifié', 401);
        }
        
        $userModel = $this->model('User');
        
        // Conditions selon le rôle
        $conditions = ['STATUT_MEMBRES' => 1];
        if ($currentUser['role'] != 1) {
            $conditions['COOPERATIVE_ID'] = $currentUser['cooperative'];
        }
        
        $totalMembres = count($userModel->getAll($conditions));
        
        // Stats par rôle
        $stats = [
            'total_membres' => $totalMembres,
            'cooperative_id' => $currentUser['role'] == 1 ? null : $currentUser['cooperative'],
            'generated_by' => [
                'id' => $currentUser['id'],
                'nom' => $currentUser['nom'],
                'prenom' => $currentUser['prenom'],
                'role' => $currentUser['role']
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->logAction('VIEW_STATS', "Consultation des statistiques", $currentUser['id'], $currentUser['cooperative']);
        
        $this->success($stats);
    }
    
    private function generateReference() {
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
        return 'MEM-' . $date . '-' . $random;
    }
    
    /**
     * Loguer les actions importantes
     */
    private function logAction($action, $description, $userId, $cooperativeId, $targetId = null) {
        // Vous pouvez implémenter un système de logs ici
        // Exemple: stocker dans une table `audit_logs`
        $log = [
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'cooperative_id' => $cooperativeId,
            'target_id' => $targetId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Pour l'instant, on log juste dans les erreurs PHP
        error_log("AUDIT: " . json_encode($log));
        
        // Dans une version complète, vous pourriez faire:
        // $this->model('AuditLog')->create($log);
    }
}