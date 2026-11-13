<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Core\Auth;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', static function (): void {
        redirect(Auth::check() ? '/tasks' : '/login');
    });

    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/register', [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register']);
    $router->post('/logout', [AuthController::class, 'logout']);

    $router->get('/tasks', [TaskController::class, 'index']);
    $router->get('/tasks/create', [TaskController::class, 'create']);
    $router->post('/tasks', [TaskController::class, 'store']);
    $router->get('/tasks/{id}', [TaskController::class, 'show']);
    $router->get('/tasks/{id}/edit', [TaskController::class, 'edit']);
    $router->post('/tasks/{id}', [TaskController::class, 'update']);
    $router->post('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
    $router->get('/tasks/{id}/delete', [TaskController::class, 'confirmDelete']);
    $router->post('/tasks/{id}/delete', [TaskController::class, 'destroy']);

    $router->get('/admin/users', [AdminController::class, 'users']);
    $router->post('/admin/users/{id}/toggle', [AdminController::class, 'toggleActive']);
};
