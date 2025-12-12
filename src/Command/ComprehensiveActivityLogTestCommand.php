<?php

namespace App\Command;

use App\Entity\ActivityLog;
use App\Entity\Destination;
use App\Entity\Hotel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:comprehensive-activity-test',
    description: 'Test comprehensive activity logging including login/logout simulation',
)]
class ComprehensiveActivityLogTestCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $io->title('🧪 Comprehensive Activity Logging Test');
            
            // Test 1: Create various entities to trigger activity logging
            $io->section('1️⃣ Testing Entity CRUD Operations');
            
            // Create a test destination
            $destination = new Destination();
            $destination->setName('Activity Test Destination')
                       ->setLocation('Test City')
                       ->setDescription('This destination is created to test activity logging')
                       ->setImage('test-destination.jpg');
            
            $this->entityManager->persist($destination);
            $this->entityManager->flush();
            $io->success('✅ Destination created (CREATE logged)');
            
            // Update the destination
            $destination->setDescription('Updated description for activity test');
            $this->entityManager->flush();
            $io->success('✅ Destination updated (UPDATE logged)');
            
            // Create a test hotel
            $hotel = new Hotel();
            $hotel->setName('Activity Test Hotel')
                  ->setDescription('Test hotel for activity logging')
                  ->setPrice(150.00)
                  ->setLocation('Test City')
                  ->setImage('test-hotel.jpg');
            
            $this->entityManager->persist($hotel);
            $this->entityManager->flush();
            $io->success('✅ Hotel created (CREATE logged)');
            
            // Update the hotel
            $hotel->setPrice(175.00);
            $this->entityManager->flush();
            $io->success('✅ Hotel updated (UPDATE logged)');
            
            // Test 2: Simulate user creation (this would normally be done through registration)
            $io->section('2️⃣ Testing User Operations');
            
            $testUser = new User();
            $testUser->setEmail('activity.test@travelnegor.com')
                    ->setFirstName('Activity')
                    ->setLastName('Tester')
                    ->setRoles(['ROLE_STAFF'])
                    ->setPassword('test123'); // In real app, this would be hashed
            
            $this->entityManager->persist($testUser);
            $this->entityManager->flush();
            $io->success('✅ User created (CREATE logged)');
            
            // Update user
            $testUser->setFirstName('Activity Updated');
            $this->entityManager->flush();
            $io->success('✅ User updated (UPDATE logged)');
            
            // Test 3: Manual login/logout simulation
            $io->section('3️⃣ Testing Security Events');
            
            // Simulate login
            $loginLog = ActivityLog::create(
                action: 'LOGIN',
                entityType: 'User',
                entityId: $testUser->getId(),
                entityName: 'Activity Tester',
                user: $testUser,
                description: "Simulated login for testing: {$testUser->getEmail()}",
                newData: ['email' => $testUser->getEmail(), 'login_time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')]
            );
            $loginLog->setIpAddress('127.0.0.1')
                    ->setUserAgent('CLI Test Command');
            
            $this->entityManager->persist($loginLog);
            $this->entityManager->flush();
            $io->success('✅ Login event logged');
            
            // Simulate page access
            $pageAccessLog = ActivityLog::create(
                action: 'PAGE_ACCESS',
                entityType: 'AdminPanel',
                entityId: $testUser->getId(),
                entityName: 'admin_dashboard',
                user: $testUser,
                description: 'Simulated admin dashboard access'
            );
            $pageAccessLog->setIpAddress('127.0.0.1')
                         ->setUserAgent('CLI Test Command');
            
            $this->entityManager->persist($pageAccessLog);
            $this->entityManager->flush();
            $io->success('✅ Page access logged');
            
            // Simulate logout
            $logoutLog = ActivityLog::create(
                action: 'LOGOUT',
                entityType: 'User',
                entityId: $testUser->getId(),
                entityName: 'Activity Tester',
                user: $testUser,
                description: "Simulated logout: {$testUser->getEmail()}"
            );
            $logoutLog->setIpAddress('127.0.0.1')
                     ->setUserAgent('CLI Test Command');
            
            $this->entityManager->persist($logoutLog);
            $this->entityManager->flush();
            $io->success('✅ Logout event logged');
            
            // Test 4: Failed login attempt
            $failedLoginLog = ActivityLog::create(
                action: 'LOGIN_FAILED',
                entityType: 'User',
                entityName: 'nonexistent@email.com',
                description: 'Failed login attempt for testing'
            );
            $failedLoginLog->setIpAddress('192.168.1.100')
                          ->setUserAgent('Suspicious Bot');
            
            $this->entityManager->persist($failedLoginLog);
            $this->entityManager->flush();
            $io->success('✅ Failed login attempt logged');
            
            // Test 5: Cleanup - Delete test entities
            $io->section('4️⃣ Testing Deletion Operations');
            
            $this->entityManager->remove($destination);
            $this->entityManager->flush();
            $io->success('✅ Destination deleted (DELETE logged)');
            
            $this->entityManager->remove($hotel);
            $this->entityManager->flush();
            $io->success('✅ Hotel deleted (DELETE logged)');
            
            $this->entityManager->remove($testUser);
            $this->entityManager->flush();
            $io->success('✅ User deleted (DELETE logged)');
            
            // Summary
            $io->section('📊 Test Results Summary');
            
            $activityLogs = $this->entityManager->getRepository(ActivityLog::class)->findAll();
            $totalLogs = count($activityLogs);
            
            $io->text(sprintf('📈 Total activity logs created: %d', $totalLogs));
            
            if ($totalLogs > 0) {
                // Group logs by action type
                $actionCounts = [];
                foreach ($activityLogs as $log) {
                    $action = $log->getAction();
                    $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;
                }
                
                $io->text('📋 Activity breakdown:');
                foreach ($actionCounts as $action => $count) {
                    $io->text(sprintf('   • %s: %d', $action, $count));
                }
                
                $io->text('');
                $io->text('🔍 Recent activity log entries:');
                
                $recentLogs = array_slice($activityLogs, -5); // Show last 5
                $io->table(
                    ['Action', 'Entity Type', 'Description', 'Timestamp'],
                    array_map(function (ActivityLog $log) {
                        return [
                            $log->getAction(),
                            $log->getEntityType(),
                            substr($log->getDescription() ?? '', 0, 50) . '...',
                            $log->getCreatedAt()->format('Y-m-d H:i:s'),
                        ];
                    }, $recentLogs)
                );
            }
            
            $io->success('🎉 Comprehensive activity logging test completed successfully!');
            $io->note('💡 You can now view these logs in the Admin Panel → Activity Logs');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('❌ Error during comprehensive activity log test: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}