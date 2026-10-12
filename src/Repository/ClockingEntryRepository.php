<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ClockingEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClockingEntry>
 *
 * @method ClockingEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClockingEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClockingEntry[]    findAll()
 * @method ClockingEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClockingEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClockingEntry::class);
    }
}
