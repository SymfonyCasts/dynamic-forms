<?php

declare(strict_types=1);

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;
use Symfonycasts\DynamicForms\Tests\fixtures\DynamicFormsTestKernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$kernel = new DynamicFormsTestKernel($_SERVER['APP_ENV'] ?? 'dev', true);
$kernel->boot();

$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
