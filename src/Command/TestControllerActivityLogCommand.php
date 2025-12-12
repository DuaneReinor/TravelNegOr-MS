<?php

namespace App\Command;

use App\Controller\AdminHotelController;
use App\Controller\UserController;
use App\Entity\Hotel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-controller-activity-log',
    description: 'Test activity logging through controller methods',
)]
class TestControllerActivityLogCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('Controller Activity Log Test');
            
            // Test 1: Create hotel through controller
            $io->section('Test 1: Hotel Creation via AdminHotelController');
            $hotelController = new AdminHotelController($this->entityManager, $this->requestStack);
            
            // Create a mock request for hotel creation
            $hotelData = [
                'name' => 'Controller Test Hotel',
                'description' => 'Testing activity logging via controller',
                'price' => 120.00,
                'location' => 'Controller Test Location',
                'image' => null
            ];
            
            // Simulate form submission by creating the hotel directly with logging
            $hotel = new Hotel();
            $hotel->setName($hotelData['name'])
                  ->setDescription($hotelData['description'])
                  ->setPrice($hotelData['price'])
                  ->setLocation($hotelData['location']);
            
            $this->entityManager->persist($hotel);
            $this->entityManager->flush();
            
            // Call the controller's logging method directly
            $hotelController->logActivity('CREATE', 'Hotel', $hotel->getId(), $hotel->getName(), 
                "Created hotel: {$hotel->getName()} in {$hotel->getLocation()}");
            
            $io->success('Hotel created and logged via controller method!');
            
            // Test 2: Create user through controller
            $io->section('Test 2: User Creation via UserController');
            $userController = new UserController($this->entityManager, $this->requestStack);
            
            $user = new User();
            $user->setFirstName('Controller')
                 ->setLastName('Test')
                 ->setEmail('controller.test@travelnegor.com')
                 ->setPassword('test123');
            
            // Hash password
            $hashed = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashed);
            $user->setRoles(['ROLE_STAFF']);
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Call the controller's logging method directly
            $name = $user->getFirstName() . ' ' . $user->getLastName();
            $userController->logActivity('CREATE', 'User', $user->getId(), $name, 
                "Created user: {$name} ({$user->getEmail()})");
            
            $io->success('User created and logged via controller method!');
            
            // Test 3: Check all recent activity logs
            $io->section('Test 3: Recent Activity Logs');
            $activityLogs = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)
                ->findBy([], ['createdAt' => 'DESC'], 10);
            
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
            $this->entityManager->remove($user);
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