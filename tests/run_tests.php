<?php

declare(strict_types=1);

/**
 * TaskFlow integration test suite.
 *
 * Requirements:
 *   - PHP 8.1+ with pdo_mysql and curl
 *   - A MySQL/MariaDB server configured through .env (or real environment variables)
 *
 * Run from the project root:
 *   php tests/run_tests.php
 *
 * The suite creates and drops a separate `task_tracker_test` database, starts
 * PHP's built-in server on 127.0.0.1:8099 and exercises the application over HTTP.
 */

use App\Core\Config;
use App\Core\Database;

putenv('APP_ENV=testing');
putenv('APP_DEBUG=false');

require dirname(__DIR__) . '/app/bootstrap.php';

final class HttpClient
{
    private string $cookieFile;
    private int $counter = 0;

    public function __construct(private string $baseUrl)
    {
        $file = tempnam(sys_get_temp_dir(), 'taskflow_cookie_');
        if ($file === false) {
            throw new RuntimeException('Could not create a cookie jar.');
        }
        $this->cookieFile = $file;
        file_put_contents($this->cookieFile, '');
    }

    public function __destruct()
    {
        if (is_file($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    /**
     * @param array<string, string>|null $data
     * @return array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string}
     */
    public function request(string $method, string $path, ?array $data = null): array
    {
        $this->counter++;
        $url = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : $this->baseUrl . $path;

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Could not initialize cURL.');
        }

        $headers = [];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'TaskFlow-Test-Suite/1.0',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml'],
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
                $length = strlen($line);
                $trimmed = trim($line);
                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $trimmed, 2);
                $headers[strtolower(trim($name))][] = trim($value);

                return $length;
            },
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data ?? []));
        }

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('HTTP request failed: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $flat = [];
        foreach ($headers as $name => $values) {
            $flat[$name] = (string) end($values);
        }

        return [
            'status' => $status,
            'headers' => $flat,
            'headerList' => $headers,
            'body' => (string) $body,
        ];
    }

    /**
     * @return array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string}
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param array<string, string> $data
     * @return array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string}
     */
    public function post(string $path, array $data): array
    {
        return $this->request('POST', $path, $data);
    }

    /**
     * @param array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string} $response
     * @return array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string}
     */
    public function follow(array $response, string $fallback = '/login'): array
    {
        $location = $response['headers']['location'] ?? $fallback;
        $parts = parse_url($location);
        $path = is_array($parts) && isset($parts['path']) ? $parts['path'] : $fallback;
        if (is_array($parts) && isset($parts['query'])) {
            $path .= '?' . $parts['query'];
        }

        return $this->get($path);
    }
}

/* -----------------------------------------------------------------------------
 * Assertions and test harness
 * -------------------------------------------------------------------------- */

final class TestFailure extends RuntimeException
{
}

/**
 * @param array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string} $response
 */
function assertStatus(array $response, int $expected, string $context = ''): void
{
    if ($response['status'] !== $expected) {
        throw new TestFailure(
            'Expected HTTP ' . $expected . ' but received ' . $response['status']
            . ($context !== '' ? ' (' . $context . ')' : '')
            . "\nLocation: " . ($response['headers']['location'] ?? '(none)')
        );
    }
}

function assertContains(string $needle, string $haystack, string $context = ''): void
{
    if (!str_contains($haystack, $needle)) {
        throw new TestFailure(
            'Expected response to contain "' . $needle . '"'
            . ($context !== '' ? ' (' . $context . ')' : '')
        );
    }
}

function assertNotContains(string $needle, string $haystack, string $context = ''): void
{
    if (str_contains($haystack, $needle)) {
        throw new TestFailure(
            'Expected response NOT to contain "' . $needle . '"'
            . ($context !== '' ? ' (' . $context . ')' : '')
        );
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $context = ''): void
{
    if ($expected !== $actual) {
        throw new TestFailure(
            'Expected ' . var_export($expected, true) . ' but received ' . var_export($actual, true)
            . ($context !== '' ? ' (' . $context . ')' : '')
        );
    }
}

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new TestFailure($message);
    }
}

