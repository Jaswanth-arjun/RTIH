<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use PDOException;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        Auth::requireGuest();

        $this->render('auth/login', [
            'layout' => 'layouts/auth',
            'title' => 'Sign in',
            'heading' => 'Welcome back',
            'old' => Session::getFlash('old', []),
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function login(): void
    {
        Auth::requireGuest();
        Csrf::requireValid(Request::string('_token'));

        $email = Request::string('email');
        $password = (string) Request::input('password', '');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => 'required|email|max:190',
                'password' => 'required|max:72',
            ],
            ['email' => 'Email address', 'password' => 'Password']
        );

        if ($validator->fails()) {
            $this->redirectWithErrors('/login', $validator->errors(), ['email' => $email]);
        }

        $email = mb_strtolower($validator->value('email'));

        if (Auth::tooManyAttempts($email)) {
            $minutes = Auth::lockoutMinutesRemaining($email);
            $this->flash('error', 'Too many failed sign-in attempts. Please try again in ' . max(1, $minutes) . ' minute(s).');
            Session::flash('old', ['email' => $email]);
            redirect('/login');
        }

        $user = Auth::attempt($email, $password);
        if ($user === null) {
            Auth::recordFailedAttempt($email);
            $this->flash('error', 'The email address or password is incorrect.');
            Session::flash('old', ['email' => $email]);
            redirect('/login');
        }

        Auth::clearFailedAttempts($email);
        Auth::login($user);

        $this->flash('success', 'Welcome back, ' . $user['name'] . '.');
        $intended = Session::pull('intended');
        redirect(safe_local_path(is_string($intended) ? $intended : null, '/tasks'));
    }

    public function showRegister(): void
    {
        Auth::requireGuest();

        $this->render('auth/register', [
            'layout' => 'layouts/auth',
            'title' => 'Create account',
            'heading' => 'Create your account',
            'old' => Session::getFlash('old', []),
            'errors' => Session::getFlash('errors', []),
        ]);
    }

    public function register(): void
    {
        Auth::requireGuest();
        Csrf::requireValid(Request::string('_token'));

        $name = Request::string('name');
        $email = Request::string('email');
        $password = (string) Request::input('password', '');
        $confirmation = (string) Request::input('password_confirmation', '');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'name' => 'required|min:2|max:100',
                'email' => 'required|email|max:190',
                'password' => 'required|min:8|max:72|confirmed',
                'password_confirmation' => 'required',
            ],
            [
                'name' => 'Full name',
                'email' => 'Email address',
                'password' => 'Password',
                'password_confirmation' => 'Password confirmation',
            ]
        );

        $old = ['name' => $name, 'email' => $email];

        if ($validator->fails()) {
            $this->redirectWithErrors('/register', $validator->errors(), $old);
        }

        $email = mb_strtolower($validator->value('email'));
        if (User::emailExists($email)) {
            $this->redirectWithErrors('/register', ['email' => 'An account with this email address already exists.'], $old);
        }

        try {
            $userId = User::create(
                $validator->value('name'),
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            );
        } catch (PDOException $exception) {
            // A unique index protects against a race between the check above and the insert.
            if ((string) $exception->getCode() === '23000') {
                $this->redirectWithErrors('/register', ['email' => 'An account with this email address already exists.'], $old);
            }

            throw $exception;
        }

        $user = User::findPublicById($userId);
        if ($user === null) {
            throw new \RuntimeException('The newly created user could not be loaded.');
        }

        Auth::login($user);
        $this->flash('success', 'Your account has been created. Welcome to TaskFlow, ' . $user['name'] . '.');
        redirect('/tasks');
    }

    public function logout(): void
    {
        Csrf::requireValid(Request::string('_token'));
        Auth::logout();
        $this->flash('success', 'You have been signed out.');
        redirect('/login');
    }
}
