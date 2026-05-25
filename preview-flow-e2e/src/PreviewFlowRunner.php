<?php

namespace PreviewFlowE2E;

use PreviewEndpoints\Http\PreviewController;
use PreviewContracts\PreviewPage;
use PreviewEndpoints\Renderer\ArrayJsonRenderer;
use PreviewEndpoints\Repository\ArrayPageRepository;
use PreviewSessionStore\PreviewSessionStore;

class PreviewFlowRunner
{
    private PreviewController $controller;
    private PreviewSessionStore $store;

    public function __construct(string $storageDir)
    {
        $this->store = new PreviewSessionStore($storageDir);
        $repo = new ArrayPageRepository([
            'auth.login' => new PreviewPage('auth.login', [
                ['type' => 'text', 'name' => 'title', 'value' => 'Login'],
                ['type' => 'input', 'name' => 'email', 'label' => 'Email'],
            ], ['source' => 'auth.login']),
        ]);
        $this->controller = new PreviewController($this->store, $repo, new ArrayJsonRenderer());
    }

    /**
     * @return array<string,mixed>
     */
    public function run(): array
    {
        $session = $this->store->create('auth.login', 'http://127.0.0.1:8000');

        $okResponse = $this->controller->preview($session->id);
        $okJson = json_decode($okResponse->body, true);

        $missingSessionResponse = $this->controller->preview('missing-id');
        $missingSessionJson = json_decode($missingSessionResponse->body, true);

        $missingPageSession = $this->store->create('missing.view', 'http://127.0.0.1:8000');
        $missingPageResponse = $this->controller->preview($missingPageSession->id);
        $missingPageJson = json_decode($missingPageResponse->body, true);

        return [
            'happyPath' => [
                'status' => $okResponse->statusCode,
                'hasScreen' => is_array($okJson) && isset($okJson['screen']),
                'hasSchemaVersion' => is_array($okJson) && isset($okJson['schemaVersion']),
                'hasComponents' => is_array($okJson) && isset($okJson['components']),
                'payload' => $okJson,
            ],
            'missingSession' => [
                'status' => $missingSessionResponse->statusCode,
                'payload' => $missingSessionJson,
            ],
            'missingPage' => [
                'status' => $missingPageResponse->statusCode,
                'payload' => $missingPageJson,
            ],
        ];
    }
}
