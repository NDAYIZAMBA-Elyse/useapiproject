<?php
// app/controllers/AuthController.php

require_once 'Controller.php';

class AuthController extends Controller {
    
    public function index() {
        $this->success([
            'message' => 'Auth API',
            'endpoints' => [
                'POST /login' => 'User login',
                'POST /register' => 'User registration',
                'POST /logout' => 'User logout',
                'GET /me' => 'Get current user from token',
                'GET /me/cooperative' => 'Get user cooperative from token'
            ]
        ]);
    }

    public function login() {
        $input = $this->getInput();
        
        // Validation
        $errors = $this->validate($input, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            return $this->error($errors, 422);
        }
        
        $userModel = $this->model('User');
        $user = $userModel->checkCredentials($input['email'], $input['password']);
        
        if ($user) {
            // Générer un token JWT avec TOUTES les informations
            $tokenPayload = [
                'sub' => $user['ID_MEMBRES'],               // ID unique
                'email' => $user['EMAIL_MEMBRES'],          // Email
                'role' => $user['ROLE_ID'],                 // Rôle ID
                'nom' => $user['NOM_MEMBRES'],              // Nom
                'prenom' => $user['PRENOM_MEMBRES'],        // Prénom
                'cooperative' => $user['COOPERATIVE_ID'],   // Coopérative ID
                'telephone' => $user['TELEPHONE'],          // Téléphone
                'reference' => $user['REFERENCE_MEMBRE'],   // Référence
                'statut' => $user['STATUT_MEMBRES'],        // Statut
                'username' => $user['USERNAME'] ?? '',      // Username
                'type_identite' => $user['TYPE_IDENTITE_ID'] ?? null, // Type identité
                'numero_identite' => $user['NUMERO_IDENTITE'] ?? '',  // Numéro identité
                'adresse' => $user['ADRESSE_MEMBRE'] ?? '', // Adresse
                'date_naissance' => $user['DATE_NAISSANCE'] ?? null, // Date naissance
                'lieu_naissance' => $user['LIEU_NAISSANCE'] ?? '', // Lieu naissance
                'photo' => $user['PHOTO_PATH'] ?? '',       // Photo
                'date_adhesion' => $user['DATE_ADHESION'] ?? null, // Date adhésion
                'fait_par' => $user['FAIT_PAR'] ?? null,    // Créé par
                'iat' => time(),                            // Date d'émission
                'exp' => time() + (7 * 24 * 60 * 60)        // Expiration (7 jours)
            ];
            
            $token = $this->generateJWT($tokenPayload);
            
            $this->success([
                'user' => [
                    'id' => $user['ID_MEMBRES'],
                    'nom' => $user['NOM_MEMBRES'],
                    'prenom' => $user['PRENOM_MEMBRES'],
                    'email' => $user['EMAIL_MEMBRES'],
                    'telephone' => $user['TELEPHONE'],
                    'role_id' => $user['ROLE_ID'],
                    'photo' => $user['PHOTO_PATH'],
                    'cooperative_id' => $user['COOPERATIVE_ID'],
                    'reference' => $user['REFERENCE_MEMBRE'],
                    'statut' => $user['STATUT_MEMBRES']
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 7 * 24 * 60 * 60,
                'expires_at' => date('Y-m-d H:i:s', $tokenPayload['exp']),
                'message' => 'Login successful'
            ]);
        } else {
            $this->error('Email ou mot de passe incorrect', 401);
        }
    }
    
    public function register() {
        $userController = new UserController();
        return $userController->store();
    }

    public function logout() {
        session_destroy();
        $this->success(null, 'Logout successful');
    }
    
    public function me() {
        // Récupérer uniquement depuis le token
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->error('Token manquant', 401);
        }
        
        $token = $matches[1];
        $payload = $this->decodeJWT($token);
        
        if (!$payload) {
            return $this->error('Token invalide ou expiré', 401);
        }
        
        // Retourner toutes les informations du token
        $this->success([
            'id' => $payload['sub'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'nom' => $payload['nom'],
            'prenom' => $payload['prenom'],
            'cooperative' => $payload['cooperative'],
            'telephone' => $payload['telephone'],
            'reference' => $payload['reference'],
            'statut' => $payload['statut'],
            'username' => $payload['username'] ?? '',
            'type_identite' => $payload['type_identite'] ?? null,
            'numero_identite' => $payload['numero_identite'] ?? '',
            'adresse' => $payload['adresse'] ?? '',
            'date_naissance' => $payload['date_naissance'] ?? null,
            'lieu_naissance' => $payload['lieu_naissance'] ?? '',
            'photo' => $payload['photo'] ?? '',
            'date_adhesion' => $payload['date_adhesion'] ?? null,
            'fait_par' => $payload['fait_par'] ?? null,
            'token_expires' => date('Y-m-d H:i:s', $payload['exp']),
            'via' => 'jwt_token_only'
        ]);
    }
    
    /**
     * Récupérer la coopérative de l'utilisateur connecté
     * Avec jointure complète sur la table cooperatives
     * 
     * @return json
     */
    
    public function myCooperative()
    {
        $currentUser = TokenHelper::getUser();

        if (!$currentUser) {
            return $this->error('Non authentifié', 401);
        }

        $cooperative = $this->model('User')->getCoopSociete($currentUser['cooperative']);

        if (!$cooperative) {
            return $this->error('Coopérative introuvable', 404);
        }

        if (
            $currentUser['role'] != 1 &&
            $cooperative['ID_COOPERATIVE'] != $currentUser['cooperative']
        ) {
            return $this->error('Accès interdit', 403);
        }

        return $this->success([
            'cooperative' => $cooperative
        ]);
    }

    /**
     * Récupérer la coopérative avec les statistiques (nombre de membres, cotisations, etc.)
     * 
     * @return json
     */
    public function myCooperativeWithStats()
    {
        $currentUser = TokenHelper::getUser();

        if (!$currentUser) {
            return $this->error('Non authentifié', 401);
        }

        $cooperative = $this->model('User')->getCoopSocieteWithStats($currentUser['cooperative']);

        if (!$cooperative) {
            return $this->error('Coopérative introuvable', 404);
        }

        if (
            $currentUser['role'] != 1 &&
            $cooperative['ID_COOPERATIVE'] != $currentUser['cooperative']
        ) {
            return $this->error('Accès interdit', 403);
        }

        return $this->success([
            'cooperative' => $cooperative
        ]);
    }

    public function profile() {
        // Récupérer le profil complet depuis la base
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->error('Token manquant', 401);
        }
        
        $token = $matches[1];
        $payload = $this->decodeJWT($token);
        
        if (!$payload) {
            return $this->error('Token invalide ou expiré', 401);
        }
        
        $userId = $payload['sub'];
        
        $userModel = $this->model('User');
        $user = $userModel->withRelations($userId);
        
        if ($user) {
            $this->success($user);
        } else {
            $this->error('User not found', 404);
        }
    }
    
