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
    
    // Routes rôles protégées (modification)
    $router->get('/roles', 'RolesController@index');
    $router->get('/roles/{id}', 'RolesController@show');
    $router->post('/roles', 'RolesController@store');
    $router->put('/roles/{id}', 'RolesController@update');
    $router->delete('/roles/{id}', 'RolesController@destroy');
    $router->get('/roles/{id}/stats', 'RolesController@stats');
    $router->get('/roles/search', 'RolesController@search');

    $router->get('/me', 'AuthController@me'); // Infos utilisateur courant
    $router->get('/profile', 'UserController@profile'); // Profil complet
});

// $router->group(['middleware' => 'JwtMiddleware'], function ($router) {
//     // Routes protégées ici
//     $router->get('membres', 'UserController@index');
//     $router->get('/membres/{id}', 'UserController@show');
//     $router->post('membres', 'UserController@store');
//     $router->put('membres/{id}', 'UserController@update');
//     $router->delete('membres/{id}', 'UserController@destroy');
//     $router->get('membres/{id}/stats', 'UserController@stats');
//         // Autres routes protégées...
// });

// $router->group(['middleware' => 'JwtMiddleware'], function ($router) {
//     // Routes protégées ici
//     $router->get('roles', 'RolesController@index');
//     $router->get('/roles/{id}', 'RolesController@show');
//     $router->post('roles', 'RolesController@store');
//     $router->put('roles/{id}', 'RolesController@update');
//     $router->delete('roles/{id}', 'RolesController@destroy');
//     $router->get('roles/{id}/stats', 'RolesController@stats');
//         // Autres routes protégées...
// });

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