function assertLocationContains(array $response, string $needle): void
{
    $location = $response['headers']['location'] ?? '';
    if (!str_contains($location, $needle)) {
        throw new TestFailure('Expected Location to contain "' . $needle . '", got "' . $location . '"');
    }
}

function extractToken(string $html): string
{
    if (preg_match('/_token"\s+value="([a-f0-9]{64})"/', $html, $matches) !== 1) {
        throw new TestFailure('Could not find a CSRF token in the response.');
    }

    return $matches[1];
}

function tokenFor(HttpClient $client, string $path): string
{
    $response = $client->get($path);
    assertStatus($response, 200, 'load CSRF token from ' . $path);

    return extractToken($response['body']);
}

/**
 * @return array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string}
 */
function loginAs(HttpClient $client, string $email, string $password): array
{
    $page = $client->get('/login');
    assertStatus($page, 200, 'load login page');
    $token = extractToken($page['body']);

    return $client->post('/login', [
        '_token' => $token,
        'email' => $email,
        'password' => $password,
    ]);
}

/**
 * @param array<string, string> $overrides
 * @return array{status: int, headers: array<string, string>, headerList: array<string, list<string>>, body: string}
 */
function createTask(HttpClient $client, array $overrides = []): array
{
    $token = tokenFor($client, '/tasks/create');
    $data = array_merge([
        '_token' => $token,
        'title' => 'Integration task',
        'description' => 'Created by the automated integration suite.',
        'status' => 'pending',
        'priority' => 'medium',
        'due_date' => '',
    ], $overrides);

    return $client->post('/tasks', $data);
}

function taskIdFromRedirect(array $response): int
{
    $location = $response['headers']['location'] ?? '';
    if (preg_match('#/tasks/([0-9]+)$#', $location, $matches) !== 1) {
        throw new TestFailure('Could not read task id from redirect: ' . $location);
    }

    return (int) $matches[1];
}

/**
 * @return array{0: int, 1: int}
 */
