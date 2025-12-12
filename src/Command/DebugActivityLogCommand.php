<?php

namespace App\Command;

use App\Entity\ActivityLog;
use App\Entity\Hotel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-activity-log',
    description: 'Debug activity logging by manually creating logs',
)]
class DebugActivityLogCommand extends Command
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
            $io->title('Manual Activity Log Test');
            
            // Test 1: Create a manual activity log
            $io->section('Test 1: Manual Activity Log Creation');
            $activityLog = ActivityLog::create(
                action: 'TEST_CREATE',
                entityType: 'TestEntity',
                entityId: 1,
                entityName: 'Test Entity',
                description: 'Manual test activity log creation'
            );
            
            $this->entityManager->persist($activityLog);
            $this->entityManager->flush();
            
            $io->success('Manual activity log created successfully!');
            
            // Test 2: Create a hotel and check if subscriber fires
            $io->section('Test 2: Hotel Creation (Testing Subscriber)');
            $hotel = new Hotel();
            $hotel->setName('Debug Test Hotel')
                  ->setDescription('Testing activity logging')
                  ->setPrice(100.00)
                  ->setLocation('Debug Location');
            
            $this->entityManager->persist($hotel);
            $this->entityManager->flush();
            
            $io->success('Hotel created successfully!');
            
            // Test 3: Check all recent activity logs
            $io->section('Test 3: Recent Activity Logs');
            $activityLogs = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)
                ->findBy([], ['createdAt' => 'DESC'], 5);
            
            if (empty($activityLogs)) {
                $io->warning('No activity logs found in database!');
            } else {
                $io->table(
                    ['Action', 'Entity Type', 'Entity Name', 'Description', 'Created At'],
                    array_map(function ($log) {
                        return [
                            $log->getAction(),
                            $log->getEntityType(),
                            $log->getEntityName() ?? 'N/A',
                            $log->getDescription(),
                            $log->getCreatedAt()->format('Y-m-d H:i:s')
                        ];
                    }, $activityLogs)
                );
            }
            
            // Clean up test data
            $this->entityManager->remove($hotel);
            $this->entityManager->remove($activityLog);
            $this->entityManager->flush();
            
            $io->success('Test data cleaned up!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            $io->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}