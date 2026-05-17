<?php

require __DIR__ . '/../vendor/autoload.php';

use PreviewEndpoints\Http\PreviewController;
use PreviewEndpoints\PreviewPage;
use PreviewEndpoints\Renderer\ArrayJsonRenderer;
use PreviewEndpoints\Repository\ArrayPageRepository;
use PreviewSessionStore\PreviewSessionStore;

function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'preview_endpoints_' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0775, true);

try {
    $store = new PreviewSessionStore($tmpDir);
    $session = $store->create('auth.login', 'http://127.0.0.1:8000');

    $repository = new ArrayPageRepository([
        'auth.login' => new PreviewPage('auth.login', [
            ['type' => 'text', 'name' => 'title', 'value' => 'Login'],
            ['type' => 'button', 'name' => 'submit', 'label' => 'Sign in'],
        ], ['title' => 'Login screen']),
    ]);

    $controller = new PreviewController($store, $repository, new ArrayJsonRenderer());

    $sessionResponse = $controller->session($session->id);
    ensure($sessionResponse->statusCode === 200, 'Expected session endpoint to return 200');
    $sessionBody = json_decode($sessionResponse->body, true);
    ensure(is_array($sessionBody), 'Expected session response to be valid JSON');
    ensure(($sessionBody['id'] ?? null) === $session->id, 'Expected session id in response');

    $previewResponse = $controller->preview($session->id);
    ensure($previewResponse->statusCode === 200, 'Expected preview endpoint to return 200');
    $previewBody = json_decode($previewResponse->body, true);
    ensure(is_array($previewBody), 'Expected preview response to be valid JSON');
    ensure(($previewBody['schemaVersion'] ?? null) === 1, 'Expected schemaVersion 1');
    ensure(($previewBody['screen'] ?? null) === 'auth.login', 'Expected screen auth.login');
    ensure(count($previewBody['components'] ?? []) === 2, 'Expected 2 preview components');

    $missingSessionResponse = $controller->session('missing-id');
    ensure($missingSessionResponse->statusCode === 404, 'Expected missing session to return 404');
    $missingSessionBody = json_decode($missingSessionResponse->body, true);
    ensure(($missingSessionBody['error']['code'] ?? null) === 'SESSION_NOT_FOUND', 'Expected SESSION_NOT_FOUND error code');

    $missingPreviewResponse = $controller->preview('missing-id');
    ensure($missingPreviewResponse->statusCode === 404, 'Expected missing preview session to return 404');

    $missingPageSession = $store->create('unknown.page', 'http://127.0.0.1:8000');
    $missingPageResponse = $controller->preview($missingPageSession->id);
    ensure($missingPageResponse->statusCode === 404, 'Expected missing page to return 404');
    $missingPageBody = json_decode($missingPageResponse->body, true);
    ensure(($missingPageBody['error']['code'] ?? null) === 'PAGE_NOT_FOUND', 'Expected PAGE_NOT_FOUND error code');

    echo "PreviewController assertions passed.\n";
} finally {
    $storageFile = $tmpDir . DIRECTORY_SEPARATOR . 'preview_sessions.json';
    if (is_file($storageFile)) {
        unlink($storageFile);
    }
    @rmdir($tmpDir);
}
