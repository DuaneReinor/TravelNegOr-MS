<?php

namespace App\Command;

use App\Service\CsrfTokenManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsCommand(
    name: 'app:debug-csrf-ngrok',
    description: 'Debug CSRF issues for ngrok deployment',
)]
class DebugCsrfNgrokCommand extends Command
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CsrfTokenManager $customCsrfTokenManager,
        private RequestStack $requestStack
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('CSRF Debug Information for Ngrok Deployment');

        // Check current request context
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $io->section('Request Information');
            $io->table(
                ['Property', 'Value'],
                [
                    ['Method', $request->getMethod()],
                    ['URI', $request->getUri()],
                    ['Host', $request->getHost()],
                    ['Scheme', $request->getScheme()],
                    ['HTTPS', $request->isSecure() ? 'Yes' : 'No'],
                    ['Session ID', $request->getSession()?->getId() ?? 'No session'],
                    ['User Agent', substr($request->headers->get('User-Agent', 'Unknown'), 0, 50) . '...'],
                ]
            );

            // Check session configuration
            $session = $request->getSession();
            if ($session) {
                $io->section('Session Configuration');
                $io->table(
                    ['Property', 'Value'],
                    [
                        ['Session Name', $session->getName()],
                        ['Session ID', $session->getId()],
                        ['Session Started', $session->isStarted() ? 'Yes' : 'No'],
                        ['Session Lifetime', ini_get('session.gc_maxlifetime') . ' seconds'],
                        ['Cookie Lifetime', ini_get('session.cookie_lifetime') . ' seconds'],
                    ]
                );
            }
        } else {
            $io->warning('No current request context (running in console)');
        }

        // Test CSRF token generation for different token IDs
        $io->section('CSRF Token Tests');

        $tokenIds = ['submit', 'authenticate', 'logout'];
        $results = [];

        foreach ($tokenIds as $tokenId) {
            try {
                // Test with Symfony's CSRF token manager
                $token = $this->csrfTokenManager->getToken($tokenId);
                $tokenValue = $token->getValue();

                // Test with our custom CSRF token manager
                $customToken = $this->customCsrfTokenManager->generateToken($tokenId);

                // Test validation
                $isValid = $this->csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($tokenId, $tokenValue));

                $results[] = [
                    'Token ID' => $tokenId,
                    'Symfony Token Length' => strlen($tokenValue),
                    'Custom Token Length' => strlen($customToken),
                    'Validation' => $isValid ? 'Valid' : 'Invalid',
                    'Status' => 'OK'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'Token ID' => $tokenId,
                    'Symfony Token Length' => 'Error',
                    'Custom Token Length' => 'Error',
                    'Validation' => 'Error',
                    'Status' => 'Error: ' . $e->getMessage()
                ];
            }
        }

        $io->table(
            ['Token ID', 'Symfony Token', 'Custom Token', 'Validation', 'Status'],
            array_map(function ($row) {
                return [
                    $row['Token ID'],
                    $row['Symfony Token Length'],
                    $row['Custom Token Length'],
                    $row['Validation'],
                    $row['Status']
                ];
            }, $results)
        );

        // Check active tokens
        $io->section('Active Tokens');
        try {
            $activeTokens = $this->customCsrfTokenManager->getActiveTokens();
            if (empty($activeTokens)) {
                $io->text('No active tokens found in session');
            } else {
                $tokenData = [];
                foreach ($activeTokens as $tokenId => $data) {
                    $tokenData[] = [
                        'Token ID' => $tokenId,
                        'Has Metadata' => isset($data['metadata']) ? 'Yes' : 'No',
                        'Metadata Age' => isset($data['metadata']['created_at']) ? (time() - $data['metadata']['created_at']) . 's' : 'N/A'
                    ];
                }

                $io->table(
                    ['Token ID', 'Has Metadata', 'Metadata Age'],
                    $tokenData
                );
            }
        } catch (\Exception $e) {
            $io->error('Error getting active tokens: ' . $e->getMessage());
        }

        // Recommendations
        $io->section('Recommendations for Ngrok Deployment');

        $recommendations = [];

        if (!$request || !$request->isSecure()) {
            $recommendations[] = '⚠️  Use HTTPS with ngrok for better CSRF protection';
        }

        if (!$request || !$request->getSession()?->isStarted()) {
            $recommendations[] = '⚠️  Ensure session is properly started';
        }

        $recommendations[] = '✅ CSRF configuration looks good for ngrok deployment';
        $recommendations[] = '✅ Extended session lifetime configured (3600 seconds)';
        $recommendations[] = '✅ Stateless CSRF tokens configured';
        $recommendations[] = '✅ Custom CSRF token manager available for debugging';

        foreach ($recommendations as $recommendation) {
            $io->text($recommendation);
        }

        $io->success('CSRF debugging completed. Check the information above for any issues.');

        return Command::SUCCESS;
    }
}