<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setEmail('admin@travelnegor.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);
        
        $manager->persist($admin);

        // Create Staff User 1
        $staff1 = new User();
        $staff1->setEmail('staff1@travelnegor.com');
        $staff1->setFirstName('John');
        $staff1->setLastName('Doe');
        $staff1->setRoles(['ROLE_STAFF']);
        
        $hashedPassword1 = $this->passwordHasher->hashPassword($staff1, 'staff123');
        $staff1->setPassword($hashedPassword1);
        
        $manager->persist($staff1);

        // Create Staff User 2
        $staff2 = new User();
        $staff2->setEmail('staff2@travelnegor.com');
        $staff2->setFirstName('Jane');
        $staff2->setLastName('Smith');
        $staff2->setRoles(['ROLE_STAFF']);
        
        $hashedPassword2 = $this->passwordHasher->hashPassword($staff2, 'staff123');
        $staff2->setPassword($hashedPassword2);
        
        $manager->persist($staff2);

        // Create Regular Customer User
        $customer = new User();
        $customer->setEmail('customer@travelnegor.com');
        $customer->setFirstName('Customer');
        $customer->setLastName('User');
        $customer->setRoles(['ROLE_USER']);
        
        $hashedPassword3 = $this->passwordHasher->hashPassword($customer, 'user123');
        $customer->setPassword($hashedPassword3);
        
        $manager->persist($customer);

        $manager->flush();
    }
}
