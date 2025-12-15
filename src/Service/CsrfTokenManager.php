<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Custom CSRF Token Manager optimized for ngrok deployment
 * Handles token regeneration and session compatibility
 */
class CsrfTokenManager
{
    private const TOKEN_PREFIX = 'csrf_';
    private const TOKEN_LIFETIME = 3600; // 1 hour

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private TokenGeneratorInterface $tokenGenerator,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Generate a new CSRF token with ngrok-specific handling
     */
    public function generateToken(string $tokenId): string
    {
        $token = $this->tokenGenerator->generateToken();
        
        try {
            $this->tokenStorage->setToken($this->getTokenKey($tokenId), $token);
            
            // Store token metadata for ngrok compatibility (only if session is available)
            $session = $this->getSession();
            if ($session) {
                $this->storeTokenMetadata($tokenId, $token);
            }
        } catch (\Exception $e) {
            // In console commands or other contexts without session, just store the token
            // The metadata storage is optional for basic functionality
        }
        
        return $token;
    }

    /**
     * Refresh CSRF token if it's expired or invalid
     */
    public function refreshTokenIfNeeded(string $tokenId): string
    {
        try {
            $tokenKey = $this->getTokenKey($tokenId);
            $token = $this->tokenStorage->getToken($tokenKey);
            
            if (!$this->checkTokenValidity($tokenId, $token)) {
                return $this->generateToken($tokenId);
            }
            
            return $token;
        } catch (\Exception $e) {
            // If there's any issue with the existing token, generate a new one
            return $this->generateToken($tokenId);
        }
    }

    /**
     * Validate CSRF token with ngrok-specific checks
     */
    public function checkTokenValidity(string $tokenId, ?string $token): bool
    {
        if (!$token) {
            return false;
        }

        try {
            $storedToken = $this->tokenStorage->getToken($this->getTokenKey($tokenId));
            
            if (!$storedToken) {
                return false;
            }

            // Check if tokens match
            if (!hash_equals($storedToken, $token)) {
                return false;
            }

            // Check token age for ngrok compatibility (only if session is available)
            $session = $this->getSession();
            if ($session && !$this->isTokenNotExpired($tokenId)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            // If we can't validate properly, assume invalid for security
            return false;
        }
    }

    /**
     * Remove token from storage (useful for form resubmission prevention)
     */
    public function removeToken(string $tokenId): void
    {
        try {
            $this->tokenStorage->removeToken($this->getTokenKey($tokenId));
            
            $session = $this->getSession();
            if ($session) {
                $this->removeTokenMetadata($tokenId);
            }
        } catch (\Exception $e) {
            // Ignore errors during removal
        }
    }

    /**
     * Get all active tokens for debugging
     */
    public function getActiveTokens(): array
    {
        $tokens = [];
        
        try {
            $session = $this->getSession();
            if ($session) {
                $sessionKeys = $session->all();
                foreach ($sessionKeys as $key => $value) {
                    if (str_starts_with($key, self::TOKEN_PREFIX)) {
                        $tokenId = substr($key, strlen(self::TOKEN_PREFIX));
                        $tokens[$tokenId] = [
                            'token' => $value,
                            'metadata' => $this->getTokenMetadata($tokenId)
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Return empty array if we can't access session
        }
        
        return $tokens;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $cleanedCount = 0;
        
        try {
            $session = $this->getSession();
            if ($session) {
                $sessionKeys = $session->all();
                foreach ($sessionKeys as $key => $value) {
                    if (str_starts_with($key, self::TOKEN_PREFIX)) {
                        $tokenId = substr($key, strlen(self::TOKEN_PREFIX));
                        if ($this->isTokenExpired($tokenId)) {
                            $this->removeToken($tokenId);
                            $cleanedCount++;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Return 0 if cleanup fails
        }
        
        return $cleanedCount;
    }

    private function getTokenKey(string $tokenId): string
    {
        return self::TOKEN_PREFIX . $tokenId;
    }

    private function getSession(): ?SessionInterface
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            return $request?->getSession();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function storeTokenMetadata(string $tokenId, string $token): void
    {
        try {
            $session = $this->getSession();
            if ($session) {
                $metadataKey = self::TOKEN_PREFIX . 'metadata_' . $tokenId;
                $session->set($metadataKey, [
                    'created_at' => time(),
                    'last_accessed' => time(),
                    'ip_address' => $this->getClientIp(),
                    'user_agent' => $this->getUserAgent()
                ]);
            }
        } catch (\Exception $e) {
            // Ignore metadata storage errors
        }
    }

    private function getTokenMetadata(string $tokenId): ?array
    {
        try {
            $session = $this->getSession();
            if ($session) {
                $metadataKey = self::TOKEN_PREFIX . 'metadata_' . $tokenId;
                return $session->get($metadataKey);
            }
        } catch (\Exception $e) {
            // Return null if metadata can't be retrieved
        }
        return null;
    }

    private function removeTokenMetadata(string $tokenId): void
    {
        try {
            $session = $this->getSession();
            if ($session) {
                $metadataKey = self::TOKEN_PREFIX . 'metadata_' . $tokenId;
                $session->remove($metadataKey);
            }
        } catch (\Exception $e) {
            // Ignore metadata removal errors
        }
    }

    private function isTokenNotExpired(string $tokenId): bool
    {
        try {
            $metadata = $this->getTokenMetadata($tokenId);
            if (!$metadata) {
                return false;
            }

            $age = time() - $metadata['created_at'];
            return $age < self::TOKEN_LIFETIME;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isTokenExpired(string $tokenId): bool
    {
        return !$this->isTokenNotExpired($tokenId);
    }

    private function getClientIp(): ?string
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            return $request?->getClientIp();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getUserAgent(): ?string
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            return $request?->headers->get('User-Agent');
        } catch (\Exception $e) {
            return null;
        }
    }
}