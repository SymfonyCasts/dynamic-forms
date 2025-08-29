<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfonycasts\DynamicForms\Tests\fixtures\DynamicFormsTestKernel;

require __DIR__ . '/../../vendor/autoload.php';

$kernel = new DynamicFormsTestKernel('test', true);
$kernel->boot();

$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
