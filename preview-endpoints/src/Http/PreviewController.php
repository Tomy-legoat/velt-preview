<?php

namespace PreviewEndpoints\Http;

use PreviewContracts\Contracts\JsonRendererInterface;
use PreviewContracts\Contracts\PageRepositoryInterface;
use PreviewContracts\PreviewPage;
use PreviewSessionStore\PreviewSessionStore;
use PreviewProtocol\Signature\SessionSignature;
use PreviewProtocol\Protocol\ProtocolCapabilities;
use PreviewProtocol\Protocol\ProtocolVersion;

class PreviewController
{
    public function __construct(
        private PreviewSessionStore $sessionStore,
        private PageRepositoryInterface $pageRepository,
        private JsonRendererInterface $renderer,
        private ?SessionSignature $sessionSignature = null
    ) {
    }

    public function session(string $id, ?array $auth = null): Response
    {
        $authenticationError = $this->authenticate($id, $auth);
        if ($authenticationError !== null) {
            return $authenticationError;
        }

        $session = $this->sessionStore->get($id);
        if ($session === null) {
            return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
        }

        if ($session->isExpired()) {
            return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
        }

        return Response::json($session->toArray(), 200);
    }

    public function preview(string $id, ?array $auth = null): Response
    {
        $authenticationError = $this->authenticate($id, $auth);
        if ($authenticationError !== null) {
            return $authenticationError;
        }

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

        try {
            $body = $this->renderer->render($page);
        } catch (\Throwable) {
            return Response::json(PreviewErrorResponse::internalError()->toArray(), 500);
        }

        return new Response(200, $body, ['Content-Type' => 'application/json']);
    }

    public function heartbeat(string $id, ?array $auth = null): Response
    {
        $authenticationError = $this->authenticate($id, $auth);
        if ($authenticationError !== null) {
            return $authenticationError;
        }

        $session = $this->sessionStore->recordHeartbeat($id);
        if ($session === null) {
            return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
        }
        if ($session->isExpired()) {
            return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
        }

        return Response::json([
            'sessionId' => $session->id,
            'lastHeartbeat' => $session->lastHeartbeat,
            'expiresAt' => $session->expiresAt,
        ]);
    }

    public function negotiate(string $id, array $client, ?array $auth = null): Response
    {
        $authenticationError = $this->authenticate($id, $auth);
        if ($authenticationError !== null) {
            return $authenticationError;
        }
        $session = $this->sessionStore->get($id);
        if ($session === null) {
            return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
        }
        if ($session->isExpired()) {
            return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
        }
        try {
            if (!is_string($client['protocolVersion'] ?? null) || !is_array($client['capabilities'] ?? null)) {
                throw new \InvalidArgumentException('Missing protocol negotiation fields');
            }
            $clientVersion = ProtocolVersion::fromString($client['protocolVersion']);
            $serverVersion = ProtocolVersion::fromString(ProtocolVersion::toString());
            if (!$serverVersion->isCompatibleWith($clientVersion)) {
                return Response::json(PreviewErrorResponse::protocolMismatch()->toArray(), 422);
            }
            $negotiated = ProtocolCapabilities::full()->negotiate(new ProtocolCapabilities($client['capabilities']));
        } catch (\InvalidArgumentException) {
            return Response::json(PreviewErrorResponse::capabilityNegotiationFailed()->toArray(), 422);
        }
        $session->capabilities = $negotiated->toArray();
        $session->sequence ??= 0;
        $session->lastHeartbeat = time();
        $this->sessionStore->saveSession($session);
        return Response::json(['protocolVersion' => ProtocolVersion::toString(), 'capabilities' => $negotiated->toArray(), 'sequence' => $session->sequence]);
    }

    public function resume(string $id, int $sequence, ?array $auth = null): Response
    {
        $authenticationError = $this->authenticate($id, $auth);
        if ($authenticationError !== null) {
            return $authenticationError;
        }

        $session = $this->sessionStore->resume($id, $sequence);
        if ($session === null) {
            $existing = $this->sessionStore->get($id);
            if ($existing === null) {
                return Response::json(PreviewErrorResponse::sessionNotFound()->toArray(), 404);
            }
            if ($existing->isExpired()) {
                return Response::json(PreviewErrorResponse::sessionExpired()->toArray(), 410);
            }
            return Response::json(PreviewErrorResponse::invalidPayload()->toArray(), 422);
        }

        return Response::json([
            'sessionId' => $session->id,
            'sequence' => $session->sequence,
            'lastHeartbeat' => $session->lastHeartbeat,
        ]);
    }

    private function authenticate(string $id, ?array $auth): ?Response
    {
        if ($this->sessionSignature === null) {
            return null;
        }

        if ($auth === null || ($auth['session_id'] ?? null) !== $id) {
            return Response::json(PreviewErrorResponse::invalidSignature()->toArray(), 401);
        }

        $normalizedAuth = $auth;
        foreach (['timestamp', 'expires_at'] as $key) {
            if (isset($normalizedAuth[$key]) && is_string($normalizedAuth[$key]) && preg_match('/^-?\d+$/', $normalizedAuth[$key]) === 1) {
                $normalizedAuth[$key] = (int) $normalizedAuth[$key];
            }
        }

        if (!$this->sessionSignature->validateToken($normalizedAuth)) {
            return Response::json(PreviewErrorResponse::invalidSignature()->toArray(), 401);
        }

        return null;
    }
}
