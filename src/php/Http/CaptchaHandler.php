<?php

namespace weanteomnio\AOCaptcha\Http;

use weanteomnio\AOCaptcha\BehaviorAnalyzer;
use weanteomnio\AOCaptcha\Challenge;
use weanteomnio\AOCaptcha\Storage\StorageInterface;

class CaptchaHandler
{
    private const CHALLENGE_TTL = 300;
    private const PASS_TTL = 600;
    private const POSITION_TOLERANCE = 15.0;

    public function __construct(
        private StorageInterface $storage,
        private Challenge $challenge = new Challenge(),
        private BehaviorAnalyzer $analyzer = new BehaviorAnalyzer(),
        private string $sessionKeyPrefix = 'aocaptcha',
    ) {
    }

    /**
     * @param array<mixed>|null $body
     * @return array<mixed>
     */
    public function handle(string $action, ?array $body = null, ?string $browserId = null): array
    {
        $id = $this->resolveBrowserId($browserId);

        return match ($action) {
            'new' => $this->handleNew($id),
            'verify' => $this->handleVerify($id, $body ?? []),
            default => ['valid' => false, 'message' => 'Unknown action.'],
        };
    }

    public function verifyPass(string $token, ?string $browserId = null): bool
    {
        $id = $this->resolveBrowserId($browserId);
        $key = $this->key('pass', $id);

        $stored = $this->storage->get($key);
        $this->storage->delete($key);

        if ($stored === null || $token === '') {
            return false;
        }

        return hash_equals((string) ($stored['token'] ?? ''), $token);
    }

    /**
     * Convenience entry point for a drop-in endpoint file: reads
     * $_GET['action'] and php://input, emits no-cache JSON headers, echoes
     * the JSON response, and exits.
     */
    public function handleRequest(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('CDN-Cache-Control: no-store');
        header('Surrogate-Control: no-store');
        header('X-Accel-Expires: 0');
        header('Vary: Cookie');

        $action = (string) ($_GET['action'] ?? '');

        $body = null;
        if ($action === 'verify') {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $body = is_array($decoded) ? $decoded : [];
        }

        echo json_encode($this->handle($action, $body));
        exit;
    }

    /**
     * @return array<mixed>
     */
    private function handleNew(string $browserId): array
    {
        $challenge = $this->challenge->generate();
        $this->storage->set($this->key('challenge', $browserId), $challenge, self::CHALLENGE_TTL);
        return $challenge;
    }

    /**
     * @param array<mixed> $body
     * @return array<mixed>
     */
    private function handleVerify(string $browserId, array $body): array
    {
        if (empty($body['movements'])) {
            return ['valid' => false, 'message' => 'Invalid request.'];
        }

        $challengeKey = $this->key('challenge', $browserId);
        $stored = $this->storage->get($challengeKey);
        $this->storage->delete($challengeKey);

        if ($stored === null) {
            return ['valid' => false, 'message' => 'Verification failed. Please try again.'];
        }

        if (empty($body['snapped'])) {
            return ['valid' => false, 'message' => 'Verification failed. Please try again.'];
        }

        $snapX = (float) ($body['snapPosition']['x'] ?? -1);
        $snapY = (float) ($body['snapPosition']['y'] ?? -1);
        $posDist = sqrt(
            ($snapX - $stored['target']['x']) ** 2 +
            ($snapY - $stored['target']['y']) ** 2
        );
        if ($posDist > self::POSITION_TOLERANCE) {
            return ['valid' => false, 'message' => 'Verification failed. Please try again.'];
        }

        $looksHuman = $this->analyzer->looksHuman(
            is_array($body['movements']) ? $body['movements'] : [],
            (float) ($body['duration'] ?? 0)
        );

        if (!$looksHuman) {
            return ['valid' => false, 'message' => 'Verification failed. Please try again.'];
        }

        $token = bin2hex(random_bytes(16));
        $this->storage->set($this->key('pass', $browserId), ['token' => $token], self::PASS_TTL);

        return ['valid' => true, 'token' => $token];
    }

    private function resolveBrowserId(?string $browserId): string
    {
        if ($browserId !== null) {
            return $browserId;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return session_id() ?: 'anonymous';
    }

    private function key(string $suffix, string $browserId): string
    {
        return $this->sessionKeyPrefix . ':' . $suffix . ':' . $browserId;
    }
}