function countTasks(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(status = \'completed\'), 0) AS completed FROM tasks WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'completed' => 0];

    return [(int) $row['total'], (int) $row['completed']];
}

/* -----------------------------------------------------------------------------
 * Prepare the disposable database
 * -------------------------------------------------------------------------- */

$sourceDatabase = (string) config('db.name', 'task_tracker');
$testDatabase = 'task_tracker_test';

if (preg_match('/^[A-Za-z0-9_]+$/', $testDatabase) !== 1) {
    fwrite(STDERR, "Invalid test database name.\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;charset=utf8mb4',
    (string) config('db.host'),
    (int) config('db.port')
);

try {
    $adminPdo = new PDO($dsn, (string) config('db.user'), (string) config('db.password'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $adminPdo->exec('DROP DATABASE IF EXISTS `' . $testDatabase . '`');

    $sql = file_get_contents(dirname(__DIR__) . '/database.sql');
    if ($sql === false) {
        throw new RuntimeException('Could not read database.sql.');
    }
    $sql = str_replace('`' . $sourceDatabase . '`', '`' . $testDatabase . '`', $sql);

    $statements = [];
    $current = '';
    $quote = null;
    $length = strlen($sql);
    for ($i = 0; $i < $length; $i++) {
        $character = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote !== null) {
            $current .= $character;
            if ($character === $quote) {
                if ($next === $quote) {
                    $current .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if ($character === "'" || $character === '"' || $character === '`') {
            $quote = $character;
            $current .= $character;
            continue;
        }

        if ($character === '-' && $next === '-') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $current .= "\n";
            continue;
        }

        if ($character === ';') {
            if (trim($current) !== '') {
                $statements[] = trim($current);
            }
            $current = '';
            continue;
        }

        $current .= $character;
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    foreach ($statements as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $adminPdo->exec($statement);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "Database setup failed: " . $exception->getMessage() . "\n");
    exit(1);
}

Config::set('db.name', $testDatabase);
putenv('DB_NAME=' . $testDatabase);
Database::reset();

$pdo = Database::connection();

/* -----------------------------------------------------------------------------
 * Start the application on PHP's built-in server
 * -------------------------------------------------------------------------- */

$port = 8099;
$baseUrl = 'http://127.0.0.1:' . $port;
$serverLog = BASE_PATH . '/storage/logs/test-server.log';
@file_put_contents($serverLog, '');

$phpBinary = PHP_BINARY;
$command = [
    $phpBinary,
    '-c',
    BASE_PATH . '/php.ini',
    '-S',
    '127.0.0.1:' . $port,
    '-t',
    BASE_PATH . '/public',
    BASE_PATH . '/public/router.php',
];

$nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
$descriptors = [
    0 => ['file', $nullDevice, 'r'],
    1 => ['file', $serverLog, 'a'],
    2 => ['file', $serverLog, 'a'],
];

$serverProcess = proc_open($command, $descriptors, $pipes, BASE_PATH);
if (!is_resource($serverProcess)) {
    fwrite(STDERR, "Could not start the PHP development server.\n");
    exit(1);
}

$serverReady = false;
for ($attempt = 0; $attempt < 100; $attempt++) {
    $socket = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.15);
    if (is_resource($socket)) {
        fclose($socket);
        $serverReady = true;
        break;
    }
    usleep(100000);
}

if (!$serverReady) {
    proc_terminate($serverProcess);
    proc_close($serverProcess);
    fwrite(STDERR, "The PHP development server did not start.\n");
    fwrite(STDERR, (string) @file_get_contents($serverLog));
    exit(1);
}

register_shutdown_function(static function () use (&$serverProcess, $adminPdo, $testDatabase): void {
    if (is_resource($serverProcess)) {
        proc_terminate($serverProcess);
        proc_close($serverProcess);
    }

    try {
        $adminPdo->exec('USE `mysql`');
        $adminPdo->exec('DROP DATABASE IF EXISTS `' . $testDatabase . '`');
    } catch (Throwable) {
        // Best-effort cleanup.
    }
});

/* -----------------------------------------------------------------------------
 * Tests
 * -------------------------------------------------------------------------- */

$passed = 0;
$failed = 0;
$failures = [];

/**
 * @param callable(): void $test
 */
function test(string $name, callable $test): void
{
    global $passed, $failed, $failures;

    try {
        $test();
        $passed++;
        echo "  PASS  " . $name . "\n";
    } catch (Throwable $exception) {
        $failed++;
        $failures[] = [$name, $exception];
        echo "  FAIL  " . $name . "\n";
        echo "        " . str_replace("\n", "\n        ", $exception->getMessage()) . "\n";
    }
}

echo "\nTaskFlow integration tests\n";
echo "Database: " . $testDatabase . " · Server: " . $baseUrl . "\n\n";

$demoTaskId = null;
$xssTaskId = null;

test('Guest is redirected from the dashboard to the login page', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $response = $client->get('/tasks');
    assertStatus($response, 302);
    assertLocationContains($response, '/login');

    $login = $client->get('/login');
    assertStatus($login, 200);
    assertContains('name="_token"', $login['body']);
    assertContains('Sign in', $login['body']);

    $register = $client->get('/register');
    assertStatus($register, 200);
    assertContains('Create your account', $register['body']);
});

test('Security headers and hardened session cookie are sent', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $response = $client->get('/login');
    assertStatus($response, 200);

    $headers = $response['headers'];
    assertSameValue('nosniff', $headers['x-content-type-options'] ?? '', 'nosniff header');
    assertSameValue('DENY', $headers['x-frame-options'] ?? '', 'frame options');
    assertContains("default-src 'self'", $headers['content-security-policy'] ?? '', 'CSP');
    assertContains('strict-origin-when-cross-origin', $headers['referrer-policy'] ?? '');

    $cookies = implode(' | ', $response['headerList']['set-cookie'] ?? []);
    assertContains('HttpOnly', $cookies, 'session cookie flags');
    assertContains('SameSite=Lax', $cookies, 'session cookie same-site');
});

test('Wrong password is rejected without revealing whether the account exists', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $response = loginAs($client, 'demo@taskflow.test', 'not-the-password');
    assertStatus($response, 302);
    assertLocationContains($response, '/login');

    $page = $client->follow($response);
    assertContains('The email address or password is incorrect.', $page['body']);
    assertNotContains('Demo@12345', $page['body']);

    assertStatus($client->get('/tasks'), 302);
});

test('Login rejects an empty email address with a friendly validation error', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $login = $client->get('/login');
    $token = extractToken($login['body']);

    $response = $client->post('/login', ['_token' => $token, 'email' => '', 'password' => 'Demo@12345']);
    assertStatus($response, 302);
    assertLocationContains($response, '/login');

    $page = $client->follow($response);
    assertContains('Email address is required.', $page['body']);
});

test('A valid member login opens the dashboard with seeded tasks and stats', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $response = loginAs($client, 'demo@taskflow.test', 'Demo@12345');
    assertStatus($response, 302);
    assertLocationContains($response, '/tasks');

    $dashboard = $client->follow($response);
    assertStatus($dashboard, 200);
    assertContains('Welcome back, Demo User.', $dashboard['body']);
    assertContains('Prepare quarterly report', $dashboard['body']);
    assertContains('data-testid="stat-total">12<', $dashboard['body']);
});

