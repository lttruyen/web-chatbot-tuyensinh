<?php

$purifierCache = __DIR__ . '/../storage/app/purifier';
if (!is_dir($purifierCache)) {
    @mkdir($purifierCache, 0775, true);
}
@chmod($purifierCache, 0775);

if (!defined('HTMLPURIFIER_CACHE')) {
    define('HTMLPURIFIER_CACHE', $purifierCache);
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\InjectQuyen;
use App\Http\Middleware\RequireLogin;
use App\Http\Middleware\TrustProxies;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(TrustProxies::class);

        $middleware->alias([
            'session.role' => InjectQuyen::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
