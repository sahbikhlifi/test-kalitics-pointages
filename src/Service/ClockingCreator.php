<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use App\Exception\ClockingConflictException;
use App\Form\Model\ClockingRequest;
use App\Repository\ClockingRepository;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transforme une saisie de pointage en un pointage par collaborateur.
 *
 * Si le collaborateur a déjà un pointage ce jour-là, les nouvelles lignes y sont ajoutées :
 * on garde ainsi une seule ligne « pointage » par collaborateur et par jour.
 * La saisie est supposée validée (cf. ClockingRequestConstraint).
 *
 * Entre cette validation et l'écriture, une autre requête peut enregistrer un pointage
 * pour le même collaborateur et le même jour : les contraintes uniques de la base le refusent,
 * et on le signale par une ClockingConflictException plutôt qu'une erreur 500.
 */
class ClockingCreator
{
    public function __construct(
        private readonly ClockingRepository $clockingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Clocking[] un pointage par collaborateur saisi
     *
     * @throws ClockingConflictException si un pointage concurrent a été enregistré entre-temps
     */
    public function create(ClockingRequest $request): array
    {
        // Le mapping est en DATE_MUTABLE : on évite de partager la même instance entre pointages.
        $date = DateTime::createFromInterface($request->date);

        try {
            return $this->entityManager->wrapInTransaction(function () use ($request, $date): array {
                $existingClockings = $this->clockingRepository->findByUsersAndDateIndexedByUser($request->users, $date);

                $clockings = [];
                foreach ($request->users as $user) {
                    $clocking = $existingClockings[$user->getId()] ?? null;
                    if ($clocking === null) {
                        $clocking = new Clocking();
                        $clocking->setClockingUser($user);
                        $clocking->setDate(clone $date);
                        $this->entityManager->persist($clocking);
                    }

                    foreach ($request->entries as $input) {
                        $entry = new ClockingEntry();
                        $entry->setProject($input->project);
                        $entry->setDuration($input->duration);
                        $clocking->addEntry($entry);
                    }

                    $clockings[] = $clocking;
                }

                $this->entityManager->flush();

                return $clockings;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // La transaction est annulée : rien n'a été écrit pour aucun des collaborateurs.
            throw new ClockingConflictException($exception);
        }
    }
}
