<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\User;
use App\Validator\ClockingRequestConstraint;
use DateTime;
use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Saisie d'un pointage : 1..n collaborateurs, une date, 1..n lignes (chantier, durée).
 *
 * - 1 collaborateur  => 1 pointage avec n lignes (cas « je pointe ma journée »),
 * - n collaborateurs => n pointages avec les mêmes lignes (cas « chef de chantier »).
 */
#[ClockingRequestConstraint]
class ClockingRequest
{
    #[Assert\NotNull]
    #[Assert\LessThanOrEqual(value: 'today', message: 'clocking.date.future')]
    public ?DateTimeInterface $date = null;
    /**
     * @var ClockingEntryInput[]
     */
    #[Assert\Count(min: 1, minMessage: 'clocking.entries.min')]
    #[Assert\Valid]
    public array $entries = [];
    /**
     * @var User[]
     */
    #[Assert\Count(min: 1, minMessage: 'clocking.users.min')]
    public array $users = [];

    public static function createDefault(): self
    {
        $request = new self();
        // DateTime (et non Immutable) : le formulaire renvoie l'objet initial si la date n'est pas modifiée,
        // et le mapping Doctrine est en DATE_MUTABLE.
        $request->date = new DateTime('today');
        $request->entries[] = new ClockingEntryInput();

        return $request;
    }
}