    public function refresh() {
        // Rafraîchir un token expiré
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->error('Token manquant', 401);
        }
        
        $oldToken = $matches[1];
        $payload = $this->decodeJWT($oldToken, false); // Ne pas vérifier l'expiration
        
        if (!$payload) {
            return $this->error('Token invalide', 401);
        }
        
        // Régénérer un nouveau token avec les mêmes données
        $payload['iat'] = time();
        $payload['exp'] = time() + (7 * 24 * 60 * 60);
        
        $newToken = $this->generateJWT($payload);
        
        $this->success([
            'token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => 7 * 24 * 60 * 60,
            'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
            'message' => 'Token refreshed'
        ]);
    }
    
    private function generateJWT($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    private function decodeJWT($token, $checkExpiration = true) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Vérifier la signature
        $data = $header . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $data, JWT_SECRET, true);
        $expectedSignatureBase64 = $this->base64UrlEncode($expectedSignature);
        
        if (!hash_equals($expectedSignatureBase64, $signature)) {
            return false;
        }
        
        // Décoder le payload
        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);
        
        // Vérifier l'expiration seulement si demandé
        if ($checkExpiration && isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }
        
        return $decodedPayload;
    }
    
    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    private function base64UrlDecode($data) {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        $data = strtr($data, '-_', '+/');
        return base64_decode($data);
    }
}