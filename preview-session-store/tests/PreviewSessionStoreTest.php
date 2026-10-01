<?php

require __DIR__ . '/../../vendor/autoload.php';

use PreviewSessionStore\Exceptions\PreviewSessionNotFoundException;
use PreviewSessionStore\PreviewSessionStore;

function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'preview_store_test_' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0775, true);

try {
    $store = new PreviewSessionStore($tmpDir);
    $session = $store->create('auth.login', 'http://127.0.0.1:8000');

    ensure($session->view === 'auth.login', 'Expected session view auth.login');
    ensure(strlen($session->id) >= 1, 'Expected session id to be non-empty');
    ensure($session->expiresAt !== null, 'Expected default session TTL to set expiresAt');

    $shortSession = $store->create('auth.login', 'http://127.0.0.1:8000', 1);
    ensure($shortSession->expiresAt !== null, 'Expected custom session TTL to set expiresAt');

    $invalidTtlCaught = false;
    try {
        $store->create('auth.login', 'http://127.0.0.1:8000', 0);
    } catch (InvalidArgumentException) {
        $invalidTtlCaught = true;
    }
    ensure($invalidTtlCaught, 'Expected non-positive TTL to be rejected');

    $loaded = $store->get($session->id);
    ensure($loaded !== null, 'Expected loaded session to exist');
    ensure($loaded->id === $session->id, 'Expected loaded session id to match');

    $file = $tmpDir . DIRECTORY_SEPARATOR . 'preview_sessions.json';
    ensure(is_file($file), 'Expected storage file to exist');
    $json = json_decode((string) file_get_contents($file), true);
    ensure(is_array($json), 'Expected storage file to contain valid JSON');
    ensure(array_key_exists($session->id, $json), 'Expected storage file to contain created session');

    $caught = false;
    try {
        $store->getOrFail('nonexistent-id');
    } catch (PreviewSessionNotFoundException) {
        $caught = true;
    }
    ensure($caught, 'Expected getOrFail to throw for missing session');

    ensure($store->delete($session->id) === true, 'Expected delete to return true');
    ensure($store->get($session->id) === null, 'Expected deleted session to be absent');

    echo "PreviewSessionStore assertions passed.\n";
} finally {
    $file = $tmpDir . DIRECTORY_SEPARATOR . 'preview_sessions.json';
    if (is_file($file)) {
        unlink($file);
    }
    @rmdir($tmpDir);
}
