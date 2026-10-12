<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

/**
 * Un pointage concurrent a été enregistré entre la validation de la saisie et son écriture
 * (même collaborateur, même jour, ou même chantier sur ce pointage).
 */
final class ClockingConflictException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('A concurrent clocking was saved for the same user and day.', 0, $previous);
    }
}
