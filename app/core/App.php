<?php
class App {
    private $controller = 'ErrorController';  // Par défaut: contrôleur d'erreur
    private $method = 'notFound';
    private $params = [];

    public function __construct() {
        // Analyse de l'URL
        $url = $this->parseUrl();
        
        // Si l'URL est vide, rediriger vers une page d'accueil
        if (empty($url[0])) {
            $this->controller = 'HomeController';
            $this->method = 'index';
        } else {
            // Controller
            $controllerName = ucfirst($url[0]) . 'Controller';
            $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
            
            if (file_exists($controllerFile)) {
                $this->controller = $controllerName;
                unset($url[0]);
            } else {
                // Si le contrôleur n'existe pas, erreur 404
                $this->controller = 'ErrorController';
                $this->method = 'notFound';
            }
        }
        
        // Inclure le controller
        require_once APP_PATH . '/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;
        
        // Méthode (seulement si ce n'est pas déjà une erreur)
        if ($this->method !== 'notFound' && isset($url[1]) && !empty($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                // Méthode non trouvée
                $this->method = 'notFound';
            }
        }
        
        // Paramètres
        $this->params = $url ? array_values($url) : [];
    }
    
    public function run() {
        // Vérifier que la méthode existe
        if (!method_exists($this->controller, $this->method)) {
            $this->method = 'notFound';
        }
        
        // Appel de la méthode
        call_user_func_array([$this->controller, $this->method], $this->params);
    }
    
    private function parseUrl() {
        // Récupérer l'URI complète
        $request_uri = $_SERVER['REQUEST_URI'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        
        // Retirer le script_name de l'URI
        $uri = str_replace(dirname($script_name), '', $request_uri);
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = trim($uri, '/');
        
        // Séparer en segments
        if (!empty($uri)) {
            return explode('/', $uri);
        }
        
        return [];
    }
}