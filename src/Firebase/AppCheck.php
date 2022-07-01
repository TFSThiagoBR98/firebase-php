<?php

declare(strict_types=1);

namespace Kreait\Firebase;

use GuzzleHttp\ClientInterface;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\AppCheckTokenVerifier;

/**
 * @internal
 */
final class AppCheck implements Contract\AppCheck
{
    private ClientInterface $apiClient;
    private AppCheckTokenVerifier $verifier;

    function __construct(ClientInterface $apiClient, AppCheckTokenVerifier $verifier)
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
