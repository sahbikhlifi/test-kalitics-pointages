<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Project;
use App\Entity\User;
use App\Form\Model\ClockingFilter;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filtres de la liste des pointages, soumis en GET avec des paramètres courts (?user=…&project=…&from=…&to=…).
 */
class ClockingFilterType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('user', EntityType::class, [
            'class' => User::class,
            'choice_label' => static fn (User $choice): string => $choice->getFullName(),
            'label' => 'entity.Clocking.clockingUser',
            'placeholder' => 'clocking.filter.all_users',
            'required' => false,
            'query_builder' => static fn (UserRepository $repository): QueryBuilder => $repository
                ->createQueryBuilder('u')
                ->orderBy('u.lastName', 'ASC')
                ->addOrderBy('u.firstName', 'ASC'),
        ]);
        $builder->add('project', EntityType::class, [
            'class' => Project::class,
            'choice_label' => 'name',
            'label' => 'entity.ClockingEntry.project',
            'placeholder' => 'clocking.filter.all_projects',
            'required' => false,
            'query_builder' => static fn (ProjectRepository $repository): QueryBuilder => $repository
                ->createQueryBuilder('p')
                ->orderBy('p.name', 'ASC'),
        ]);
        $builder->add('from', DateType::class, [
            'label' => 'clocking.filter.from',
            'widget' => 'single_text',
            'required' => false,
        ]);
        $builder->add('to', DateType::class, [
            'label' => 'clocking.filter.to',
            'widget' => 'single_text',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ClockingFilter::class,
                'method' => 'GET',
                'csrf_protection' => false,
                // ?page=… cohabite avec les filtres dans l'URL.
                'allow_extra_fields' => true,
            ],
        );
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
