<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Task;

final class TaskController extends Controller
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function index(): void
    {
        $status = Request::string('status');
        $sort = Request::string('sort', 'newest');

        if (!array_key_exists($status, task_statuses())) {
            $status = '';
        }
        if (!in_array($sort, ['newest', 'oldest', 'title', 'due_date', 'priority'], true)) {
            $sort = 'newest';
        }

        $filters = [
            'q' => Request::string('q'),
            'status' => $status,
            'sort' => $sort,
            'page' => max(1, Request::int('page', 1)),
        ];

        $tasks = Task::paginateForUser((int) Auth::id(), [
            'search' => $filters['q'],
            'status' => $filters['status'],
            'sort' => $filters['sort'],
            'page' => $filters['page'],
            'per_page' => (int) config('pagination.per_page', 8),
        ]);

        $this->render('tasks/index', [
            'title' => 'My tasks',
            'heading' => 'My tasks',
            'tasks' => $tasks,
            'stats' => Task::statsForUser((int) Auth::id()),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->render('tasks/create', [
            'title' => 'New task',
            'heading' => 'Create a task',
            'task' => [
                'title' => '',
                'description' => '',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => '',
            ],
            'errors' => $this->takeFlashArray('errors'),
            'old' => $this->takeFlashArray('old'),
        ]);
    }

    public function store(): void
    {
        Csrf::requireValid(Request::string('_token'));
        $data = $this->validatedTask('/tasks/create');

        $taskId = Task::create((int) Auth::id(), $data);

        $this->flash('success', 'Task "' . $data['title'] . '" was created.');
        redirect('/tasks/' . $taskId);
    }

    public function show(string $id): void
    {
        $task = $this->findTaskOrFail((int) $id);

        $this->render('tasks/show', [
            'title' => $task['title'],
            'heading' => 'Task details',
            'task' => $task,
        ]);
    }

    public function edit(string $id): void
    {
        $task = $this->findTaskOrFail((int) $id);

        $this->render('tasks/edit', [
            'title' => 'Edit task',
            'heading' => 'Edit task',
            'task' => $task,
            'errors' => $this->takeFlashArray('errors'),
            'old' => $this->takeFlashArray('old'),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::requireValid(Request::string('_token'));
        $task = $this->findTaskOrFail((int) $id);
        $data = $this->validatedTask('/tasks/' . (int) $id . '/edit');

        Task::updateForUser((int) $id, (int) Auth::id(), $data);

        $this->flash('success', 'Task "' . $data['title'] . '" was updated.');
        redirect('/tasks/' . (int) $id);
    }

    public function updateStatus(string $id): void
    {
        Csrf::requireValid(Request::string('_token'));
        $task = $this->findTaskOrFail((int) $id);

        $status = Request::string('status');
        if (!array_key_exists($status, task_statuses())) {
            abort(422, 'Invalid task status.', 'The selected status is not valid.');
        }

        Task::updateStatusForUser((int) $id, (int) Auth::id(), $status);

        $returnTo = Request::string('return_to');
        if (!str_starts_with($returnTo, '/tasks')) {
            $returnTo = '/tasks/' . (int) $id;
        }

        $this->flash('success', 'Status changed to ' . task_status_label($status) . ' for "' . $task['title'] . '".');
        redirect(safe_local_path($returnTo, '/tasks'));
    }

    public function confirmDelete(string $id): void
    {
        $task = $this->findTaskOrFail((int) $id);

        $this->render('tasks/delete', [
            'title' => 'Delete task',
            'heading' => 'Delete task',
            'task' => $task,
        ]);
    }

    public function destroy(string $id): void
    {
        Csrf::requireValid(Request::string('_token'));
        $task = $this->findTaskOrFail((int) $id);

        Task::deleteForUser((int) $id, (int) Auth::id());

        $this->flash('success', 'Task "' . $task['title'] . '" was deleted.');
        redirect('/tasks');
    }

    /**
     * @return array<string, mixed>
     */
    private function findTaskOrFail(int $id): array
    {
        $task = Task::findForUser($id, (int) Auth::id());

        if ($task === null) {
            // A 404 avoids revealing whether another user's task exists.
            abort(404, 'Task not found or not owned by the current user.', 'The task could not be found.');
        }

        return $task;
    }

    /**
     * @return array{title: string, description: ?string, status: string, priority: string, due_date: ?string}
     */
    private function validatedTask(string $failurePath): array
    {
        $input = [
            'title' => Request::string('title'),
            'description' => Request::string('description'),
            'status' => Request::string('status', 'pending'),
            'priority' => Request::string('priority', 'medium'),
            'due_date' => Request::string('due_date'),
        ];

        $validator = Validator::make(
            $input,
            [
                'title' => 'required|max:150',
                'description' => 'nullable|max:5000',
                'status' => 'required|in:pending,in_progress,completed',
                'priority' => 'required|in:low,medium,high',
                'due_date' => 'nullable|date',
            ],
            [
                'title' => 'Title',
                'description' => 'Description',
                'status' => 'Status',
                'priority' => 'Priority',
                'due_date' => 'Due date',
            ]
        );

        if ($validator->fails()) {
            $this->redirectWithErrors($failurePath, $validator->errors(), $input);
        }

        $description = $validator->value('description');
        $dueDate = $validator->value('due_date');

        return [
            'title' => $validator->value('title'),
            'description' => $description === '' ? null : $description,
            'status' => $validator->value('status'),
            'priority' => $validator->value('priority'),
            'due_date' => $dueDate === '' ? null : $dueDate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function takeFlashArray(string $key): array
    {
        $value = Session::getFlash($key, []);

        return is_array($value) ? $value : [];
    }
}
