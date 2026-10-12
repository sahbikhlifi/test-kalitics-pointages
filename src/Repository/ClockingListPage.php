<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Clocking;

/**
 * Une page de la liste des pointages, avec les totaux de l'ensemble filtré.
 */
final class ClockingListPage
{
    /**
     * @param Clocking[] $clockings
     */
    public function __construct(
        public readonly array $clockings,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalClockings,
        public readonly int $totalHours,
    ) {
    }

    public function getPageCount(): int
    {
        return max(1, (int) ceil($this->totalClockings / $this->perPage));
    }
}
