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
    name: 'app:simple-debug',
    description: 'Simple test to check if ActivityLogSubscriber works',
)]
class SimpleDebugCommand extends Command
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
            $io->title('🔍 Simple Activity Log Test');
            
            // Check postPersist listeners
            $postPersistListeners = $this->entityManager->getEventManager()->getListeners('postPersist');
            $io->text('postPersist listeners found: ' . count($postPersistListeners));
            foreach ($postPersistListeners as $listener) {
                $io->text("  • " . get_class($listener));
            }
            
            // Test creating an entity
            $io->section('🧪 Testing Entity Creation');
            $destination = new Destination();
            $destination->setName('Simple Debug Test')
                       ->setLocation('Debug City')
                       ->setDescription('Testing activity logging')
                       ->setImage('debug.jpg');
            
            $io->text('Creating destination...');
            $this->entityManager->persist($destination);
            $this->entityManager->flush();
            $io->text('Destination created and flushed!');
            
            // Check activity logs
            $activityLogs = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)->findAll();
            $io->text('Total activity logs: ' . count($activityLogs));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}