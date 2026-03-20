<?php
// app/controllers/ErrorController.php

require_once 'Controller.php';

class ErrorController extends Controller {
    
    public function notFound() {
        $this->error('Route not found', 404);
    }
    
    public function index() {
        $this->error('Invalid request', 400);
    }
    
    public function methodNotAllowed() {
        $this->error('Method not allowed', 405);
    }
    
    public function serverError() {
        $this->error('Internal server error', 500);
    }
}