<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClockingRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un pointage : un collaborateur, un jour, et 1..n lignes (chantier, durée).
 */
#[ORM\Entity(repositoryClass: ClockingRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CLOCKING_USER_DATE', columns: ['clocking_user_id', 'date'])]
#[UniqueEntity(fields: ['clockingUser', 'date'], message: 'clocking.user_date.unique')]
class Clocking
{
    #[ORM\ManyToOne(inversedBy: 'clockings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $clockingUser = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $date = null;
    /**
     * @var Collection<int, ClockingEntry>
     */
    #[Assert\Count(min: 1, minMessage: 'clocking.entries.min')]
    #[Assert\Valid]
    #[ORM\OneToMany(targetEntity: ClockingEntry::class, mappedBy: 'clocking', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $entries;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function addEntry(ClockingEntry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setClocking($this);
        }

        return $this;
    }

    public function getClockingUser(): ?User
    {
        return $this->clockingUser;
    }

    public function setClockingUser(?User $clockingUser): static
    {
        $this->clockingUser = $clockingUser;

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    /**
     * @return Collection<int, ClockingEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalDuration(): int
    {
        $total = 0;
        foreach ($this->entries as $entry) {
            $total += (int) $entry->getDuration();
        }

        return $total;
    }

    public function removeEntry(ClockingEntry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            // set the owning side to null (unless already changed)
            if ($entry->getClocking() === $this) {
                $entry->setClocking(null);
            }
        }

        return $this;
    }
}
