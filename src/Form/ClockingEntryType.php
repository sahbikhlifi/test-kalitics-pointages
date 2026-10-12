<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Project;
use App\Form\Model\ClockingEntryInput;
use App\Repository\ProjectRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Une ligne (chantier, durée) du formulaire de pointage.
 */
class ClockingEntryType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('project', EntityType::class, [
            'class' => Project::class,
            'choice_label' => static fn (Project $choice): string => sprintf(
                '%s (%s → %s)',
                $choice->getName(),
                $choice->getDateStart()?->format('d/m/Y'),
                $choice->getDateEnd()?->format('d/m/Y') ?? '…',
            ),
            'label' => 'entity.ClockingEntry.project',
            'placeholder' => 'clocking.form.project_placeholder',
            'query_builder' => static fn (ProjectRepository $repository): QueryBuilder => $repository
                ->createQueryBuilder('p')
                ->orderBy('p.name', 'ASC'),
        ]);
        $builder->add('duration', IntegerType::class, [
            'label' => 'entity.ClockingEntry.duration',
            'attr' => [
                'min' => 1,
                'max' => 10,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ClockingEntryInput::class,
            ],
        );
    }
}
