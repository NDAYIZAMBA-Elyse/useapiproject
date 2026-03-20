<?php
// public/index.php

// ============================================
// DÉBOGAGE
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// CONSTANTES
// ============================================
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// ============================================
// CONFIGURATION
// ============================================
require_once APP_PATH . '/../config/config.php';
require_once APP_PATH . '/../config/database.php';

// ============================================
// AUTOLOADER
// ============================================
spl_autoload_register(function ($className) {
    $className = str_replace('\\', '/', $className);
    
    $directories = [
        APP_PATH . '/controllers/',
        APP_PATH . '/core/',
        APP_PATH . '/models/',
        APP_PATH . '/middleware/',
        APP_PATH . '/helpers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ============================================
// CORS HEADERS
// ============================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// SESSION (si nécessaire)
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHARGEMENT DES ROUTES
// ============================================
try {
    // Inclure le fichier de routes
    $routesFile = ROOT_PATH . '/routes/api.php';
    
    if (file_exists($routesFile)) {
        require_once $routesFile;
    } else {
        // Fallback : routeur simple
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Fichier de routes manquant'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Erreur interne du serveur',
        'debug' => (APP_ENV === 'development') ? $e->getMessage() : null
    ]);
}