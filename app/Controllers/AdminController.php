<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\User;

final class AdminController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function users(): void
    {
        $users = User::allWithTaskCounts();

        $active = 0;
        $admins = 0;
        $tasks = 0;
        foreach ($users as $user) {
            $active += (int) $user['is_active'] === 1 ? 1 : 0;
            $admins += $user['role'] === 'admin' ? 1 : 0;
            $tasks += (int) $user['task_count'];
        }

        $this->render('admin/users', [
            'title' => 'User management',
            'heading' => 'User management',
            'users' => $users,
            'summary' => [
                'total_users' => count($users),
                'active_users' => $active,
                'admins' => $admins,
                'total_tasks' => $tasks,
            ],
        ]);
    }

    public function toggleActive(string $id): void
    {
        Csrf::requireValid(Request::string('_token'));

        $userId = (int) $id;
        if ($userId === (int) Auth::id()) {
            $this->flash('error', 'You cannot deactivate your own account.');
            redirect('/admin/users');
        }

        $user = User::findPublicById($userId);
        if ($user === null) {
            abort(404, 'User not found.', 'The user could not be found.');
        }

        $newState = (int) $user['is_active'] !== 1;
        User::setActive($userId, $newState);

        $this->flash(
            'success',
            'Account for ' . $user['name'] . ' has been ' . ($newState ? 'activated' : 'deactivated') . '.'
        );
        redirect('/admin/users');
    }
}
