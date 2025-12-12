<?php

namespace App\Command;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:manual-activity-log-test',
    description: 'Manually create an activity log entry to test the system',
)]
class ManualActivityLogTestCommand extends Command
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
            // Manually create an activity log entry
            $activityLog = ActivityLog::create(
                action: 'TEST',
                entityType: 'Command',
                entityId: null,
                entityName: 'Manual Test Command',
                user: null,
                description: 'This is a manual test activity log entry created via command line'
            );

            $this->entityManager->persist($activityLog);
            $this->entityManager->flush();

            $io->success('Manual activity log entry created successfully!');

            // Verify it was created
            $activityLogs = $this->entityManager->getRepository(ActivityLog::class)->findAll();
            
            $io->title('Manual Activity Log Test Results');
            $io->text(sprintf('Total activity logs in database: %d', count($activityLogs)));
            
            if (count($activityLogs) > 0) {
                $latestLog = $activityLogs[0]; // Most recent should be our test entry
                $io->text([
                    'Latest Activity Log Entry:',
                    sprintf('  Action: %s', $latestLog->getAction()),
                    sprintf('  Entity Type: %s', $latestLog->getEntityType()),
                    sprintf('  Description: %s', $latestLog->getDescription()),
                    sprintf('  Created At: %s', $latestLog->getCreatedAt()->format('Y-m-d H:i:s')),
                ]);
            } else {
                $io->warning('No activity logs found in database.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error during manual activity log test: ' . $e->getMessage());
            $io->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}