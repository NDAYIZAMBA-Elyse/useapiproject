<?php
// routes/api.php

require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/middleware/JwtMiddleware.php';

// Supprimer l'echo qui cause des problèmes JSON
// echo "Routing initialized";

// Créer le routeur avec base path
$basePath = '/useapiproject'; // Ajustez selon votre configuration
$router = new Router($basePath);

// =====================
// ROUTES PUBLIQUES
// =====================
$router->post('login', 'AuthController@login');
$router->post('register', 'AuthController@register');
$router->post('logout', 'AuthController@logout');

//  $router->get('/membres', 'UserController@index');
// =====================
// ROUTES PROTÉGÉES (à implémenter plus tard)
// =====================
$router->group(['middleware' => 'JwtMiddleware'], function ($router) {
    // Routes membres protégées (modification)
    $router->get('/membres', 'UserController@index');
    $router->get('/membres/{id}', 'UserController@show');
    $router->post('/membres', 'UserController@store');
    $router->put('/membres/{id}', 'UserController@update');
    $router->delete('/membres/{id}', 'UserController@destroy');
    $router->get('/membres/{id}/stats', 'UserController@stats');
    // Nouvelles routes
    $router->get('/membres/profile', 'UserController@profile');
    $router->put('/membres/profile', 'UserController@updateProfile');
    $router->get('/membres/search', 'UserController@search');
    $router->get('/membres/cooperative/{id}', 'UserController@byCooperative');
    $router->get('/membres/stats', 'UserController@stats');

    $router->get('/me', 'AuthController@me'); // Infos utilisateur courant
    // $router->get('/profile', 'UserController@profile'); // Profil complet
    $router->get('/profile', 'AuthController@profile'); // Profil complet
    $router->post('refresh', 'AuthController@refresh');
    // Routes pour la coopérative de l'utilisateur connecté
    $router->get('/auth/me/cooperative', 'AuthController@myCooperative');
    $router->get('/auth/me/cooperative/stats', 'AuthController@myCooperativeWithStats');

       // Routes rôles protégées (modification)
    $router->get('/roles', 'RolesController@index');
    $router->get('/roles/{id}', 'RolesController@show');
    $router->post('/roles', 'RolesController@store');
    $router->put('/roles/{id}', 'RolesController@update');
    $router->delete('/roles/{id}', 'RolesController@destroy');
    $router->get('/roles/{id}/stats', 'RolesController@stats');
    $router->get('/roles/search', 'RolesController@search');

       // Routes epargnes protégées (modification)
    $router->get('/epargnes', 'EpargneController@index');
    $router->get('/epargnes/{id}', 'EpargneController@show');
    $router->post('/epargnes', 'EpargneController@store');
    $router->put('/epargnes/{id}', 'EpargneController@update');
    $router->delete('/epargnes/{id}', 'EpargneController@destroy');
    $router->get('/epargnes/{id}/stats', 'EpargneController@stats');
    $router->get('/epargnes/search', 'EpargneController@search');

    // 🔥 NOUVELLE ROUTE : Récupérer les cumuls d'un membre
    $router->get('/epargnes/cumuls/membre', 'EpargneController@getCumulsByMembre');
    $router->post('/epargnes/cumuls/membre', 'EpargneController@getCumulsByMembre');

    // 🔥 NOUVELLE ROUTE : Membres ayant déjà cotisé aujourd'hui
    $router->get('/epargnes/cotises/today', 'EpargneController@getMembresCoticesToday');
});


// =====================
// DISPATCH
// =====================
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Nettoyer l'URI
$requestUri = trim($requestUri, '/');

if (!$router->dispatch($requestMethod, $requestUri)) {
    // Route non trouvée
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'Route non trouvée',
        'path' => $requestUri,
        'method' => $requestMethod
    ]);
    exit();
}