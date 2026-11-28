<?php

use App\Console\Commands\ResetAdminPasswordCommand;
use App\Services\Logs\AdminErrorLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ResetAdminPasswordCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e): void {
            if (app()->runningUnitTests()) {
                return;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return;
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return;
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                && $e->getStatusCode() < 500) {
                return;
            }

            AdminErrorLogger::fromThrowable($e);
        });
    })->create();
