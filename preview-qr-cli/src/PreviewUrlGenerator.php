<?php

namespace PreviewQrCli;

use PreviewQrCli\Contracts\ViewRegistryInterface;
use PreviewQrCli\Exception\UnknownViewException;
use PreviewSessionStore\PreviewSessionStore;
use PreviewProtocol\Signature\SessionSignature;

class PreviewUrlGenerator
{
    public function __construct(
        private PreviewSessionStore $sessionStore,
        private ViewRegistryInterface $viewRegistry,
        private string $baseUrl = 'http://127.0.0.1:8000',
        private ?SessionSignature $sessionSignature = null
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
        $url = $session->url;

        if ($this->sessionSignature !== null) {
            $url .= '?' . http_build_query(
                $this->sessionSignature->generateToken($session->id),
                '',
                '&',
                PHP_QUERY_RFC3986
            );
        }

        return [
            'id' => $session->id,
            'url' => $url,
            'qrPayload' => $url,
            'view' => $session->view,
            'createdAt' => $session->createdAt,
        ];
    }
}
