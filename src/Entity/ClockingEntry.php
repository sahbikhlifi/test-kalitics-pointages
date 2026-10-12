<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClockingEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ligne d'un pointage : le temps passé sur un chantier.
 */
#[ORM\Entity(repositoryClass: ClockingEntryRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CLOCKING_ENTRY_PROJECT', columns: ['clocking_id', 'project_id'])]
class ClockingEntry
{
    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Clocking $clocking = null;
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(value: 10)]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $duration = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'clockingEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    public function getClocking(): ?Clocking
    {
        return $this->clocking;
    }

    public function setClocking(?Clocking $clocking): static
    {
        $this->clocking = $clocking;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }
}
