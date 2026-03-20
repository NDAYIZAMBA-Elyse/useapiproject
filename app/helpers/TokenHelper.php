<?php
// app/helpers/TokenHelper.php

class TokenHelper {
    
    /**
     * Extraire le token des headers
     */
    public static function getTokenFromHeaders() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Décoder un token JWT
     */
    public static function decodeToken($token = null) {
        if (!$token) {
            $token = self::getTokenFromHeaders();
        }
        
        if (!$token) {
            return null;
        }
        
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Vérifier la signature
        $data = $header . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $data, JWT_SECRET, true);
        $expectedSignatureBase64 = self::base64UrlEncode($expectedSignature);
        
        if (!hash_equals($expectedSignatureBase64, $signature)) {
            return null;
        }
        
        // Décoder le payload
        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        
        // Vérifier l'expiration
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return null;
        }
        
        return $decodedPayload;
    }
    
    /**
     * Récupérer l'utilisateur depuis le token
     */
    public static function getUser() {
        // Vérifier d'abord la variable globale (si middleware a déjà décodé)
        if (isset($GLOBALS['current_user'])) {
            return $GLOBALS['current_user'];
        }
        
        // Décoder le token depuis les headers
        $payload = self::decodeToken();
        
        if (!$payload) {
            return null;
        }
        
        return [
            'id' => $payload['sub'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'nom' => $payload['nom'] ?? '',
            'prenom' => $payload['prenom'] ?? '',
            'cooperative' => $payload['cooperative'] ?? null,
            'telephone' => $payload['telephone'] ?? '',
            'reference' => $payload['reference'] ?? '',
            'statut' => $payload['statut'] ?? 1
        ];
    }
    
    /**
     * Vérifier si l'utilisateur est connecté (token valide)
     */
    public static function isLoggedIn() {
        return self::getUser() !== null;
    }
    
    /**
     * Récupérer l'ID utilisateur
     */
    public static function getUserId() {
        $user = self::getUser();
        return $user ? $user['id'] : null;
    }
    
    /**
     * Récupérer le rôle
     */
    public static function getUserRole() {
        $user = self::getUser();
        return $user ? $user['role'] : null;
    }
    
    /**
     * Vérifier si l'utilisateur est admin (role = 1)
     */
    public static function isAdmin() {
        return self::getUserRole() == 1;
    }
    
    /**
     * Récupérer l'ID de la coopérative
     */
    public static function getCooperativeId() {
        $user = self::getUser();
        return $user ? $user['cooperative'] : null;
    }
    
    /**
     * Vérifier les permissions
     */
    public static function hasPermission($requiredRole = null, $requiredCooperative = null) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $userRole = self::getUserRole();
        $userCooperative = self::getCooperativeId();
        
        // Les admins ont tous les droits
        if ($userRole == 1) {
            return true;
        }
        
        // Vérifier le rôle si spécifié
        if ($requiredRole && $userRole != $requiredRole) {
            return false;
        }
        
        // Vérifier la coopérative si spécifiée
        if ($requiredCooperative && $userCooperative != $requiredCooperative) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Encodage Base64Url
     */
    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    /**
     * Décodage Base64Url
     */
    private static function base64UrlDecode($data) {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        $data = strtr($data, '-_', '+/');
        return base64_decode($data);
    }
}