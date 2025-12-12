<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-logout-activity',
    description: 'Test logout activity logging functionality',
)]
class TestLogoutActivityCommand extends Command
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
            $io->title('🔐 Testing Logout Activity Logging');
            
            // Check if there are users in the database
            $users = $this->entityManager->getRepository(User::class)->findAll();
            
            if (empty($users)) {
                $io->warning('No users found in database. Please create some users first.');
                return Command::FAILURE;
            }
            
            $io->text('Available users for logout testing:');
            foreach ($users as $user) {
                $io->text(sprintf('  • %s (%s) - ID: %d', 
                    $user->getFirstName() . ' ' . $user->getLastName(),
                    $user->getEmail(),
                    $user->getId()
                ));
            }
            
            $io->section('🧪 Simulating Logout Activity');
            
            // Simulate logout for the first admin user found
            $adminUser = null;
            foreach ($users as $user) {
                if (in_array('ROLE_ADMIN', $user->getRoles())) {
                    $adminUser = $user;
                    break;
                }
            }
            
            if (!$adminUser) {
                $adminUser = $users[0]; // Use first user if no admin found
            }
            
            // Manually create a logout activity log
            $activityLog = \App\Entity\ActivityLog::create(
                action: 'LOGOUT',
                entityType: 'User',
                entityId: $adminUser->getId(),
                entityName: $adminUser->getFirstName() . ' ' . $adminUser->getLastName(),
                user: $adminUser,
                description: "Simulated logout for testing: {$adminUser->getEmail()}"
            );
            
            $activityLog->setIpAddress('127.0.0.1')
                       ->setUserAgent('CLI Test Command');
            
            $this->entityManager->persist($activityLog);
            $this->entityManager->flush();
            
            $io->success('✅ Logout activity logged successfully!');
            
            // Check recent activity logs
            $activityLogs = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)
                ->findBy([], ['createdAt' => 'DESC'], 10);
            
            $io->section('📊 Recent Activity Logs (Last 10)');
            
            if (!empty($activityLogs)) {
                $logoutLogs = array_filter($activityLogs, function($log) {
                    return $log->getAction() === 'LOGOUT';
                });
                
                if (!empty($logoutLogs)) {
                    $io->text('Found ' . count($logoutLogs) . ' logout entries:');
                    $io->table(
                        ['Action', 'Entity Type', 'User', 'Description', 'Created At'],
                        array_map(function ($log) {
                            return [
                                $log->getAction(),
                                $log->getEntityType(),
                                $log->getUser() ? $log->getUser()->getEmail() : 'N/A',
                                substr($log->getDescription() ?? '', 0, 50) . '...',
                                $log->getCreatedAt()->format('Y-m-d H:i:s'),
                            ];
                        }, $logoutLogs)
                    );
                } else {
                    $io->warning('No logout entries found in recent activity logs.');
                }
            } else {
                $io->warning('No activity logs found in database.');
            }
            
            $io->section('🔍 Testing Event Subscribers');
            
            // Check if SecurityActivitySubscriber is properly configured
            $eventManager = $this->entityManager->getEventManager();
            $subscribers = $eventManager->getListeners('kernel.request');
            
            $io->text('kernel.request listeners found: ' . count($subscribers));
            foreach ($subscribers as $listener) {
                $io->text('  • ' . get_class($listener));
            }
            
            $loginSubscribers = $eventManager->getListeners('security.interactive_login') ?? [];
            $io->text('security.interactive_login listeners found: ' . count($loginSubscribers));
            foreach ($loginSubscribers as $listener) {
                $io->text('  • ' . get_class($listener));
            }
            
            $io->success('🎉 Logout activity logging test completed!');
            $io->note('💡 To test real logout logging:');
            $io->text('1. Start the Symfony server: symfony server:start');
            $io->text('2. Login with any user account');
            $io->text('3. Logout from the application');
            $io->text('4. Check the activity logs in admin panel');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('❌ Error during logout activity test: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}