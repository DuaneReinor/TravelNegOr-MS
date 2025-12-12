<?php

namespace App\Command;

use App\Entity\Destination;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-activity-subscriber',
    description: 'Debug the ActivityLogSubscriber to see if it\'s working',
)]
class DebugActivitySubscriberCommand extends Command
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
            $io->title('🔍 Debugging ActivityLogSubscriber');
            
            // Check if ActivityLogSubscriber is registered
            $eventManager = $this->entityManager->getEventManager();
            $subscribers = $eventManager->getListeners('postPersist');
            $io->text('Registered Doctrine Event Subscribers:');
            
            foreach ($subscribers as $eventName => $listeners) {
                $io->text("- $eventName:");
                foreach ($listeners as $listener) {
                    $io->text("  • " . get_class($listener));
                }
            }
            
            // Check specifically for postPersist events
            $postPersistListeners = $this->entityManager->getEventManager()->getListeners('postPersist') ?? [];
            $io->text('\\npostPersist listeners:');
            foreach ($postPersistListeners as $listener) {
                $io->text("  • " . get_class($listener));
            }
            
            // Test creating an entity
            $io->section('🧪 Testing Entity Creation');
            $destination = new Destination();
            $destination->setName('Debug Test Destination')
                       ->setLocation('Debug City')
                       ->setDescription('Testing if ActivityLogSubscriber is triggered')
                       ->setImage('debug.jpg');
            
            $io->text('Before persist...');
            $this->entityManager->persist($destination);
            $io->text('After persist, before flush...');
            
            $this->entityManager->flush();
            $io->text('After flush!');
            
            // Check activity logs
            $activityLogs = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)->findAll();
            $io->text('\\nCurrent activity logs count: ' . count($activityLogs));
            
            $recentLogs = array_slice($activityLogs, -3);
            if (!empty($recentLogs)) {
                $io->table(
                    ['Action', 'Entity Type', 'Description', 'Created At'],
                    array_map(function ($log) {
                        return [
                            $log->getAction(),
                            $log->getEntityType(),
                            substr($log->getDescription() ?? '', 0, 50),
                            $log->getCreatedAt()->format('Y-m-d H:i:s'),
                        ];
                    }, $recentLogs)
                );
            } else {
                $io->warning('No recent activity logs found!');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}