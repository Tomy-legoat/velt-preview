<?php
namespace PreviewSessionStore;

use PreviewSessionStore\Exceptions\PreviewSessionNotFoundException;

class PreviewSessionStore
{
    private string $filePath;

    public function __construct(string $directory, string $filename = 'preview_sessions.json')
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException("Unable to create directory: $directory");
            }
        }

        $this->filePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode(new \stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * Read raw data from file as associative array keyed by id
     * @return array<string,array>
     */
    private function readData(): array
    {
        $contents = @file_get_contents($this->filePath);
        if ($contents === false || trim($contents) === '') {
            return [];
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Write associative array to file with lock
     * @param array<string,mixed> $data
     */
    private function writeData(array $data): void
    {
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new \RuntimeException('Unable to JSON encode preview sessions');
        }

        $tmp = $this->filePath . '.tmp';
        $bytes = file_put_contents($tmp, $encoded, LOCK_EX);
        if ($bytes === false) {
            throw new \RuntimeException('Unable to write preview sessions file');
        }
        rename($tmp, $this->filePath);
    }

    public function create(string $view, string $baseUrl = ''): PreviewSession
    {
        $id = bin2hex(random_bytes(6));
        $createdAt = (new \DateTimeImmutable())->format(DATE_ATOM);
        $url = $baseUrl === '' ? '/api/preview/' . $id : rtrim($baseUrl, '/') . '/api/preview/' . $id;

        $session = new PreviewSession($id, $view, $url, $createdAt);

        $data = $this->readData();
        $data[$id] = $session->toArray();
        $this->writeData($data);

        return $session;
    }

    public function get(string $id): ?PreviewSession
    {
        $data = $this->readData();
        if (!isset($data[$id])) {
            return null;
        }

        return PreviewSession::fromArray($data[$id]);
    }

    public function getOrFail(string $id): PreviewSession
    {
        $s = $this->get($id);
        if ($s === null) {
            throw new PreviewSessionNotFoundException("Preview session not found: $id");
        }
        return $s;
    }

    public function delete(string $id): bool
    {
        $data = $this->readData();
        if (!isset($data[$id])) {
            return false;
        }

        unset($data[$id]);
        $this->writeData($data);
        return true;
    }

    /** @return PreviewSession[] */
    public function all(): array
    {
        $data = $this->readData();
        $out = [];
        foreach ($data as $item) {
            $out[] = PreviewSession::fromArray($item);
        }
        return $out;
    }
}
