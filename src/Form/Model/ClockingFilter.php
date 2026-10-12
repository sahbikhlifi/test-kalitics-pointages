<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Project;
use App\Entity\User;
use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Critères de recherche de la liste des pointages (tous optionnels).
 */
class ClockingFilter
{
    public ?DateTimeInterface $from = null;
    public ?Project $project = null;
    #[Assert\GreaterThanOrEqual(propertyPath: 'from', message: 'clocking.filter.period')]
    public ?DateTimeInterface $to = null;
    public ?User $user = null;

    public function isEmpty(): bool
    {
        return $this->from === null && $this->to === null && $this->project === null && $this->user === null;
    }
}
