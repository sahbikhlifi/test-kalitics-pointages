<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Column(length: 255)]
    private ?string $address = null;
    /**
     * @var Collection<int, ClockingEntry>
     */
    #[ORM\OneToMany(targetEntity: ClockingEntry::class, mappedBy: 'project')]
    private Collection $clockingEntries;
    #[Assert\GreaterThan(propertyPath: 'dateStart')]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateEnd = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateStart = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function __construct()
    {
        $this->clockingEntries = new ArrayCollection();
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return Collection<int, ClockingEntry>
     */
    public function getClockingEntries(): Collection
    {
        return $this->clockingEntries;
    }

    public function getDateEnd(): ?DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?DateTimeInterface $dateEnd): void
    {
        $this->dateEnd = $dateEnd;
    }

    public function getDateStart(): ?DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?DateTimeInterface $dateStart): void
    {
        $this->dateStart = $dateStart;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Le chantier est-il ouvert à cette date (bornes incluses, sans date de fin = toujours ouvert) ?
     */
    public function isOpenOn(DateTimeInterface $date): bool
    {
        $day = $date->format('Y-m-d');

        if ($this->dateStart !== null && $day < $this->dateStart->format('Y-m-d')) {
            return false;
        }

        return $this->dateEnd === null || $day <= $this->dateEnd->format('Y-m-d');
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
