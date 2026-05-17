<?php

namespace PreviewEndpoints\Http;

use PreviewEndpoints\Contracts\JsonRendererInterface;
use PreviewEndpoints\Contracts\PageRepositoryInterface;
use PreviewEndpoints\PreviewPage;
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
            return Response::json([
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Preview session not found',
                ],
            ], 404);
        }

        return Response::json($session->toArray(), 200);
    }

    public function preview(string $id): Response
    {
        $session = $this->sessionStore->get($id);
        if ($session === null) {
            return Response::json([
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => 'Preview session not found',
                ],
            ], 404);
        }

        $page = $this->pageRepository->findByView($session->view);
        if ($page === null) {
            return Response::json([
                'error' => [
                    'code' => 'PAGE_NOT_FOUND',
                    'message' => 'Preview page not found',
                ],
            ], 404);
        }

        return new Response(200, $this->renderer->render($page), ['Content-Type' => 'application/json']);
    }
}
