<?php

namespace App\Command;

use App\Service\CsrfTokenManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-csrf-ngrok',
    description: 'Test CSRF token functionality for ngrok deployment',
)]
class TestCsrfNgrokCommand extends Command
{
    public function __construct(
        private CsrfTokenManager $csrfTokenManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('token-id', 't', InputOption::VALUE_OPTIONAL, 'Token ID to test', 'submit')
            ->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Number of test iterations', '10')
            ->addOption('cleanup', 'c', InputOption::VALUE_NONE, 'Clean up test tokens after testing')
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Detailed output with more information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tokenId = $input->getOption('token-id');
        $iterations = (int) $input->getOption('iterations');
        $cleanup = $input->getOption('cleanup');
        $detailed = $input->getOption('detailed');

        $io->title('CSRF Token Test for Ngrok Deployment');
        $io->text([
            "Token ID: {$tokenId}",
            "Iterations: {$iterations}",
            "Cleanup: " . ($cleanup ? 'Yes' : 'No'),
            "Detailed: " . ($detailed ? 'Yes' : 'No'),
            'Timestamp: ' . date('c')
        ]);

        try {
            $testResults = [];
            $startTime = microtime(true);

            // Test 1: Generate tokens
            $io->section('Test 1: Token Generation');
            $generatedTokens = [];
            for ($i = 0; $i < min($iterations, 5); $i++) {
                $testTokenId = "test_{$tokenId}_{$i}";
                $token = $this->csrfTokenManager->generateToken($testTokenId);
                $generatedTokens[] = $testTokenId;
                
                if ($detailed) {
                    $io->text("Generated token {$i}: " . substr($token, 0, 20) . '...');
                }
                
                $testResults["generation_{$i}"] = [
                    'success' => !empty($token),
                    'token_length' => strlen($token),
                    'token_preview' => substr($token, 0, 20) . '...'
                ];
            }
            $io->success('Token generation completed');

            // Test 2: Validate tokens
            $io->section('Test 2: Token Validation');
            $validationResults = [];
            foreach ($generatedTokens as $testTokenId) {
                $token = $this->csrfTokenManager->refreshTokenIfNeeded($testTokenId);
                $isValid = $this->csrfTokenManager->checkTokenValidity($testTokenId, $token);
                
                $validationResults[] = [
                    'token_id' => $testTokenId,
                    'valid' => $isValid
                ];
                
                if ($detailed) {
                    $status = $isValid ? '✓' : '✗';
                    $io->text("{$status} Token validation for {$testTokenId}");
                }
                
                $testResults["validation_{$testTokenId}"] = [
                    'success' => $isValid,
                    'expected' => true,
                    'actual' => $isValid
                ];
            }
            $io->success('Token validation completed');

            // Test 3: Token refresh
            $io->section('Test 3: Token Refresh');
            $refreshResults = [];
            foreach ($generatedTokens as $testTokenId) {
                $oldToken = $this->csrfTokenManager->refreshTokenIfNeeded($testTokenId);
                $newToken = $this->csrfTokenManager->refreshTokenIfNeeded($testTokenId);
                
                $refreshResults[] = [
                    'token_id' => $testTokenId,
                    'tokens_different' => $oldToken !== $newToken,
                    'new_token_length' => strlen($newToken)
                ];
                
                if ($detailed) {
                    $status = $oldToken !== $newToken ? '✓' : '○';
                    $io->text("{$status} Token refresh for {$testTokenId}");
                }
                
                $testResults["refresh_{$testTokenId}"] = [
                    'success' => !empty($newToken),
                    'token_changed' => $oldToken !== $newToken,
                    'new_token_length' => strlen($newToken)
                ];
            }
            $io->success('Token refresh completed');

            // Test 4: Active tokens management
            $io->section('Test 4: Active Tokens Management');
            $activeTokens = $this->csrfTokenManager->getActiveTokens();
            $tokenCount = count($activeTokens);
            
            $io->text("Active tokens found: {$tokenCount}");
            if ($detailed) {
                foreach ($activeTokens as $id => $tokenData) {
                    $io->text("- {$id}: " . (isset($tokenData['metadata']) ? 'with metadata' : 'basic'));
                }
            }
            
            $testResults['active_tokens'] = [
                'success' => is_array($activeTokens),
                'count' => $tokenCount,
                'has_test_tokens' => count(array_filter($generatedTokens, fn($id) => isset($activeTokens[$id]))) > 0
            ];
            $io->success('Active tokens management completed');

            // Test 5: Cleanup
            $io->section('Test 5: Cleanup Operations');
            $cleanedCount = $this->csrfTokenManager->cleanupExpiredTokens();
            $io->text("Cleaned up {$cleanedCount} expired tokens");
            
            $testResults['cleanup'] = [
                'success' => is_numeric($cleanedCount),
                'cleaned_count' => $cleanedCount
            ];
            
            // Clean up test tokens if requested
            if ($cleanup) {
                foreach ($generatedTokens as $testTokenId) {
                    $this->csrfTokenManager->removeToken($testTokenId);
                }
                $io->text('Test tokens cleaned up');
            }
            $io->success('Cleanup completed');

            // Summary
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 3);
            
            $io->section('Test Summary');
            $totalTests = count($testResults);
            $passedTests = count(array_filter($testResults, fn($result) => $result['success'] ?? false));
            $failedTests = $totalTests - $passedTests;
            
            $io->text([
                "Total Tests: {$totalTests}",
                "Passed: {$passedTests}",
                "Failed: {$failedTests}",
                "Duration: {$duration} seconds",
                "Timestamp: " . date('c')
            ]);

            if ($failedTests > 0) {
                $io->error("Some tests failed! Check the detailed results above.");
                $this->displayFailedTests($io, $testResults);
                return Command::FAILURE;
            } else {
                $io->success('All tests passed! CSRF functionality is working correctly for ngrok deployment.');
                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $io->error('Test execution failed: ' . $e->getMessage());
            if ($detailed) {
                $io->text([
                    'Exception Class: ' . get_class($e),
                    'File: ' . $e->getFile(),
                    'Line: ' . $e->getLine(),
                    'Stack Trace:',
                    $e->getTraceAsString()
                ]);
            }
            return Command::FAILURE;
        }
    }

    private function displayFailedTests(SymfonyStyle $io, array $testResults): void
    {
        $failedTests = array_filter($testResults, fn($result) => !($result['success'] ?? true));
        
        if (empty($failedTests)) {
            return;
        }
        
        $io->section('Failed Tests Details');
        foreach ($failedTests as $testName => $result) {
            $io->text([
                "<error>❌ {$testName}</error>",
                "  Success: " . ($result['success'] ? 'true' : 'false')
            ]);
            
            foreach ($result as $key => $value) {
                if ($key !== 'success') {
                    $io->text("  {$key}: " . (is_array($value) ? json_encode($value) : $value));
                }
            }
            $io->text('');
        }
    }
}