test('State-changing requests without a CSRF token are rejected', function (): void {
    global $pdo;
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');
    $before = countTasks($pdo, 2);

    $response = $client->post('/tasks', [
        'title' => 'No token task',
        'description' => '',
        'status' => 'pending',
        'priority' => 'medium',
        'due_date' => '',
    ]);

    assertStatus($response, 419);
    assertContains('Security check failed', $response['body']);
    assertSameValue($before, countTasks($pdo, 2), 'task count unchanged');
});

test('An empty title is rejected by server-side validation', function (): void {
    global $pdo;
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');
    $before = countTasks($pdo, 2);

    $response = createTask($client, ['title' => '   ']);
    assertStatus($response, 302);
    assertLocationContains($response, '/tasks/create');

    $page = $client->follow($response);
    assertContains('Title is required.', $page['body']);
    assertSameValue($before, countTasks($pdo, 2), 'task count unchanged');
});

test('A valid task can be created and is stored for the signed-in user', function (): void {
    global $pdo, $demoTaskId;

    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $title = 'Integration task ' . bin2hex(random_bytes(4));
    $response = createTask($client, [
        'title' => $title,
        'description' => 'Description created by the integration suite.',
        'status' => 'in_progress',
        'priority' => 'high',
        'due_date' => date('Y-m-d', strtotime('+3 days')),
    ]);

    assertStatus($response, 302);
    $demoTaskId = taskIdFromRedirect($response);

    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = 2');
    $stmt->execute([$demoTaskId]);
    $task = $stmt->fetch();
    assertTrueValue($task !== false, 'Task row was not created.');
    assertSameValue($title, $task['title']);
    assertSameValue('in_progress', $task['status']);
    assertSameValue('high', $task['priority']);

    $show = $client->follow($response);
    assertStatus($show, 200);
    assertContains('was created', $show['body']);
    assertContains(htmlspecialchars($title, ENT_QUOTES), $show['body']);
});

test('User-provided content is HTML-escaped to prevent stored XSS', function (): void {
    global $xssTaskId;

    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $payload = '<script>alert("xss")</script>';
    $response = createTask($client, [
        'title' => $payload,
        'description' => '<img src=x onerror=alert(1)>',
    ]);

    assertStatus($response, 302);
    $xssTaskId = taskIdFromRedirect($response);

    $show = $client->follow($response);
    assertStatus($show, 200);
    assertContains('&lt;script&gt;', $show['body']);
    assertNotContains($payload, $show['body']);
    assertNotContains('<img src=x onerror=alert(1)>', $show['body']);
});

