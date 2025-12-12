<?php

namespace App\Command;

use App\Entity\ActivityLog;
use App\Entity\Destination;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-activity-log',
    description: 'Test the activity logging system by creating sample data',
)]
class TestActivityLogCommand extends Command
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
            // Create a test destination to trigger activity logging
            $destination = new Destination();
            $destination->setName('Test Destination')
                       ->setLocation('Test Location')
                       ->setDescription('This is a test destination for activity logging')
                       ->setImage('test-image.jpg');

            $this->entityManager->persist($destination);
            $this->entityManager->flush();

            $io->success('Test destination created successfully!');

            // Update the destination to test UPDATE logging
            $destination->setDescription('Updated description for testing');
            $this->entityManager->flush();

            $io->success('Test destination updated successfully!');

            // Delete the test destination
            $this->entityManager->remove($destination);
            $this->entityManager->flush();

            $io->success('Test destination deleted successfully!');

            // Check activity logs
            $activityLogs = $this->entityManager->getRepository(ActivityLog::class)->findAll();
            
            $io->title('Activity Log Test Results');
            $io->text(sprintf('Total activity logs created: %d', count($activityLogs)));
            
            if (count($activityLogs) > 0) {
                $io->table(
                    ['Action', 'Entity Type', 'Entity Name', 'Description', 'Created At'],
                    array_map(function (ActivityLog $log) {
                        return [
                            $log->getAction(),
                            $log->getEntityType(),
                            $log->getEntityName() ?? 'N/A',
                            $log->getDescription(),
                            $log->getCreatedAt()->format('Y-m-d H:i:s'),
                        ];
                    }, $activityLogs)
                );
            } else {
                $io->warning('No activity logs were created. Please check the ActivityLogSubscriber configuration.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error during activity log test: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}