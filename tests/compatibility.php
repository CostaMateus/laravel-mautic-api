<?php

// One file doubles as a local HTTP server; no Mautic credentials or external calls.
if (PHP_SAPI === 'cli-server') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_URI'] === '/oauth/v2/token') {
        $input = $_POST ?: json_decode(file_get_contents('php://input'), true);
        if (($input['client_id'] ?? '') !== 'key' || ($input['client_secret'] ?? '') !== 'secret') {
            http_response_code(401);
            echo '{"error":"invalid_client"}';
        } elseif (($input['refresh_token'] ?? '') === 'rejected') {
            http_response_code(400);
            echo '{"error":"invalid_grant"}';
        } else {
            echo '{"access_token":"renewed","refresh_token":"next-refresh","token_type":"bearer","expires_in":3600}';
        }
    } elseif ($_SERVER['REQUEST_URI'] === '/api/missing') {
        http_response_code(404);
        echo '{}';
    } else {
        echo json_encode(['authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null, 'body' => $_POST]);
    }
    return;
}

require getenv('MAUTIC_TEST_AUTOLOAD') ?: __DIR__.'/../vendor/autoload.php';

use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use CostaMateus\Mautic\Factories\MauticFactory;
use CostaMateus\Mautic\Mautic;
use CostaMateus\Mautic\MauticServiceProvider;
use CostaMateus\Mautic\Models\MauticConsumer;

error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

function check($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = getenv('MAUTIC_TEST_ROOT') ?: dirname(__DIR__);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
check($socket !== false, $error);
$address = stream_socket_get_name($socket, false);
fclose($socket);
$process = proc_open([PHP_BINARY, '-S', $address, __FILE__], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
check(is_resource($process), 'Cannot start local HTTP server');
try {
    $ready = false;
    for ($i = 0; $i < 100; $i++) {
        $probe = @stream_socket_client('tcp://'.$address, $errno, $error, 0.1);
        if ($probe) {
            fclose($probe);
            $ready = true;
            break;
        }
        usleep(50000);
    }
    check($ready, 'Local HTTP server did not start');

    $app = new Application($root);
    $app->instance('config', new Repository([
        'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]],
    ]));
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($app);
    $app->register(DatabaseServiceProvider::class);
    $app->boot();

    $tests = [];
    $tests['provider boots without application facade aliases and merges defaults'] = static function () use ($app) {
        $app->register(MauticServiceProvider::class);
        check(config('mautic.default') === 'main', 'Missing package configuration defaults');
        check($app->make('mautic') instanceof Mautic, 'Manager binding failed');
        check($app->make(Mautic::class) === $app->make('mautic'), 'Manager class alias failed');
        check($app->make(MauticFactory::class) === $app->make('mautic.factory'), 'Factory class alias failed');
    };
    $tests['published migration runs without a global Schema alias'] = static function () use ($root) {
        require_once $root.'/src/migrations/0000_00_00_000000_mautic_api_consumer_key.php';
        (new MauticApiConsumerKey())->up();
        check(Schema::hasTable('mautic_consumer'), 'Migration did not create token table');
    };
    $tests['controller loads independently of the host application controller'] = static function () {
        check(class_exists(\CostaMateus\Mautic\Http\Controllers\MauticController::class), 'Controller cannot autoload');
    };
    $tests['callback route dispatches and can be serialized for route caching'] = static function () use ($app) {
        MauticConsumer::create(['access_token' => 'existing', 'refresh_token' => 'refresh', 'token_type' => 'bearer', 'expires' => time() + 3600]);
        ob_start();
        try {
            $response = $app['router']->dispatch(Request::create('/mautic/application/register'));
            $body = ob_get_contents().$response->getContent();
        } finally {
            ob_end_clean();
        }
        check(str_contains($body, 'Already Register'), 'Callback route did not dispatch');
        foreach ($app['router']->getRoutes() as $route) {
            $route->prepareForSerialization();
        }
        check(count($app['router']->getRoutes()->compile()['attributes']) === 1, 'Callback route did not compile');
    };

    $factory = new MauticFactory();
    $manager = new Mautic($app['config'], $factory);
    $tests['valid OAuth token authenticates the HTTP request'] = static function () use ($manager) {
        check($manager->request('POST', 'contacts/new', ['email' => 'test@example.test']) === [
            'authorization' => 'Bearer existing', 'body' => ['email' => 'test@example.test'],
        ], 'OAuth request payload or bearer token is incorrect');
    };
    $tests['expired OAuth token is renewed and persisted before the request'] = static function () use ($manager) {
        MauticConsumer::query()->update(['expires' => time() - 10]);
        $response = $manager->request('GET', 'contacts');
        check($response['authorization'] === 'Bearer renewed', 'Expired token was used');
        check(MauticConsumer::where('access_token', 'renewed')->where('refresh_token', 'next-refresh')->exists(), 'Refreshed token was not persisted');
    };
    $tests['subsequent requests reuse the newest token when timestamps tie'] = static function () use ($manager) {
        MauticConsumer::query()->update(['created_at' => '2026-01-01 00:00:00']);
        $count = MauticConsumer::count();
        check($manager->request('GET', 'contacts')['authorization'] === 'Bearer renewed', 'Newest token was not used');
        check(MauticConsumer::count() === $count, 'Timestamp tie caused an unnecessary token refresh');
    };
    $tests['failed refresh preserves the existing token record'] = static function () use ($factory) {
        $count = MauticConsumer::count();
        check($factory->refreshToken('rejected') === ['error' => 'invalid_grant'], 'Refresh error contract changed');
        check(MauticConsumer::count() === $count, 'Refresh failure changed saved tokens');
    };
    $tests['HTTP client errors preserve the status-code return contract'] = static function () use ($factory) {
        check($factory->callMautic('GET', 'missing', null, 'token') === 404, 'Expected HTTP 404');
    };
    $tests['expiry boundary rejects a token expiring now'] = static function () use ($factory) {
        check($factory->checkExpirationTime(time()), 'Token expiring now was accepted');
        check(!$factory->checkExpirationTime(time() + 3600), 'Future token was rejected');
    };
    $tests['BasicAuth works without the OAuth table or tokens'] = static function () use ($manager) {
        Schema::drop('mautic_consumer');
        config(['mautic.default' => 'basic']);
        check($manager->request('GET', 'contacts')['authorization'] === 'Basic dXNlcjpwYXNz', 'BasicAuth requires OAuth or sends incorrect credentials');
        (new MauticApiConsumerKey())->up();
        config(['mautic.default' => 'main']);
    };
    $tests['incomplete OAuth flow raises a package exception'] = static function () use ($app) {
        $factory = new class extends MauticFactory {
            public function make(array $config) { return null; }
        };
        try {
            (new Mautic($app['config'], $factory))->request('GET', 'contacts');
        } catch (\CostaMateus\Mautic\Exceptions\MauticException $e) {
            return;
        }
        throw new RuntimeException('Expected MauticException for missing token');
    };
    $tests['SDK OAuth callback exchanges the code and saves the token with an active session'] = static function () use ($factory) {
        session_start();
        $_SESSION['oauth']['state'] = 'test-state';
        $_GET = ['state' => 'test-state', 'code' => 'test-code'];
        try {
            $consumer = $factory->make(config('mautic.connections.main'));
            check($consumer instanceof MauticConsumer && $consumer->access_token === 'renewed', 'SDK callback did not persist the access token');
            check($consumer->expires > time(), 'SDK token expiry was not saved');
        } finally {
            $_GET = [];
            $_SESSION = [];
            session_destroy();
        }
    };
    $tests['rejected refresh falls back to OAuth and authenticates with the replacement token'] = static function () use ($manager) {
        MauticConsumer::query()->delete();
        MauticConsumer::create(['access_token' => 'expired', 'refresh_token' => 'rejected', 'token_type' => 'bearer', 'expires' => time() - 10]);
        session_start();
        $_SESSION['oauth']['state'] = 'fallback-state';
        $_GET = ['state' => 'fallback-state', 'code' => 'fallback-code'];
        try {
            check($manager->request('GET', 'contacts')['authorization'] === 'Bearer renewed', 'Failed refresh did not fall back to OAuth');
        } finally {
            $_GET = [];
            $_SESSION = [];
            session_destroy();
        }
    };

    $results = [];
    $failures = 0;
    foreach ($tests as $name => $test) {
        try {
            // Keep later checks independent of configuration/migration failures.
            if ($name === 'controller loads independently of the host application controller') {
                config(['mautic' => require $root.'/src/config/mautic.php']);
                config([
                    'mautic.connections.main.baseUrl' => 'http://'.$address,
                    'mautic.connections.main.clientKey' => 'key',
                    'mautic.connections.main.clientSecret' => 'secret',
                    'mautic.connections.main.callback' => 'http://localhost/callback',
                    'mautic.connections.basic.baseUrl' => 'http://'.$address,
                    'mautic.connections.basic.username' => 'user',
                    'mautic.connections.basic.password' => 'pass',
                ]);
            }
            $test();
            $results[] = 'PASS '.$name;
        } catch (Throwable $e) {
            $failures++;
            $results[] = 'FAIL '.$name.': '.$e->getMessage();
        }
    }
    echo implode(PHP_EOL, $results).PHP_EOL;
    echo count($tests).' checks, '.$failures.' failures; PHP '.PHP_VERSION.'; Laravel '.Application::VERSION.PHP_EOL;
} finally {
    proc_terminate($process);
    foreach ($pipes as $pipe) { fclose($pipe); }
    proc_close($process);
}
exit($failures ? 1 : 0);
