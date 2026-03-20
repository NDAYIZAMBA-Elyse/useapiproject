<?php
// app/core/Router.php

class Router {
    private $routes = [];
    private $middlewares = [];
    private $currentMiddleware = null;
    private $basePath = '';
    private $groupPrefix = '';
    
    public function __construct($basePath = '') {
        $this->basePath = $basePath;
    }
    
    /**
     * Créer un groupe de routes
     */
    public function group($options, $callback) {
        // Sauvegarder les paramètres actuels
        $previousMiddleware = $this->currentMiddleware;
        $previousPrefix = $this->groupPrefix;
        
        // Appliquer les options du groupe
        if (isset($options['middleware'])) {
            $this->currentMiddleware = $options['middleware'];
        }
        
        if (isset($options['prefix'])) {
            $this->groupPrefix = $previousPrefix . $options['prefix'];
        }
        
        // Exécuter le callback du groupe
        call_user_func($callback, $this);
        
        // Restaurer les paramètres précédents
        $this->currentMiddleware = $previousMiddleware;
        $this->groupPrefix = $previousPrefix;
    }
    
    /**
     * Ajouter une route
     */
    private function addRoute($method, $path, $handler) {
        // Appliquer le préfixe du groupe
        $fullPath = $this->groupPrefix . $path;
        $fullPath = trim($fullPath, '/');
        
        // Appliquer le basePath
        if ($this->basePath && $fullPath) {
            $fullPath = trim($this->basePath, '/') . '/' . $fullPath;
        }
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $this->currentMiddleware
        ];
    }
    
    /**
     * Méthodes HTTP
     */
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }
    
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    public function patch($path, $handler) {
        $this->addRoute('PATCH', $path, $handler);
    }
    
    /**
     * Dispatch une requête
     */
    public function dispatch($method, $uri) {
        // Nettoyer l'URI
        $uri = trim($uri, '/');
        
        // Retirer le basePath si présent dans l'URI
        if ($this->basePath && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
            $uri = trim($uri, '/');
        }
        
        foreach ($this->routes as $route) {
            // Convertir les paramètres {id} en regex
            $pattern = $this->buildPattern($route['path']);
            
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                // Exécuter le middleware si présent
                if ($route['middleware']) {
                    if (!$this->executeMiddleware($route['middleware'])) {
                        return false; // Middleware a bloqué la requête
                    }
                }
                
                // Extraire les paramètres
                array_shift($matches);
                
                // Appeler le handler
                $this->callHandler($route['handler'], $matches);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Convertir un chemin en pattern regex
     */
    private function buildPattern($path) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);
        $pattern = str_replace('/', '\/', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Exécuter un middleware
     */
    private function executeMiddleware($middlewareName) {
        $middlewareFile = APP_PATH . '/middleware/' . $middlewareName . '.php';
        
        if (file_exists($middlewareFile)) {
            require_once $middlewareFile;
            
            if (class_exists($middlewareName)) {
                $middleware = new $middlewareName();
                
                if (method_exists($middleware, 'handle')) {
                    return $middleware->handle();
                }
            }
        }
        
        // Middleware non trouvé
        http_response_code(500);
        echo json_encode(['error' => 'Middleware error: ' . $middlewareName]);
        return false;
    }
    
    /**
     * Appeler un handler
     */
    private function callHandler($handler, $params = []) {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerName, $methodName) = explode('@', $handler);
            
            $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
            
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    
                    if (method_exists($controller, $methodName)) {
                        call_user_func_array([$controller, $methodName], $params);
                        return;
                    }
                }
            }
        }
        
        // Handler invalide
        http_response_code(500);
        echo json_encode(['error' => 'Handler invalide']);
    }
}