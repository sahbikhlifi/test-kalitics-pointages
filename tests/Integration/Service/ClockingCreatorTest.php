<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use App\Entity\Project;
use App\Entity\User;
use App\Exception\ClockingConflictException;
use App\Form\Model\ClockingEntryInput;
use App\Form\Model\ClockingRequest;
use App\Repository\ClockingRepository;
use App\Service\ClockingCreator;
use App\Tests\DatabaseTestCase;
use DateTime;

class ClockingCreatorTest extends DatabaseTestCase
{
    public function testOneUserManyProjectsCreatesOneClockingWithManyEntries(): void
    {
        $user = $this->createUser('Jean', 'Dupont');
        $projectA = $this->createProject('A');
        $projectB = $this->createProject('B');

        $clockings = $this->creator()->create($this->request([$user], [[$projectA, 3], [$projectB, 5]]));

        self::assertCount(1, $clockings);
        $this->entityManager->clear();
        self::assertSame(1, $this->countRows(Clocking::class));
        self::assertSame(2, $this->countRows(ClockingEntry::class));

        $clocking = $this->entityManager->getRepository(Clocking::class)->findOneBy([]);
        self::assertSame(8, $clocking->getTotalDuration());
    }

    public function testManyUsersCreatesOneClockingPerUserWithSameEntries(): void
    {
        $users = [$this->createUser('Jean', 'Dupont'), $this->createUser('Marie', 'Curie'), $this->createUser('Paul', 'Martin')];
        $project = $this->createProject('A');

        $clockings = $this->creator()->create($this->request($users, [[$project, 7]]));

        self::assertCount(3, $clockings);
        $this->entityManager->clear();
        self::assertSame(3, $this->countRows(Clocking::class));
        self::assertSame(3, $this->countRows(ClockingEntry::class));
    }

    public function testExistingClockingSameDayIsCompletedInsteadOfDuplicated(): void
    {
        $user = $this->createUser('Jean', 'Dupont');
        $projectA = $this->createProject('A');
        $projectB = $this->createProject('B');

        $this->creator()->create($this->request([$user], [[$projectA, 3]]));
        $this->creator()->create($this->request([$user], [[$projectB, 4]]));

        $this->entityManager->clear();
        self::assertSame(1, $this->countRows(Clocking::class));
        self::assertSame(2, $this->countRows(ClockingEntry::class));
    }

    /**
     * Fenêtre de concurrence : un pointage (Jean, 10/03) est enregistré par une autre requête
     * après la lecture des pointages existants. La contrainte unique refuse l'écriture :
     * exception métier, et rien n'est écrit, pas même pour Marie.
     */
    public function testConcurrentClockingRaisesConflictAndWritesNothing(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $marie = $this->createUser('Marie', 'Curie');
        $project = $this->createProject('A');
        $this->creator()->create($this->request([$jean], [[$project, 3]]));
        $this->entityManager->clear();

        $staleRepository = $this->createMock(ClockingRepository::class);
        $staleRepository->method('findByUsersAndDateIndexedByUser')->willReturn([]);
        $creator = new ClockingCreator($staleRepository, $this->entityManager);

        $users = [
            $this->entityManager->find(User::class, $marie->getId()),
            $this->entityManager->find(User::class, $jean->getId()),
        ];
        $projectRef = $this->entityManager->find(Project::class, $project->getId());

        try {
            $creator->create($this->request($users, [[$projectRef, 2]]));
            self::fail('ClockingConflictException attendue.');
        } catch (ClockingConflictException $exception) {
            self::assertInstanceOf(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class, $exception->getPrevious());
        }

        // L'EntityManager est fermé après le rollback : on compte directement en SQL.
        $connection = $this->entityManager->getConnection();
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM clocking'));
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM clocking_entry'));
    }

    private function countRows(string $class): int
    {
        return $this->entityManager->getRepository($class)->count([]);
    }

    private function creator(): ClockingCreator
    {
        return static::getContainer()->get(ClockingCreator::class);
    }

    /**
     * @param \App\Entity\User[]                      $users
     * @param array<array{\App\Entity\Project, int}> $entries
     */
    private function request(array $users, array $entries): ClockingRequest
    {
        $request = new ClockingRequest();
        $request->users = $users;
        $request->date = new DateTime('2025-03-10');
        foreach ($entries as [$project, $duration]) {
            $input = new ClockingEntryInput();
            $input->project = $project;
            $input->duration = $duration;
            $request->entries[] = $input;
        }

        return $request;
    }
}
