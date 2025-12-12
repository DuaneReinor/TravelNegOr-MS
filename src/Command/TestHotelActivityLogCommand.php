<?php

namespace App\Command;

use App\Entity\Hotel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-hotel-activity-log',
    description: 'Test hotel creation activity logging',
)]
class TestHotelActivityLogCommand extends Command
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
            // Create a test hotel
            $hotel = new Hotel();
            $hotel->setName('Test Hotel Activity Log')
                  ->setDescription('Testing hotel creation activity logging')
                  ->setPrice(150.00)
                  ->setLocation('Test Location')
                  ->setImage('test-hotel.jpg');

            $this->entityManager->persist($hotel);
            $this->entityManager->flush();

            $io->success('Test hotel created successfully!');

            // Update the hotel
            $hotel->setDescription('Updated test hotel description for activity logging');
            $this->entityManager->flush();

            $io->success('Test hotel updated successfully!');

            // Delete the hotel
            $hotelId = $hotel->getId();
            $this->entityManager->remove($hotel);
            $this->entityManager->flush();

            $io->success('Test hotel deleted successfully!');

            // Check activity logs
            $activityLogs = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)
                ->findBy([], ['createdAt' => 'DESC'], 10);

            $io->title('Activity Log Test Results');
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

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}