<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use App\Entity\User;
use App\Form\Model\ClockingFilter;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Clocking>
 *
 * @method Clocking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Clocking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Clocking[]    findAll()
 * @method Clocking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClockingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clocking::class);
    }

    /**
     * Page de la liste filtrée, en 4 requêtes quelle que soit la volumétrie :
     * total de pointages, total d'heures, page de pointages (+ collaborateur), puis leurs lignes et chantiers.
     *
     * Avec un filtre chantier, on liste les pointages qui ont au moins une ligne sur ce chantier
     * (avec toutes leurs lignes), mais le total d'heures ne compte que ce chantier.
     */
    public function findPageForListing(ClockingFilter $filter, int $page, int $perPage): ClockingListPage
    {
        $totalClockings = (int) $this->createFilteredQueryBuilder($filter)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $hoursQueryBuilder = $this->createFilteredQueryBuilder($filter)
            ->select('COALESCE(SUM(e.duration), 0)')
            ->innerJoin('c.entries', 'e');
        if ($filter->project !== null) {
            $hoursQueryBuilder->andWhere('e.project = :project');
        }
        $totalHours = (int) $hoursQueryBuilder->getQuery()->getSingleScalarResult();

        $pageCount = max(1, (int) ceil($totalClockings / $perPage));
        $page = min(max(1, $page), $pageCount);

        /** @var Clocking[] $clockings */
        $clockings = $this->createFilteredQueryBuilder($filter)
            ->addSelect('u')
            ->innerJoin('c.clockingUser', 'u')
            ->orderBy('c.date', 'DESC')
            ->addOrderBy('u.lastName', 'ASC')
            ->addOrderBy('c.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        // Pas de fetch-join de collection avec LIMIT : on hydrate les lignes de la page en une requête à part.
        if ($clockings !== []) {
            $this->createQueryBuilder('c')
                ->addSelect('e', 'p')
                ->leftJoin('c.entries', 'e')
                ->leftJoin('e.project', 'p')
                ->andWhere('c IN (:clockings)')
                ->setParameter('clockings', $clockings)
                ->getQuery()
                ->getResult();
        }

        return new ClockingListPage($clockings, $page, $perPage, $totalClockings, $totalHours);
    }

    /**
     * Pointages existants pour ces collaborateurs à cette date, indexés par id de collaborateur.
     *
     * @param User[] $users
     *
     * @return array<int, Clocking>
     */
    public function findByUsersAndDateIndexedByUser(array $users, DateTimeInterface $date): array
    {
        if ($users === []) {
            return [];
        }

        $clockings = $this->createQueryBuilder('c')
            ->addSelect('e', 'p')
            ->leftJoin('c.entries', 'e')
            ->leftJoin('e.project', 'p')
            ->andWhere('c.clockingUser IN (:users)')
            ->andWhere('c.date = :date')
            ->setParameter('users', $users)
            ->setParameter('date', DateTime::createFromInterface($date), Types::DATE_MUTABLE)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($clockings as $clocking) {
            $indexed[$clocking->getClockingUser()->getId()] = $clocking;
        }

        return $indexed;
    }

    private function createFilteredQueryBuilder(ClockingFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($filter->user !== null) {
            $queryBuilder->andWhere('c.clockingUser = :user')->setParameter('user', $filter->user);
        }
        if ($filter->project !== null) {
            $queryBuilder
                ->andWhere('EXISTS (SELECT fe.id FROM ' . ClockingEntry::class . ' fe WHERE fe.clocking = c AND fe.project = :project)')
                ->setParameter('project', $filter->project);
        }
        if ($filter->from !== null) {
            $queryBuilder->andWhere('c.date >= :from')->setParameter('from', DateTime::createFromInterface($filter->from), Types::DATE_MUTABLE);
        }
        if ($filter->to !== null) {
            $queryBuilder->andWhere('c.date <= :to')->setParameter('to', DateTime::createFromInterface($filter->to), Types::DATE_MUTABLE);
        }

        return $queryBuilder;
    }
}
