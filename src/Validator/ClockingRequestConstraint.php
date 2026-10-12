<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Règles métier transverses d'une saisie de pointage :
 * - un chantier n'apparaît qu'une fois par saisie,
 * - le chantier doit être ouvert à la date pointée,
 * - un collaborateur ne peut pas être pointé deux fois sur le même chantier le même jour.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ClockingRequestConstraint extends Constraint
{
    public string $alreadyClockedMessage = 'clocking.project.already_clocked';
    public string $duplicateProjectMessage = 'clocking.project.duplicate';
    public string $projectClosedMessage = 'clocking.project.closed';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
