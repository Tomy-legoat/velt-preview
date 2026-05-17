<?php
use PreviewSessionStore\PreviewSessionStore;
use PreviewSessionStore\Exceptions\PreviewSessionNotFoundException;

require_once __DIR__ . '/../vendor/autoload.php';

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'preview_store_test_' . bin2hex(random_bytes(4));
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0775, true);
}

$store = new PreviewSessionStore($tmpDir);

// 1) Create
$session = $store->create('auth.login', 'http://127.0.0.1:8000');
assert($session->view === 'auth.login');
assert(strlen($session->id) >= 1);

// 2) Read
$loaded = $store->get($session->id);
assert($loaded !== null && $loaded->id === $session->id);

// 3) File is readable and JSON valid
$file = $tmpDir . DIRECTORY_SEPARATOR . 'preview_sessions.json';
assert(file_exists($file));
$json = json_decode(file_get_contents($file), true);
assert(is_array($json) && isset($json[$session->id]));

// 4) getOrFail throws
try {
    $store->getOrFail('nonexistent-id');
    throw new Exception('Expected exception not thrown');
} catch (PreviewSessionNotFoundException $e) {
    // expected
}

// 5) Delete
$ok = $store->delete($session->id);
assert($ok === true);
assert($store->get($session->id) === null);

// cleanup
@unlink($file);
@rmdir($tmpDir);

echo "All basic assertions passed.\n";
