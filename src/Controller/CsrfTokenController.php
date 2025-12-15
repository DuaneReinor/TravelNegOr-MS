<?php

namespace App\Controller;

use App\Service\CsrfTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CSRF Token management controller for ngrok compatibility
 */
#[Route('/admin/csrf')]
#[IsGranted('ROLE_STAFF')]
class CsrfTokenController extends AbstractController
{
    public function __construct(
        private CsrfTokenManager $csrfTokenManager
    ) {
    }

    /**
     * Refresh CSRF token via AJAX
     */
    #[Route('/refresh/{tokenId}', name: 'csrf_token_refresh', methods: ['POST'])]
    public function refreshToken(string $tokenId = 'submit', Request $request): JsonResponse
    {
        try {
            // Generate new token
            $newToken = $this->csrfTokenManager->generateToken($tokenId);
            
            // Clean up old tokens periodically
            $this->csrfTokenManager->cleanupExpiredTokens();
            
            return new JsonResponse([
                'success' => true,
                'token' => $newToken,
                'token_id' => $tokenId,
                'timestamp' => date('c'),
                'message' => 'CSRF token refreshed successfully'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to refresh CSRF token',
                'message' => $e->getMessage(),
                'timestamp' => date('c')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate CSRF token
     */
    #[Route('/validate/{tokenId}', name: 'csrf_token_validate', methods: ['POST'])]
    public function validateToken(string $tokenId = 'submit', Request $request): JsonResponse
    {
        try {
            $token = $request->request->get('_csrf_token');
            $isValid = $this->csrfTokenManager->checkTokenValidity($tokenId, $token);
            
            return new JsonResponse([
                'success' => true,
                'valid' => $isValid,
                'token_id' => $tokenId,
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'valid' => false,
                'error' => 'Token validation failed',
                'message' => $e->getMessage(),
                'timestamp' => date('c')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get CSRF token debug information
     */
    #[Route('/debug/{tokenId}', name: 'csrf_token_debug', methods: ['GET'])]
    public function debugToken(string $tokenId = 'submit', Request $request): JsonResponse
    {
        try {
            $activeTokens = $this->csrfTokenManager->getActiveTokens();
            $tokenExists = isset($activeTokens[$tokenId]);
            
            $debugInfo = [
                'token_id' => $tokenId,
                'token_exists' => $tokenExists,
                'active_tokens_count' => count($activeTokens),
                'active_tokens' => $activeTokens,
                'session_info' => [
                    'session_id' => $request->getSession()?->getId(),
                    'session_name' => $request->getSession()?->getName(),
                    'is_started' => $request->getSession()?->isStarted(),
                ],
                'request_info' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getUri(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'client_ip' => $request->getClientIp(),
                    'is_secure' => $request->isSecure(),
                ],
                'timestamp' => date('c'),
                'environment' => $this->getParameter('kernel.environment')
            ];
            
            return new JsonResponse([
                'success' => true,
                'debug_info' => $debugInfo
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get debug information',
                'message' => $e->getMessage(),
                'timestamp' => date('c')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Clean up expired CSRF tokens
     */
    #[Route('/cleanup', name: 'csrf_token_cleanup', methods: ['POST'])]
    public function cleanupTokens(Request $request): JsonResponse
    {
        try {
            $cleanedCount = $this->csrfTokenManager->cleanupExpiredTokens();
            
            return new JsonResponse([
                'success' => true,
                'cleaned_count' => $cleanedCount,
                'message' => "Cleaned up {$cleanedCount} expired tokens",
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to cleanup tokens',
                'message' => $e->getMessage(),
                'timestamp' => date('c')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Test CSRF token functionality
     */
    #[Route('/test', name: 'csrf_token_test', methods: ['GET', 'POST'])]
    public function testToken(Request $request): Response
    {
        $testResults = [];
        
        try {
            // Test 1: Generate token
            $testToken = $this->csrfTokenManager->generateToken('test_token');
            $testResults['generate'] = [
                'success' => !empty($testToken),
                'token_length' => strlen($testToken),
                'token_preview' => substr($testToken, 0, 10) . '...'
            ];
            
            // Test 2: Validate token
            $isValid = $this->csrfTokenManager->checkTokenValidity('test_token', $testToken);
            $testResults['validate'] = [
                'success' => $isValid,
                'expected' => true,
                'actual' => $isValid
            ];
            
            // Test 3: Refresh token
            $refreshedToken = $this->csrfTokenManager->refreshTokenIfNeeded('test_token');
            $testResults['refresh'] = [
                'success' => !empty($refreshedToken),
                'token_changed' => $testToken !== $refreshedToken,
                'new_token_length' => strlen($refreshedToken)
            ];
            
            // Test 4: Get active tokens
            $activeTokens = $this->csrfTokenManager->getActiveTokens();
            $testResults['active_tokens'] = [
                'success' => is_array($activeTokens),
                'count' => count($activeTokens),
                'has_test_token' => isset($activeTokens['test_token'])
            ];
            
            // Test 5: Cleanup
            $cleanedCount = $this->csrfTokenManager->cleanupExpiredTokens();
            $testResults['cleanup'] = [
                'success' => is_numeric($cleanedCount),
                'cleaned_count' => $cleanedCount
            ];
            
            // Remove test token
            $this->csrfTokenManager->removeToken('test_token');
            
        } catch (\Exception $e) {
            $testResults['error'] = [
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        $html = $this->render('admin/csrf_test.html.twig', [
            'test_results' => $testResults,
            'timestamp' => date('c')
        ]);
        
        return $html;
    }
}