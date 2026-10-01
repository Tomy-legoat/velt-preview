<?php

require __DIR__ . '/../../vendor/autoload.php';

use PreviewContracts\PreviewPage;
use PreviewContracts\Contracts\JsonRendererInterface;
use PreviewEndpoints\Http\PreviewController;
use PreviewEndpoints\Renderer\ArrayJsonRenderer;
use PreviewEndpoints\Repository\ArrayPageRepository;
use PreviewSessionStore\PreviewSessionStore;
use PreviewProtocol\Signature\SessionSignature;

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
    $repository = new ArrayPageRepository([
        'auth.login' => new PreviewPage('auth.login', [
            ['type' => 'text', 'name' => 'title', 'value' => 'Login'],
            ['type' => 'button', 'name' => 'submit', 'label' => 'Sign in'],
        ], ['title' => 'Login screen']),
    ]);

    $controller = new PreviewController($store, $repository, new ArrayJsonRenderer());
    $session = $store->create('auth.login', 'http://127.0.0.1:8000');

    $sessionResponse = $controller->session($session->id);
    ensure($sessionResponse->statusCode === 200, 'Expected session endpoint to return 200');
    $sessionBody = json_decode($sessionResponse->body, true);
    ensure(is_array($sessionBody), 'Expected session response to be valid JSON');
    ensure(($sessionBody['id'] ?? null) === $session->id, 'Expected session id in response');

    $previewResponse = $controller->preview($session->id);
    ensure($previewResponse->statusCode === 200, 'Expected preview endpoint to return 200');
    $previewBody = json_decode($previewResponse->body, true);
    ensure(is_array($previewBody), 'Expected preview response to be valid JSON');
    ensure(($previewBody['schemaVersion'] ?? null) === '1.0', 'Expected schemaVersion 1.0');
    ensure(($previewBody['screen'] ?? null) === 'auth.login', 'Expected screen auth.login');
    ensure(count($previewBody['components'] ?? []) === 2, 'Expected 2 preview components');

    $negotiatedResponse = $controller->negotiate($session->id, [
        'protocolVersion' => '1.2.0', 'capabilities' => ['authenticated', 'session_resume'],
    ]);
    ensure($negotiatedResponse->statusCode === 200, 'Expected compatible protocol negotiation');
    $negotiatedBody = json_decode($negotiatedResponse->body, true);
    $negotiatedCapabilities = $negotiatedBody['capabilities'];
    sort($negotiatedCapabilities);
    ensure($negotiatedCapabilities === ['authenticated', 'session_resume'], 'Expected capability intersection');
    $storedCapabilities = $store->get($session->id)->capabilities;
    sort($storedCapabilities);
    ensure($storedCapabilities === ['authenticated', 'session_resume'], 'Expected negotiated capabilities persisted');
    $incompatibleResponse = $controller->negotiate($session->id, [
        'protocolVersion' => '2.0.0', 'capabilities' => ['authenticated'],
    ]);
    ensure($incompatibleResponse->statusCode === 422, 'Expected incompatible major version rejected');
    $malformedResponse = $controller->negotiate($session->id, [
        'protocolVersion' => 'abc', 'capabilities' => ['authenticated'],
    ]);
    ensure($malformedResponse->statusCode === 422, 'Expected malformed protocol version rejected');

    $heartbeatResponse = $controller->heartbeat($session->id);
    ensure($heartbeatResponse->statusCode === 200, 'Expected heartbeat to return 200');
    $heartbeatBody = json_decode($heartbeatResponse->body, true);
    ensure(is_int($heartbeatBody['lastHeartbeat'] ?? null), 'Expected heartbeat timestamp');

    $resumeResponse = $controller->resume($session->id, 0);
    ensure($resumeResponse->statusCode === 200, 'Expected resume at current sequence to return 200');

    $invalidResumeResponse = $controller->resume($session->id, 1);
    ensure($invalidResumeResponse->statusCode === 422, 'Expected resume beyond current sequence to return 422');

    $signedController = new PreviewController(
        $store,
        $repository,
        new ArrayJsonRenderer(),
        new SessionSignature('this-is-a-test-secret-key-at-least-32-chars-long')
    );
    $signedToken = (new SessionSignature('this-is-a-test-secret-key-at-least-32-chars-long'))
        ->generateToken($session->id);
    $signedResponse = $signedController->preview($session->id, array_map('strval', $signedToken));
    ensure($signedResponse->statusCode === 200, 'Expected signed preview request to return 200');
    $replayedResponse = $signedController->preview($session->id, array_map('strval', $signedToken));
    ensure($replayedResponse->statusCode === 401, 'Expected replayed token to return 401');
    $unsignedResponse = $signedController->preview($session->id);
    ensure($unsignedResponse->statusCode === 401, 'Expected unsigned request to return 401');

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

    $storedSessions = json_decode((string) file_get_contents($tmpDir . DIRECTORY_SEPARATOR . 'preview_sessions.json'), true);
    $storedSessions[$session->id]['expiresAt'] = '2000-01-01T00:00:00+00:00';
    file_put_contents(
        $tmpDir . DIRECTORY_SEPARATOR . 'preview_sessions.json',
        json_encode($storedSessions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $expiredResponse = $controller->preview($session->id);
    ensure($expiredResponse->statusCode === 410, 'Expected expired session to return 410');
    $expiredBody = json_decode($expiredResponse->body, true);
    ensure(($expiredBody['error']['code'] ?? null) === 'SESSION_EXPIRED', 'Expected SESSION_EXPIRED error code');

    $failingRenderer = new class implements JsonRendererInterface {
        public function render(PreviewPage $page): string
        {
            throw new RuntimeException('renderer failure');
        }
    };
    $errorController = new PreviewController($store, $repository, $failingRenderer);
    $errorSession = $store->create('auth.login', 'http://127.0.0.1:8000');
    $internalErrorResponse = $errorController->preview($errorSession->id);
    ensure($internalErrorResponse->statusCode === 500, 'Expected renderer failure to return 500');
    $internalErrorBody = json_decode($internalErrorResponse->body, true);
    ensure(($internalErrorBody['error']['code'] ?? null) === 'INTERNAL_ERROR', 'Expected INTERNAL_ERROR error code');

    echo "PreviewController assertions passed.\n";
} finally {
    $storageFile = $tmpDir . DIRECTORY_SEPARATOR . 'preview_sessions.json';
    if (is_file($storageFile)) {
        unlink($storageFile);
    }
    @rmdir($tmpDir);
}

