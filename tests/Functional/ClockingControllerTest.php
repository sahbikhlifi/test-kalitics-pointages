<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use App\Repository\ClockingRepository;
use App\Tests\DatabaseTestCase;

class ClockingControllerTest extends DatabaseTestCase
{
    public function testCreateFormIsDisplayed(): void
    {
        $this->createUser('Jean', 'Dupont');
        $this->createProject('A');

        $this->client->request('GET', '/clockings/create');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="create_clocking[users][]"]');
        self::assertSelectorExists('select[name="create_clocking[entries][0][project]"]');
        self::assertSelectorExists('#clocking-entries[data-prototype]');
    }

    public function testCreateManyUsersWithManyProjects(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $marie = $this->createUser('Marie', 'Curie');
        $projectA = $this->createProject('A');
        $projectB = $this->createProject('B');

        $this->submit([$jean->getId(), $marie->getId()], [[$projectA->getId(), 3], [$projectB->getId(), 5]]);

        self::assertResponseRedirects('/clockings');
        self::assertSame(2, $this->entityManager->getRepository(Clocking::class)->count([]));
        self::assertSame(4, $this->entityManager->getRepository(ClockingEntry::class)->count([]));

        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash-success', '2 pointages enregistrés');
        self::assertSelectorTextContains('table', '8 h');
    }

    public function testCreateWithDefaultDateOfToday(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $project = $this->createProject('A');

        $this->submit([$jean->getId()], [[$project->getId(), 3]], (new \DateTime('today'))->format('Y-m-d'));

        self::assertResponseRedirects('/clockings');
        self::assertSame(1, $this->entityManager->getRepository(Clocking::class)->count([]));
    }

    public function testDuplicateProjectIsRejected(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $project = $this->createProject('A');

        $this->submit([$jean->getId()], [[$project->getId(), 3], [$project->getId(), 2]]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'est saisi plusieurs fois');
        self::assertSame(0, $this->entityManager->getRepository(Clocking::class)->count([]));
    }

    public function testClosedProjectIsRejected(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $project = $this->createProject('Fermé', '2020-01-01', '2020-02-01');

        $this->submit([$jean->getId()], [[$project->getId(), 3]]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'n\'est pas ouvert à cette date');
    }

    public function testUserAlreadyClockedOnProjectThatDayIsRejected(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $project = $this->createProject('A');

        $this->submit([$jean->getId()], [[$project->getId(), 3]]);
        $this->submit([$jean->getId()], [[$project->getId(), 2]]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Dupont Jean est déjà pointé(e) sur « A »');
        self::assertSame(1, $this->entityManager->getRepository(ClockingEntry::class)->count([]));
    }

    public function testMissingUsersAndEntriesAreRejected(): void
    {
        $this->client->request('GET', '/clockings/create');
        $this->client->submitForm('create_clocking[submit]', [
            'create_clocking[date]' => '2025-03-10',
            'create_clocking[entries][0][project]' => '',
            'create_clocking[entries][0][duration]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Sélectionnez au moins un collaborateur.');
        self::assertSelectorTextContains('body', 'Ajoutez au moins un chantier.');
    }

    /**
     * Fenêtre de concurrence de bout en bout : le repository ne « voit » pas le pointage
     * enregistré entre-temps, la validation passe, la base refuse l'écriture (contrainte unique),
     * la transaction est annulée et le formulaire est réaffiché avec la saisie.
     */
    public function testConcurrentClockingShowsErrorAndKeepsInput(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $project = $this->createProject('A');
        // Pointage « concurrent » écrit directement en base, avant toute requête HTTP.
        $existing = new Clocking();
        $existing->setClockingUser($jean);
        $existing->setDate(new \DateTime('2025-03-10'));
        $existing->addEntry((new ClockingEntry())->setProject($project)->setDuration(3));
        $this->entityManager->persist($existing);
        $this->entityManager->flush();

        $staleRepository = $this->createMock(ClockingRepository::class);
        $staleRepository->method('findByUsersAndDateIndexedByUser')->willReturn([]);
        $this->client->disableReboot();
        static::getContainer()->set(ClockingRepository::class, $staleRepository);

        $other = $this->createProject('B');
        $this->submit([$jean->getId()], [[$other->getId(), 2]]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-errors', 'Un autre pointage vient d\'être enregistré');
        // La saisie est conservée pour pouvoir la renvoyer.
        self::assertSelectorExists(sprintf('input[name="create_clocking[users][]"][value="%d"][checked]', $jean->getId()));
        self::assertSelectorExists('input[name="create_clocking[entries][0][duration]"][value="2"]');

        $connection = static::getContainer()->get('doctrine')->getConnection();
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM clocking_entry'));
    }

    public function testDeleteRequiresPostWithValidCsrfToken(): void
    {
        $jean = $this->createUser('Jean', 'Dupont');
        $project = $this->createProject('A');
        $this->submit([$jean->getId()], [[$project->getId(), 3]]);
        $clockingId = $this->entityManager->getRepository(Clocking::class)->findOneBy([])->getId();

        $this->client->request('GET', "/clockings/$clockingId/delete");
        self::assertResponseStatusCodeSame(405);

        $this->client->request('POST', "/clockings/$clockingId/delete", ['_token' => 'invalid']);
        self::assertResponseStatusCodeSame(403);

        $crawler = $this->client->request('GET', '/clockings');
        $this->client->submit($crawler->selectButton('Supprimer')->form());

        self::assertResponseRedirects('/clockings');
        self::assertSame(0, $this->entityManager->getRepository(Clocking::class)->count([]));
        self::assertSame(0, $this->entityManager->getRepository(ClockingEntry::class)->count([]));
    }

    /**
     * Soumission brute : simule le formulaire après ajout dynamique de lignes en JS.
     *
     * @param int[]                  $userIds
     * @param array<array{int, int}> $entries
     */
    private function submit(array $userIds, array $entries, string $date = '2025-03-10'): void
    {
        $crawler = $this->client->request('GET', '/clockings/create');
        $token = $crawler->filter('input[name="create_clocking[_token]"]')->attr('value');

        $data = [
            'users' => array_map('strval', $userIds),
            'date' => $date,
            'entries' => [],
            '_token' => $token,
        ];
        foreach ($entries as $index => [$projectId, $duration]) {
            $data['entries'][$index] = ['project' => (string) $projectId, 'duration' => (string) $duration];
        }

        $this->client->request('POST', '/clockings/create', ['create_clocking' => $data]);
        $this->entityManager->clear();
    }
}
