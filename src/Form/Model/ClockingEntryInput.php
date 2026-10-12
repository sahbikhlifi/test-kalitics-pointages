<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Project;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une ligne (chantier, durée) saisie dans le formulaire de pointage.
 */
class ClockingEntryInput
{
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(value: 10)]
    public ?int $duration = null;
    #[Assert\NotNull]
    public ?Project $project = null;
}
