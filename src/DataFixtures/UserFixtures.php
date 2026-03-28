<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
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
        $admin->setUsername('admin');
        $admin->setEmail('admin@cloudrobe.com');
        $admin->setFullName('Admin User');
        $admin->setRoles(['ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123'  // Default password - should be changed after first login
        );
        $admin->setPassword($hashedPassword);
        
        $manager->persist($admin);

        // Create Staff User
        $staff = new User();
        $staff->setUsername('staff');
        $staff->setEmail('staff@cloudrobe.com');
        $staff->setFullName('Staff User');
        $staff->setRoles(['ROLE_STAFF']);
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $staff,
            'staff123'  // Default password - should be changed after first login
        );
        $staff->setPassword($hashedPassword);
        
        $manager->persist($staff);

        // Create additional sample users
        $adminYannie = new User();
        $adminYannie->setUsername('yannie');
        $adminYannie->setEmail('yannie@cloudrobe.com');
        $adminYannie->setFullName('Yannie Administrator');
        $adminYannie->setRoles(['ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $adminYannie,
            'yannie123'
        );
        $adminYannie->setPassword($hashedPassword);
        
        $manager->persist($adminYannie);

        $staffMember = new User();
        $staffMember->setUsername('john');
        $staffMember->setEmail('john@cloudrobe.com');
        $staffMember->setFullName('John Staff Member');
        $staffMember->setRoles(['ROLE_STAFF']);
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $staffMember,
            'john123'
        );
        $staffMember->setPassword($hashedPassword);
        
        $manager->persist($staffMember);

        $manager->flush();
    }
}
