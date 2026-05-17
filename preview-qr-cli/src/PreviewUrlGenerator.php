<?php

namespace PreviewQrCli;

use PreviewQrCli\Contracts\ViewRegistryInterface;
use PreviewQrCli\Exception\UnknownViewException;
use PreviewSessionStore\PreviewSessionStore;

class PreviewUrlGenerator
{
    public function __construct(
        private PreviewSessionStore $sessionStore,
        private ViewRegistryInterface $viewRegistry,
        private string $baseUrl = 'http://127.0.0.1:8000'
    ) {
    }

    /**
     * @return array{id:string,url:string,qrPayload:string,view:string,createdAt:string}
     */
    public function createForView(string $view): array
    {
        if (!$this->viewRegistry->exists($view)) {
            throw new UnknownViewException('Unknown view: ' . $view);
        }

        $session = $this->sessionStore->create($view, $this->baseUrl);

        return [
            'id' => $session->id,
            'url' => $session->url,
            'qrPayload' => $session->url,
            'view' => $session->view,
            'createdAt' => $session->createdAt,
        ];
    }
}
