<?php

namespace PreviewEndpoints\Http;

use PreviewContracts\Contracts\JsonRendererInterface;
use PreviewContracts\Contracts\PageRepositoryInterface;
use PreviewContracts\PreviewPage;
use PreviewSessionStore\PreviewSessionStore;

class PreviewController
{
    public function __construct(
        private PreviewSessionStore $sessionStore,
        private PageRepositoryInterface $pageRepository,
        private JsonRendererInterface $renderer
    ) {
    }

    public function session(string $id): Response
    {
        $session = $this->sessionStore->get($id);
        if ($session === null) {
            return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
        }

        if ($session->isExpired()) {
            return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
        }

        return Response::json($session->toArray(), 200);
    }

    public function preview(string $id): Response
    {
        $session = $this->sessionStore->get($id);
        if ($session === null) {
            return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
        }

        if ($session->isExpired()) {
            return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
        }

        $page = $this->pageRepository->findByView($session->view);
        if ($page === null) {
            return Response::json(PreviewErrorResponse::pageNotFound()->toArray(), 404);
        }

        return new Response(200, $this->renderer->render($page), ['Content-Type' => 'application/json']);
    }
}
