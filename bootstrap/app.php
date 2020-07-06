<?php

/*
 * This file is part of the dev-community code formatter
 * (c) The dev-community.de authors
 */

use DevCommunityDE\CodeFormatter\Api\Auth\ApiAuthorizer;
use DevCommunityDE\CodeFormatter\CodeFormatterApp;
use DevCommunityDE\CodeFormatter\Parser\Parser;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists($path = __DIR__ . '/env.php')) {
    require $path;
}

$auth = new ApiAuthorizer();
$auth->authorize();

$app = new CodeFormatterApp(new Parser());
$app->run();
