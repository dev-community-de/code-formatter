<?php

use DevCommunityDE\CodeFormatter\CodeFormatterApp;
use DevCommunityDE\CodeFormatter\Api\Auth\ApiAuthorizer;

require_once __DIR__ . '/../vendor/autoload.php';

$auth = new ApiAuthorizer;
$auth->authorize();

$app = new CodeFormatterApp;
$app->run();
