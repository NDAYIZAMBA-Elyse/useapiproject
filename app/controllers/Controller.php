<?php
class Controller {
    protected $model;
    
    // Charger un modèle
    protected function model($modelName) {
        // Chemin vers le fichier du modèle
        $modelFile = dirname(__DIR__) . '/models/' . $modelName . '.php';
        
        if (file_exists($modelFile)) {
            require_once $modelFile;
            
            // Vérifier si la classe existe
            if (class_exists($modelName)) {
                $model = new $modelName();
                
                // Si le modèle a une table définie, s'assurer qu'elle est configurée
                // Utiliser la réflexion pour accéder à la propriété protégée
                $reflection = new ReflectionClass($model);
                
                if ($reflection->hasProperty('table')) {
                    $property = $reflection->getProperty('table');
                    $property->setAccessible(true);
                    
                    $tableName = $property->getValue($model);
                    
                    // Si une table est définie dans la classe enfant, l'utiliser
                    if (!empty($tableName) && method_exists($model, 'setTable')) {
                        $model->setTable($tableName);
                    }
                }
                
                return $model;
            }
        }
        
        // Fallback : utiliser le Model de base
        require_once dirname(__DIR__) . '/models/Model.php';
        $model = new Model();
        $model->setTable(strtolower($modelName));
        return $model;
    }
    
    // Réponse JSON
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Réponse d'erreur
    protected function error($message, $statusCode = 400) {
        $this->json(['error' => true, 'message' => $message], $statusCode);
    }
    
    // Réponse de succès
    protected function success($data = null, $message = 'Success') {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->json($response, 200);
    }
    
    // Récupérer les données de requête
    protected function getInput() {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            return $input ?: [];
        }
        
        if (!empty($_POST)) {
            return $_POST;
        }
        
        if (!empty($_GET)) {
            return $_GET;
        }
        
        return [];
    }
    
    // Validation
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $rulesArray = explode('|', $rule);
            
            foreach ($rulesArray as $singleRule) {
                // Règle: required
                if ($singleRule === 'required' && (!isset($data[$field]) || empty($data[$field]))) {
                    $errors[$field][] = "Le champ {$field} est requis";
                }
                
                // Règle: email
                if ($singleRule === 'email' && isset($data[$field]) && !empty($data[$field])) {
                    if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "Le champ {$field} doit être un email valide";
                    }
                }
                
                // Règle: numeric
                if ($singleRule === 'numeric' && isset($data[$field]) && !empty($data[$field])) {
                    if (!is_numeric($data[$field])) {
                        $errors[$field][] = "Le champ {$field} doit être un nombre";
                    }
                }
                
                // Règle: min:value
                if (strpos($singleRule, 'min:') === 0) {
                    $min = (int) str_replace('min:', '', $singleRule);
                    if (isset($data[$field]) && strlen($data[$field]) < $min) {
                        $errors[$field][] = "Le champ {$field} doit avoir au moins {$min} caractères";
                    }
                }
                
                // Règle: max:value
                if (strpos($singleRule, 'max:') === 0) {
                    $max = (int) str_replace('max:', '', $singleRule);
                    if (isset($data[$field]) && strlen($data[$field]) > $max) {
                        $errors[$field][] = "Le champ {$field} ne doit pas dépasser {$max} caractères";
                    }
                }
            }
        }
        
        return $errors;
    }
}