test('A task can be edited and the changes persist', function (): void {
    global $pdo, $demoTaskId;

    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $token = tokenFor($client, '/tasks/' . $demoTaskId . '/edit');
    $newTitle = 'Updated integration task ' . bin2hex(random_bytes(3));
    $response = $client->post('/tasks/' . $demoTaskId, [
        '_token' => $token,
        'title' => $newTitle,
        'description' => 'Updated description.',
        'status' => 'pending',
        'priority' => 'low',
        'due_date' => date('Y-m-d', strtotime('+5 days')),
    ]);

    assertStatus($response, 302);
    assertLocationContains($response, '/tasks/' . $demoTaskId);

    $stmt = $pdo->prepare('SELECT title, description, status, priority FROM tasks WHERE id = ? AND user_id = 2');
    $stmt->execute([$demoTaskId]);
    $task = $stmt->fetch();
    assertSameValue($newTitle, $task['title']);
    assertSameValue('pending', $task['status']);
    assertSameValue('low', $task['priority']);

    $show = $client->follow($response);
    assertContains('was updated', $show['body']);
});

test('Task status can be changed from the dashboard', function (): void {
    global $pdo, $demoTaskId;

    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $token = tokenFor($client, '/tasks');
    $response = $client->post('/tasks/' . $demoTaskId . '/status', [
        '_token' => $token,
        'status' => 'completed',
        'return_to' => '/tasks',
    ]);

    assertStatus($response, 302);
    assertLocationContains($response, '/tasks');

    $stmt = $pdo->prepare('SELECT status FROM tasks WHERE id = ? AND user_id = 2');
    $stmt->execute([$demoTaskId]);
    assertSameValue('completed', (string) $stmt->fetchColumn());

    $page = $client->follow($response);
    assertContains('Status changed to Completed', $page['body']);
});

test('Invalid status and invalid due-date values are rejected', function (): void {
    global $pdo;
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');
    $before = countTasks($pdo, 2);

    $response = createTask($client, ['title' => 'Invalid status task', 'status' => 'hacked']);
    assertStatus($response, 302);
    $page = $client->follow($response);
    assertContains('Status contains an invalid value.', $page['body']);

    $response = createTask($client, ['title' => 'Invalid date task', 'due_date' => '2026-02-30']);
    assertStatus($response, 302);
    $page = $client->follow($response);
    assertContains('Due date must be a valid date', $page['body']);

    assertSameValue($before, countTasks($pdo, 2), 'no invalid tasks were inserted');
});

test('Search returns matching tasks and escaping keeps LIKE input literal', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $search = $client->get('/tasks?q=quarterly');
    assertStatus($search, 200);
    assertContains('Prepare quarterly report', $search['body']);
    assertNotContains('Review pull requests', $search['body']);

    $injection = $client->get('/tasks?q=' . urlencode("' OR 1=1 --"));
    assertStatus($injection, 200);
    assertNotContains('Draft marketing copy', $injection['body']);
    assertNotContains('SQLSTATE', $injection['body']);
});

test('Status filters return only tasks with the selected status', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $pending = $client->get('/tasks?status=pending');
    assertStatus($pending, 200);
    assertContains('Prepare quarterly report', $pending['body']);
    assertNotContains('Review pull requests', $pending['body']);

    $inProgress = $client->get('/tasks?status=in_progress');
    assertStatus($inProgress, 200);
    assertContains('Review pull requests', $inProgress['body']);
    assertNotContains('Prepare quarterly report', $inProgress['body']);

    $completed = $client->get('/tasks?status=completed');
    assertStatus($completed, 200);
    assertContains('Update onboarding docs', $completed['body']);
    assertNotContains('Prepare quarterly report', $completed['body']);
});

