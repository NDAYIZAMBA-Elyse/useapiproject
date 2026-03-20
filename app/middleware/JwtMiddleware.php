<?php
// app/middleware/JwtMiddleware.php

class JwtMiddleware {
    
    public function handle() {
        // Récupérer le token depuis les headers
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        // Vérifier le format "Bearer token"
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            error_log("Aucun header Authorization trouvé");
            http_response_code(401);
            echo json_encode([
                'error' => 'Token manquant ou non Authorization header', 
                'debug' => 'Vérfier que le token est bien dans le header Authorization'
            ]);
            return false;
        }
        
        $token = $matches[1];
        
        // Valider le token
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide ou expiré']);
            return false;
        }
        
        // Stocker les informations dans $GLOBALS pour accès facile (optionnel)
        // Mais PAS dans la session
        $GLOBALS['current_user'] = [
            'id' => $payload['sub'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'nom' => $payload['nom'] ?? '',
            'prenom' => $payload['prenom'] ?? '',
            'cooperative' => $payload['cooperative'] ?? null,
            'telephone' => $payload['telephone'] ?? '',
            'reference' => $payload['reference'] ?? '',
            'statut' => $payload['statut'] ?? 1,
            'token_expires' => $payload['exp']
        ];
        
        return true;
    }
    
    private function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Décoder le payload
        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);
        
        if (!$decodedPayload) {
            return false;
        }
        
        // Vérifier l'expiration
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }
        
        // Vérifier la signature
        $data = $header . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $data, JWT_SECRET, true);
        $expectedSignatureBase64 = $this->base64UrlEncode($expectedSignature);
        
        if (!hash_equals($expectedSignatureBase64, $signature)) {
            return false;
        }
        
        return $decodedPayload;
    }
    
    private function base64UrlDecode($data) {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        $data = strtr($data, '-_', '+/');
        return base64_decode($data);
    }
    
    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}