<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Form\Model\ClockingEntryInput;
use App\Form\Model\ClockingRequest;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateClockingType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $options
     *
     * @return void
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('users', EntityType::class, [
            'class' => User::class,
            'choice_label' => static fn (User $choice): string => $choice->getFullName(),
            'label' => 'entity.Clocking.clockingUser',
            'multiple' => true,
            'expanded' => true,
            'query_builder' => static fn (UserRepository $repository): QueryBuilder => $repository
                ->createQueryBuilder('u')
                ->orderBy('u.lastName', 'ASC')
                ->addOrderBy('u.firstName', 'ASC'),
        ]);
        $builder->add('date', DateType::class, [
            'label' => 'entity.Clocking.date',
            'widget' => 'single_text',
        ]);
        $builder->add('entries', CollectionType::class, [
            'label' => 'entity.Clocking.entries',
            'entry_type' => ClockingEntryType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
            // Lignes laissées vides (ni chantier ni durée) : ignorées plutôt qu'en erreur.
            'delete_empty' => static fn (?ClockingEntryInput $entry = null): bool => $entry === null
                || ($entry->project === null && $entry->duration === null),
            'by_reference' => false,
            'prototype' => true,
            'error_bubbling' => false,
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'clocking.form.submit',
        ]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ClockingRequest::class,
            ],
        );
    }
}
