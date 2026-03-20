<?php
// Environnement : development | production
define('APP_ENV', 'development');

// Base URL
define('BASE_URL', 'http://localhost/useapiproject');

// Chemin racine
// define('ROOT_PATH', dirname(dirname(__DIR__)));
// define('APP_PATH', ROOT_PATH . '/app');

// JWT Secret
define('JWT_SECRET', 'votre_clé_secrète_très_longue_et_complexe_ici');

// Timezone
date_default_timezone_set('Europe/Paris');

// Configuration de l'application
$config = [
    'version' => '1.0.0',
    'name' => 'USE API Project'
];