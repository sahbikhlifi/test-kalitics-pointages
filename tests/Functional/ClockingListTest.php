<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use App\Entity\Project;
use App\Entity\User;
use App\Tests\DatabaseTestCase;
use DateTime;

class ClockingListTest extends DatabaseTestCase
{
    private Project $projectA;
    private Project $projectB;
    private User $jean;
    private User $marie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jean = $this->createUser('Jean', 'Dupont');
        $this->marie = $this->createUser('Marie', 'Curie');
        $this->projectA = $this->createProject('Alpha');
        $this->projectB = $this->createProject('Bravo');

        $this->clock($this->jean, '2025-03-10', [[$this->projectA, 4], [$this->projectB, 3]]);
        $this->clock($this->jean, '2025-03-11', [[$this->projectB, 8]]);
        $this->clock($this->marie, '2025-03-10', [[$this->projectA, 6]]);
        $this->clock($this->marie, '2025-04-02', [[$this->projectB, 2]]);
    }

    public function testWithoutFilterShowsEverythingWithTotals(): void
    {
        $this->client->request('GET', '/clockings');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.results-summary', '4 pointages');
        self::assertSelectorTextContains('.results-summary', '23 h');
        self::assertCount(4, $this->client->getCrawler()->filter('#clockings-table tbody tr'));
    }

    public function testListUrlsHaveNoTrailingSlashAndOldUrlsRedirect(): void
    {
        foreach (['/clockings', '/projects', '/users'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);

            // Anciennes URL (avec barre finale) : redirection permanente vers l'URL canonique.
            $this->client->request('GET', "$url/");
            self::assertResponseStatusCodeSame(301);
            self::assertResponseRedirects("http://localhost$url");
        }
    }

    public function testFilterByUser(): void
    {
        $this->client->request('GET', '/clockings', ['user' => $this->marie->getId()]);

        self::assertSelectorTextContains('.results-summary', '2 pointages');
        self::assertSelectorTextContains('.results-summary', '8 h');
        self::assertSelectorTextNotContains('#clockings-table', 'Dupont');
    }

    public function testFilterByProjectKeepsWholeClockingButCountsOnlyThatProject(): void
    {
        $this->client->request('GET', '/clockings', ['project' => $this->projectA->getId()]);

        self::assertSelectorTextContains('.results-summary', '2 pointages');
        self::assertSelectorTextContains('.results-summary', '10 h sur Alpha');
        // Le pointage de Jean du 10/03 reste affiché en entier (Alpha + Bravo).
        self::assertSelectorTextContains('#clockings-table', 'Bravo');
    }

    public function testFilterByPeriod(): void
    {
        $this->client->request('GET', '/clockings', ['from' => '2025-03-11', 'to' => '2025-03-31']);

        self::assertSelectorTextContains('.results-summary', '1 pointage');
        self::assertSelectorTextContains('#clockings-table', '11/03/2025');
    }

    public function testInvertedPeriodShowsErrorAndUnfilteredList(): void
    {
        $this->client->request('GET', '/clockings', ['from' => '2025-04-01', 'to' => '2025-03-01']);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.filters', 'La date de fin doit être postérieure à la date de début.');
        self::assertSelectorTextContains('.results-summary', '4 pointages');
    }

    public function testNoResultOffersToResetFilters(): void
    {
        $this->client->request('GET', '/clockings', ['from' => '2020-01-01', 'to' => '2020-01-31']);

        self::assertSelectorTextContains('#clockings-table', 'Aucun pointage ne correspond à ces filtres.');
        self::assertSelectorExists('#clockings-table a[href="/clockings"]');
    }

    public function testPaginationKeepsFilters(): void
    {
        $user = $this->createUser('Paul', 'Martin');
        for ($day = 1; $day <= 30; $day++) {
            $this->clock($user, sprintf('2024-01-%02d', $day), [[$this->projectA, 1]]);
        }

        $this->client->request('GET', '/clockings', ['user' => $user->getId()]);
        self::assertCount(25, $this->client->getCrawler()->filter('#clockings-table tbody tr'));
        self::assertSelectorExists(sprintf('.pagination a[href="/clockings?user=%d&page=2"]', $user->getId()));

        $this->client->request('GET', '/clockings', ['user' => $user->getId(), 'page' => 2]);
        self::assertCount(5, $this->client->getCrawler()->filter('#clockings-table tbody tr'));
        self::assertSelectorTextContains('.pagination [aria-current="page"]', '2');

        // Page hors limites : on retombe sur la dernière.
        $this->client->request('GET', '/clockings', ['user' => $user->getId(), 'page' => 99]);
        self::assertCount(5, $this->client->getCrawler()->filter('#clockings-table tbody tr'));
    }

    public function testDeleteReturnsToTheSameFilteredList(): void
    {
        $crawler = $this->client->request('GET', '/clockings', ['user' => $this->marie->getId()]);
        $this->client->submit($crawler->selectButton('Supprimer')->first()->form());

        self::assertResponseRedirects(sprintf('/clockings?user=%d', $this->marie->getId()));
        self::assertSame(3, $this->entityManager->getRepository(Clocking::class)->count([]));
    }

    /**
     * @param array<array{Project, int}> $entries
     */
    private function clock(User $user, string $date, array $entries): void
    {
        $clocking = new Clocking();
        $clocking->setClockingUser($user);
        $clocking->setDate(new DateTime($date));
        foreach ($entries as [$project, $duration]) {
            $clocking->addEntry((new ClockingEntry())->setProject($project)->setDuration($duration));
        }

        $this->entityManager->persist($clocking);
        $this->entityManager->flush();
    }
}
