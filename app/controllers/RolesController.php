<?php
// app/controllers/RolesController.php

require_once 'Controller.php';
require_once __DIR__ . '/../helpers/TokenHelper.php';

class RolesController extends Controller {
    
    // public function index() {
    //     // Récupérer tous les rôles non supprimés
    //     $rolesModel = $this->model('Roles');
    //     $roles = $rolesModel->getAll(['STATUT_ROLE' => 1], 'DESCRIPTION_ROLE ASC');
    //     $this->success($roles);
    // }
//     public function index()
// {
//     $rolesModel = $this->model('Roles');

//     // 🔥 paramètres pagination
//     $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
//     $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

//     // sécurité
//     $page  = max($page, 1);
//     $limit = max($limit, 1);

//     $offset = ($page - 1) * $limit;

//     // 🔥 data
//     $roles = $rolesModel->getAll(
//         ['STATUT_ROLE' => 1],
//         'DESCRIPTION_ROLE ASC',
//         $limit,
//         $offset
//     );

//     // 🔥 total
//     $total = $rolesModel->count([
//         'STATUT_ROLE' => 1
//     ]);

//     $this->success([
//         'roles' => $roles,
//         'pagination' => [
//             'page' => $page,
//             'limit' => $limit,
//             'total' => $total,
//             'total_pages' => ceil($total / $limit)
//         ]
//     ], 'Liste des rôles paginée');
// }
public function index()
{
    $rolesModel = $this->model('Roles');

    $page  = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $search = $_GET['search'] ?? null;

    $result = $rolesModel->paginateWithDetails($page, $limit, $search);

    $this->success($result, 'Liste des rôles');
}

    // public function show($id) {
    //     $rolesModel = $this->model('Roles');
    //     $role = $rolesModel->getOne(['ID_ROLES' => $id]);
        
    //     if ($role) {
    //         $this->success($role);
    //     } else {
    //         $this->error('Role not found', 404);
    //     }
    // }

    public function show($id)
{
    $rolesModel = $this->model('Roles');

    $role = $rolesModel->getOne([
        'ID_ROLES' => (int)$id,
        'STATUT_ROLE' => 1
    ]);

    if ($role) {
        $this->success($role, 'Rôle trouvé');
    } else {
        $this->error('Rôle non trouvé', 404);
    }
}

    public function store() {
        // Créer un nouveau rôle
        $input = $this->getInput();
        
        // Debug: Voir ce qui est reçu
        error_log("Données reçues pour création de rôle: " . print_r($input, true));
        
        // VALIDATION CORRECTE :
        $errors = $this->validate($input, [
            'DESCRIPTION_ROLE' => 'required|min:2|max:100' // Seulement DESCRIPTION_ROLE
        ]);
        
        if (!empty($errors)) {
            return $this->error($errors, 422);
        }
        
        $rolesModel = $this->model('Roles');
        
        // Vérifier si la description du rôle existe déjà
        if ($rolesModel->exists(['DESCRIPTION_ROLE' => $input['DESCRIPTION_ROLE']])) {
            return $this->error('Role description already exists', 409);
        }
        
        // Par défaut, statut actif
        $input['STATUT_ROLE'] = 1;
        
        // Créer le rôle
        $roleId = $rolesModel->create($input);
        
        if ($roleId) {
            $this->success([
                'ID_ROLES' => $roleId,
                'message' => 'Role created successfully'
            ]);
        } else {
            $this->error('Failed to create role');
        }
    }
 
    public function update($id) {
        // Mettre à jour un rôle
        $input = $this->getInput();
        
        $rolesModel = $this->model('Roles');
        
        // Vérifier si le rôle existe
        $role = $rolesModel->getOne(['ID_ROLES' => $id]);
        
        if (!$role) {
            return $this->error('Role not found', 404);
        }
        
        // Si on met à jour la description, vérifier qu'elle n'existe pas déjà
        if (isset($input['DESCRIPTION_ROLE']) && $input['DESCRIPTION_ROLE'] !== $role['DESCRIPTION_ROLE']) {
            if ($rolesModel->exists(['DESCRIPTION_ROLE' => $input['DESCRIPTION_ROLE']])) {
                return $this->error('Role description already exists', 409);
            }
        }
        
        // Mettre à jour le rôle
        $success = $rolesModel->update($id, $input, 'ID_ROLES');
        
        if ($success) {
            $this->success(null, 'Role updated successfully');
        } else {
            $this->error('Failed to update role');
        }
    }
    
    public function destroy($id) {
        // Supprimer un rôle (soft delete)
        $rolesModel = $this->model('Roles');
        $role = $rolesModel->getOne(['ID_ROLES' => $id]);
        
        if (!$role) {
            return $this->error('Role not found', 404);
        }
        
        // Soft delete : mettre le statut à 0
        $success = $rolesModel->update($id, [
            'STATUT_ROLE' => 0
        ], 'ID_ROLES');
        
        if ($success) {
            $this->success(null, 'Role deleted successfully');
        } else {
            $this->error('Failed to delete role');
        }
    }
    
    public function search() {
        // Rechercher des rôles
        $query = isset($_GET['q']) ? $_GET['q'] : '';
        
        if (empty($query)) {
            return $this->error('Search query is required', 400);
        }
        
        $rolesModel = $this->model('Roles');
        $roles = $rolesModel->search($query);
        
        $this->success($roles);
    }
}
?>