test('Pagination limits results and clamps out-of-range pages', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $first = $client->get('/tasks');
    assertStatus($first, 200);
    assertSameValue(8, substr_count($first['body'], 'data-task-id='), 'eight cards on page one');

    $second = $client->get('/tasks?page=2');
    assertStatus($second, 200);
    assertContains('Showing', $second['body']);
    assertContains('data-task-id=', $second['body']);

    $outOfRange = $client->get('/tasks?page=999');
    assertStatus($outOfRange, 200);
    assertContains('aria-current="page"', $outOfRange['body']);
});

test("Another user's task cannot be viewed, edited, deleted or have its status changed", function (): void {
    global $pdo, $demoTaskId;

    $stmt = $pdo->prepare('SELECT title, status FROM tasks WHERE id = ? AND user_id = 2');
    $stmt->execute([$demoTaskId]);
    $original = $stmt->fetch();

    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'jordan@taskflow.test', 'Jordan@12345');

    $show = $client->get('/tasks/' . $demoTaskId);
    assertStatus($show, 404);
    assertContains('could not be found', $show['body']);
    assertNotContains((string) $original['title'], $show['body']);

    assertStatus($client->get('/tasks/' . $demoTaskId . '/edit'), 404);
    assertStatus($client->get('/tasks/' . $demoTaskId . '/delete'), 404);

    $token = tokenFor($client, '/tasks/create');

    assertStatus($client->post('/tasks/' . $demoTaskId, [
        '_token' => $token,
        'title' => 'Hijacked title',
        'description' => '',
        'status' => 'completed',
        'priority' => 'high',
        'due_date' => '',
    ]), 404);

    assertStatus($client->post('/tasks/' . $demoTaskId . '/status', [
        '_token' => $token,
        'status' => 'completed',
        'return_to' => '/tasks',
    ]), 404);

    assertStatus($client->post('/tasks/' . $demoTaskId . '/delete', [
        '_token' => $token,
    ]), 404);

    $stmt = $pdo->prepare('SELECT title, status FROM tasks WHERE id = ? AND user_id = 2');
    $stmt->execute([$demoTaskId]);
    $after = $stmt->fetch();
    assertSameValue($original, $after, 'foreign task was not modified');
});

test('Admin can manage accounts but cannot read another user\'s task content', function (): void {
    global $demoTaskId;

    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'admin@taskflow.test', 'Admin@12345');

    $task = $client->get('/tasks/' . $demoTaskId);
    assertStatus($task, 404);

    $users = $client->get('/admin/users');
    assertStatus($users, 200);
    assertContains('User management', $users['body']);
    assertContains('demo@taskflow.test', $users['body']);
    assertContains("cannot view or change another user's task content", $users['body']);
});

test('A standard member cannot access the admin area', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $response = $client->get('/admin/users');
    assertStatus($response, 403);
    assertContains('Access denied', $response['body']);
});

test('Deactivating an account blocks new and existing sessions', function (): void {
    global $pdo;
    $admin = new HttpClient('http://127.0.0.1:8099');
    loginAs($admin, 'admin@taskflow.test', 'Admin@12345');

    $jordan = new HttpClient('http://127.0.0.1:8099');
    loginAs($jordan, 'jordan@taskflow.test', 'Jordan@12345');
    assertStatus($jordan->get('/tasks'), 200);

    $token = tokenFor($admin, '/admin/users');
    $response = $admin->post('/admin/users/3/toggle', ['_token' => $token]);
    assertStatus($response, 302);

    $expected = $pdo->query('SELECT is_active FROM users WHERE id = 3')->fetchColumn();
    assertSameValue(0, (int) $expected);

    assertStatus($jordan->get('/tasks'), 302);

    $blockedClient = new HttpClient('http://127.0.0.1:8099');
    $blockedLogin = loginAs($blockedClient, 'jordan@taskflow.test', 'Jordan@12345');
    assertStatus($blockedLogin, 302);
    $blockedPage = $blockedClient->follow($blockedLogin);
    assertContains('The email address or password is incorrect.', $blockedPage['body']);

    $token = tokenFor($admin, '/admin/users');
    $admin->post('/admin/users/3/toggle', ['_token' => $token]);
    assertSameValue(1, (int) $pdo->query('SELECT is_active FROM users WHERE id = 3')->fetchColumn());
});

