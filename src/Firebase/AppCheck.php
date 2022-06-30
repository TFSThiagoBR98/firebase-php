<?php

declare(strict_types=1);

namespace Kreait\Firebase;

use GuzzleHttp\ClientInterface;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

/**
 * @internal
 */
final class AppCheck implements Contract\AppCheck
{
    private ClientInterface $apiClient;
    private IdTokenVerifier $verifier;

    const APP_CHECK_ISSUER = 'https://firebaseappcheck.googleapis.com/';
    const JWKS_URL = 'https://firebaseappcheck.googleapis.com/v1/jwks';

    private function __construct(ClientInterface $apiClient, IdTokenVerifier $verifier)
    {
        $this->verifier = $verifier;
        $this->apiClient = $apiClient;
    }

    /**
     * @param \Stringable|string $appCheckToken
     *
     * @throws InvalidArgumentException
     */
    public function verifyToken($appCheckToken): bool
    {
        try {
            $token = $this->verifier->verifyIdToken($appCheckToken);
            // TODO: Check signature and JWT Format
            return true;
        } catch (IdTokenVerificationFailed $e) {
            return false;
        }
    }
}
