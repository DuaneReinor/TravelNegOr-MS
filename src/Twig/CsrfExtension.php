<?php

namespace App\Twig;

use App\Service\CsrfTokenManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for CSRF token management with ngrok compatibility
 */
class CsrfExtension extends AbstractExtension
{
    public function __construct(
        private CsrfTokenManager $csrfTokenManager
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csrf_token_ngrok', [$this, 'getNgrokCsrfToken'], ['is_safe' => ['html']]),
            new TwigFunction('csrf_token_refresh', [$this, 'refreshCsrfToken'], ['is_safe' => ['html']]),
            new TwigFunction('csrf_debug_info', [$this, 'getCsrfDebugInfo'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Generate CSRF token optimized for ngrok
     */
    public function getNgrokCsrfToken(string $tokenId = 'submit'): string
    {
        $token = $this->csrfTokenManager->generateToken($tokenId);
        
        // Return as hidden input field for easy inclusion in forms
        return sprintf(
            '<input type="hidden" name="_csrf_token_%s" value="%s" data-csrf-id="%s" data-generated="%s">',
            htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8'),
            date('c')
        );
    }

    /**
     * Refresh CSRF token and return new token
     */
    public function refreshCsrfToken(string $tokenId = 'submit'): string
    {
        $token = $this->csrfTokenManager->refreshTokenIfNeeded($tokenId);
        
        return sprintf(
            '<input type="hidden" name="_csrf_token_%s" value="%s" data-csrf-id="%s" data-refreshed="%s">',
            htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8'),
            date('c')
        );
    }

    /**
     * Get CSRF debug information for troubleshooting
     */
    public function getCsrfDebugInfo(string $tokenId = 'submit'): string
    {
        $tokens = $this->csrfTokenManager->getActiveTokens();
        $tokenKey = $tokenId;
        
        $debugInfo = [
            'current_token_id' => $tokenId,
            'active_tokens' => count($tokens),
            'tokens' => [],
            'session_info' => [
                'session_id' => session_id(),
                'session_name' => session_name(),
            ],
            'timestamp' => date('c'),
        ];
        
        foreach ($tokens as $id => $tokenData) {
            $metadata = $tokenData['metadata'] ?? [];
            $debugInfo['tokens'][$id] = [
                'exists' => true,
                'created_at' => date('c', $metadata['created_at'] ?? time()),
                'age_seconds' => time() - ($metadata['created_at'] ?? time()),
                'ip_address' => $metadata['ip_address'] ?? 'unknown',
                'user_agent' => substr($metadata['user_agent'] ?? 'unknown', 0, 50) . '...',
            ];
        }
        
        if (!isset($tokens[$tokenId])) {
            $debugInfo['tokens'][$tokenId] = ['exists' => false];
        }
        
        return sprintf(
            '<script type="application/json" id="csrf-debug-info">%s</script>',
            htmlspecialchars(json_encode($debugInfo, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8')
        );
    }
}