<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfonycasts\DynamicForms\Tests\fixtures\DynamicFormsTestKernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$kernel = new DynamicFormsTestKernel($_SERVER['APP_ENV'] ?? 'dev', true);
$kernel->boot();

$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