test('An administrator cannot deactivate their own account', function (): void {
    global $pdo;
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'admin@taskflow.test', 'Admin@12345');
    $token = tokenFor($client, '/admin/users');

    $response = $client->post('/admin/users/1/toggle', ['_token' => $token]);
    assertStatus($response, 302);
    $page = $client->follow($response);
    assertContains('cannot deactivate your own account', $page['body']);
    assertSameValue(1, (int) $pdo->query('SELECT is_active FROM users WHERE id = 1')->fetchColumn());
});

test('Passwords are stored as verifiable hashes, never as plain text', function (): void {
    global $pdo;
    $hash = (string) $pdo->query("SELECT password_hash FROM users WHERE email = 'demo@taskflow.test'")->fetchColumn();
    assertNotContains('Demo@12345', $hash);
    assertTrueValue(str_starts_with($hash, '$2y$'), 'Expected a bcrypt hash.');
    assertTrueValue(password_verify('Demo@12345', $hash), 'password_verify() should accept the demo password.');
    assertTrueValue(!password_verify('wrong-password', $hash), 'password_verify() should reject a wrong password.');
});

test('A task can be deleted only after a confirmation page', function (): void {
    global $pdo, $demoTaskId;

    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $confirm = $client->get('/tasks/' . $demoTaskId . '/delete');
    assertStatus($confirm, 200);
    assertContains('Delete this task?', $confirm['body']);
    assertContains('Yes, delete task', $confirm['body']);

    $token = extractToken($confirm['body']);
    $response = $client->post('/tasks/' . $demoTaskId . '/delete', ['_token' => $token]);
    assertStatus($response, 302);
    assertLocationContains($response, '/tasks');

    assertSameValue(0, (int) $pdo->query('SELECT COUNT(*) FROM tasks WHERE id = ' . (int) $demoTaskId)->fetchColumn());

    $page = $client->follow($response);
    assertContains('was deleted', $page['body']);
});

test('Registration creates a hashed member account and signs the user in', function (): void {
    global $pdo;
    $client = new HttpClient('http://127.0.0.1:8099');
    $page = $client->get('/register');
    $token = extractToken($page['body']);
    $email = 'newuser' . bin2hex(random_bytes(4)) . '@example.test';

    $response = $client->post('/register', [
        '_token' => $token,
        'name' => 'New Test User',
        'email' => $email,
        'password' => 'NewUser@123',
        'password_confirmation' => 'NewUser@123',
    ]);

    assertStatus($response, 302);
    assertLocationContains($response, '/tasks');

    $dashboard = $client->follow($response);
    assertStatus($dashboard, 200);
    assertContains('Your account has been created', $dashboard['body']);
    assertContains('data-testid="stat-total">0<', $dashboard['body']);

    $stmt = $pdo->prepare('SELECT name, email, password_hash, role, is_active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    assertTrueValue($user !== false, 'Registered user was not stored.');
    assertSameValue('user', $user['role']);
    assertSameValue(1, (int) $user['is_active']);
    assertTrueValue(password_verify('NewUser@123', (string) $user['password_hash']), 'Hash should verify.');
});

test('Registration rejects duplicate emails and weak passwords', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $page = $client->get('/register');
    $token = extractToken($page['body']);

    $duplicate = $client->post('/register', [
        '_token' => $token,
        'name' => 'Duplicate User',
        'email' => 'demo@taskflow.test',
        'password' => 'NewUser@123',
        'password_confirmation' => 'NewUser@123',
    ]);
    assertStatus($duplicate, 302);
    assertContains('already exists', $client->follow($duplicate)['body']);

    $weak = $client->post('/register', [
        '_token' => $token,
        'name' => 'Weak Password',
        'email' => 'weak' . bin2hex(random_bytes(3)) . '@example.test',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);
    assertStatus($weak, 302);
    assertContains('at least 8 characters', $client->follow($weak)['body']);
});

test('Registration without a CSRF token is rejected', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $response = $client->post('/register', [
        'name' => 'No Token',
        'email' => 'notoken' . bin2hex(random_bytes(3)) . '@example.test',
        'password' => 'NewUser@123',
        'password_confirmation' => 'NewUser@123',
    ]);

    assertStatus($response, 419);
});

