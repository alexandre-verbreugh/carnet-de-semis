<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Observation;
use App\Entity\Plot;
use App\Entity\Sowing;
use App\Enum\ObservationType;
use App\Enum\SowingStatus;
use App\Repository\PlotRepository;
use App\Repository\SowingRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Saisie d'une observation, pensee pour etre remplie debout devant un bac.
 *
 * Tous les champs de mesure sont facultatifs : une observation utile peut se
 * limiter a « levée, 11 plants ». Exiger davantage garantirait que plus rien
 * ne soit saisi apres deux semaines.
 */
class ObservationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'label' => 'Type',
                'class' => ObservationType::class,
                'choice_label' => static fn (ObservationType $type): string => $type->label(),
            ])
            ->add('observedAt', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'data' => $builder->getData()?->getObservedAt() ?? new \DateTimeImmutable('today'),
            ])
            ->add('sowing', EntityType::class, [
                'label' => 'Semis concerné',
                'class' => Sowing::class,
                'required' => false,
                'placeholder' => 'Aucun — observation sur l\'emplacement',
                'choice_label' => static fn (Sowing $semis): string => \sprintf(
                    '%s — %s (semé le %s)',
                    $semis->getPlot()?->getName() ?? '?',
                    $semis->getSpecies()?->getFullName() ?? '?',
                    $semis->getSownAt()?->format('d/m/Y') ?? '?',
                ),
                'query_builder' => static fn (SowingRepository $repository) => $repository
                    ->createQueryBuilder('s')
                    ->andWhere('s.status NOT IN (:closed)')
                    ->setParameter('closed', [SowingStatus::Termine, SowingStatus::Echec])
                    ->orderBy('s.sownAt', 'DESC'),
            ])
            ->add('plot', EntityType::class, [
                'label' => 'Emplacement',
                'class' => Plot::class,
                'choice_label' => static fn (Plot $plot): string => $plot->getName(),
                'query_builder' => static fn (PlotRepository $repository) => $repository
                    ->createQueryBuilder('p')
                    ->andWhere('p.isArchived = false')
                    ->orderBy('p.name', 'ASC'),
                'help' => 'Renseigné automatiquement si un semis est choisi.',
                'required' => false,
            ])
            ->add('germinatedCount', IntegerType::class, [
                'label' => 'Nombre de plants',
                'required' => false,
                'help' => 'Pour une levée : combien sont sortis. Pour une perte : combien il en reste.',
                'attr' => ['min' => 0, 'max' => 10000],
            ])
            ->add('heightCm', IntegerType::class, [
                'label' => 'Hauteur (cm)',
                'required' => false,
                'attr' => ['min' => 0, 'max' => 1000],
            ])
            ->add('leafCount', IntegerType::class, [
                'label' => 'Nombre de feuilles',
                'required' => false,
                'attr' => ['min' => 0, 'max' => 1000],
            ])
            ->add('harvestGrams', IntegerType::class, [
                'label' => 'Récolte (grammes)',
                'required' => false,
                'attr' => ['min' => 0, 'max' => 1000000],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Ce que tu vois, même approximatif.'],
            ]);

        // L'emplacement se deduit du semis quand il n'a pas ete choisi.
        //
        // La priorite elevee est indispensable : le validateur ecoute le meme
        // evenement a la priorite 0, et l'emplacement etant obligatoire, sans
        // cela toute observation rattachee a un semis serait refusee.
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $observation = $event->getData();

            if ($observation instanceof Observation && null === $observation->getPlot()) {
                $observation->setPlot($observation->getSowing()?->getPlot());
            }
        }, 100);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Observation::class]);
    }
}
