<?php

declare(strict_types=1);

namespace App\Validator;

use App\Form\Model\ClockingRequest;
use App\Repository\ClockingRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ClockingRequestConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ClockingRepository $clockingRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ClockingRequestConstraint) {
            throw new UnexpectedTypeException($constraint, ClockingRequestConstraint::class);
        }

        if (!$value instanceof ClockingRequest) {
            throw new UnexpectedValueException($value, ClockingRequest::class);
        }

        if ($value->date === null) {
            return;
        }

        $projectIds = [];
        foreach ($value->entries as $index => $entry) {
            $project = $entry->project;
            if ($project === null) {
                continue;
            }

            if (isset($projectIds[$project->getId()])) {
                $this->context->buildViolation($constraint->duplicateProjectMessage)
                    ->setParameter('{{ project }}', (string) $project->getName())
                    ->atPath("entries[$index].project")
                    ->addViolation();
                continue;
            }
            $projectIds[$project->getId()] = $project;

            if (!$project->isOpenOn($value->date)) {
                $this->context->buildViolation($constraint->projectClosedMessage)
                    ->setParameter('{{ project }}', (string) $project->getName())
                    ->atPath("entries[$index].project")
                    ->addViolation();
            }
        }

        $existingClockings = $this->clockingRepository->findByUsersAndDateIndexedByUser($value->users, $value->date);
        foreach ($existingClockings as $clocking) {
            foreach ($clocking->getEntries() as $existingEntry) {
                $project = $existingEntry->getProject();
                if (!isset($projectIds[$project->getId()])) {
                    continue;
                }

                $this->context->buildViolation($constraint->alreadyClockedMessage)
                    ->setParameter('{{ user }}', $clocking->getClockingUser()->getFullName())
                    ->setParameter('{{ project }}', (string) $project->getName())
                    ->atPath('users')
                    ->addViolation();
            }
        }
    }
}