test('Repeated failed logins are throttled', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    $page = $client->get('/login');
    $token = extractToken($page['body']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $response = $client->post('/login', [
            '_token' => $token,
            'email' => 'demo@taskflow.test',
            'password' => 'wrong-password-' . $attempt,
        ]);
        assertStatus($response, 302);
    }

    $blocked = $client->post('/login', [
        '_token' => $token,
        'email' => 'demo@taskflow.test',
        'password' => 'wrong-password-final',
    ]);
    assertStatus($blocked, 302);
    assertContains('Too many failed sign-in attempts', $client->follow($blocked)['body']);
});

test('Logout destroys the session and protects the dashboard again', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');

    $token = tokenFor($client, '/tasks');
    $response = $client->post('/logout', ['_token' => $token]);
    assertStatus($response, 302);
    assertLocationContains($response, '/login');

    assertStatus($client->get('/tasks'), 302);
});

test('Unknown routes return 404 and wrong methods return 405', function (): void {
    $client = new HttpClient('http://127.0.0.1:8099');

    $missing = $client->get('/this-page-does-not-exist');
    assertStatus($missing, 404);
    assertContains('Page not found', $missing['body']);

    $wrongMethod = $client->get('/logout');
    assertStatus($wrongMethod, 405);

    $loggedOut = new HttpClient('http://127.0.0.1:8099');
    loginAs($loggedOut, 'demo@taskflow.test', 'Demo@12345');
    $redirect = $loggedOut->get('/login');
    assertStatus($redirect, 302);
    assertLocationContains($redirect, '/tasks');
});

test('Dashboard summary numbers agree with the database', function (): void {
    global $pdo;
    $client = new HttpClient('http://127.0.0.1:8099');
    loginAs($client, 'demo@taskflow.test', 'Demo@12345');
    $dashboard = $client->get('/tasks');
    assertStatus($dashboard, 200);

    $total = (int) $pdo->query('SELECT COUNT(*) FROM tasks WHERE user_id = 2')->fetchColumn();
    $pending = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE user_id = 2 AND status = 'pending'")->fetchColumn();
    $progress = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE user_id = 2 AND status = 'in_progress'")->fetchColumn();
    $completed = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE user_id = 2 AND status = 'completed'")->fetchColumn();

    foreach (['total' => $total, 'pending' => $pending, 'progress' => $progress, 'completed' => $completed] as $key => $expected) {
        if (preg_match('/data-testid="stat-' . $key . '">([0-9]+)</', $dashboard['body'], $matches) !== 1) {
            throw new TestFailure('Missing stat card: ' . $key);
        }
        assertSameValue($expected, (int) $matches[1], 'stat ' . $key);
    }
});

echo "\n" . str_repeat('-', 60) . "\n";
echo "Result: " . $passed . " passed, " . $failed . " failed\n";

if ($failed > 0) {
    echo "\nFailures:\n";
    foreach ($failures as [$name, $exception]) {
        echo ' - ' . $name . ': ' . $exception->getMessage() . "\n";
    }
    echo "\nServer log: " . $serverLog . "\n";
    exit(1);
}

echo "All integration tests passed.\n";
exit(0);
