<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Project;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base des tests qui touchent la base : schéma recréé à partir du mapping avant chaque test.
 */
abstract class DatabaseTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function createProject(string $name, string $dateStart = '2020-01-01', ?string $dateEnd = null): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setAddress("Adresse $name");
        $project->setDateStart(new DateTime($dateStart));
        $project->setDateEnd($dateEnd === null ? null : new DateTime($dateEnd));

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    protected function createUser(string $firstName, string $lastName): User
    {
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setMatricule("{$firstName}_{$lastName}");

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
