<?php

require __DIR__ . '/../vendor/autoload.php';

use PreviewEndpoints\Http\PreviewController;
use PreviewEndpoints\Http\PreviewErrorResponse;
use PreviewEndpoints\Http\Request;
use PreviewContracts\PreviewPage;
use PreviewEndpoints\Renderer\ArrayJsonRenderer;
use PreviewEndpoints\Repository\ArrayPageRepository;
use PreviewSessionStore\PreviewSessionStore;

$request = Request::fromGlobals();
$store = new PreviewSessionStore(__DIR__ . '/../storage');

$repository = new ArrayPageRepository([
    'auth.login' => new PreviewPage('auth.login', [
        ['type' => 'text', 'name' => 'title', 'value' => 'Login'],
        ['type' => 'button', 'name' => 'submit', 'label' => 'Sign in'],
    ], ['title' => 'Login screen']),
]);

$controller = new PreviewController($store, $repository, new ArrayJsonRenderer());

if ($request->method !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(PreviewErrorResponse::methodNotAllowed()->toArray(), JSON_UNESCAPED_SLASHES);
    exit;
}

if (preg_match('#^/api/session/([^/]+)$#', $request->path, $matches)) {
    $response = $controller->session($matches[1]);
} elseif (preg_match('#^/api/preview/([^/]+)$#', $request->path, $matches)) {
    $response = $controller->preview($matches[1]);
} else {
    $response = new \PreviewEndpoints\Http\Response(404, json_encode(PreviewErrorResponse::notFound()->toArray(), JSON_UNESCAPED_SLASHES) ?: '{}', ['Content-Type' => 'application/json']);
}

http_response_code($response->statusCode);
foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}
echo $response->body;
