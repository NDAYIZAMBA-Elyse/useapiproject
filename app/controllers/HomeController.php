<?php
// app/controllers/HomeController.php

require_once 'Controller.php';

class HomeController extends Controller {
    
    public function index() {
        $this->success([
            'message' => 'API USE Project',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /membres' => 'Liste tous les membres',
                'GET /membres/{id}' => 'Affiche un membre',
                'POST /membres' => 'Crée un membre',
                'PUT /membres/{id}' => 'Met à jour un membre',
                'DELETE /membres/{id}' => 'Supprime un membre',
                'POST /auth/login' => 'Connexion',
                'POST /auth/register' => 'Inscription'
            ],
            'documentation' => '/api/docs'
        ]);
    }
}