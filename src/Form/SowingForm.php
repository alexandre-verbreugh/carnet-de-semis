<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Plot;
use App\Entity\SeedLot;
use App\Entity\Sowing;
use App\Entity\Species;
use App\Enum\SowingMethod;
use App\Repository\PlotRepository;
use App\Repository\SeedLotRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SowingForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plot', EntityType::class, [
                'label' => 'Emplacement',
                'class' => Plot::class,
                'choice_label' => static fn (Plot $plot): string => \sprintf('%s — %s', $plot->getName(), $plot->getShortDescription()),
                'query_builder' => static fn (PlotRepository $repository) => $repository
                    ->createQueryBuilder('p')
                    ->andWhere('p.isArchived = false')
                    ->orderBy('p.name', 'ASC'),
                'placeholder' => 'Choisir un emplacement',
            ])
            ->add('species', EntityType::class, [
                'label' => 'Espèce',
                'class' => Species::class,
                'choice_label' => static fn (Species $espece): string => $espece->getFullName(),
                'query_builder' => static fn (EntityRepository $repository) => $repository
                    ->createQueryBuilder('e')
                    ->orderBy('e.name', 'ASC')
                    ->addOrderBy('e.variety', 'ASC'),
                'placeholder' => 'Choisir une espèce',
            ])
            ->add('sownAt', DateType::class, [
                'label' => 'Date de semis',
                'widget' => 'single_text',
                'data' => $builder->getData()?->getSownAt() ?? new \DateTimeImmutable('today'),
            ])
            ->add('seedCount', IntegerType::class, [
                'label' => 'Nombre de graines',
                'required' => false,
                'help' => 'Sans ce nombre, le taux de levée ne peut pas être calculé.',
                'attr' => ['min' => 1, 'max' => 10000],
            ])
            ->add('method', EnumType::class, [
                'label' => 'Méthode',
                'class' => SowingMethod::class,
                'choice_label' => static fn (SowingMethod $methode): string => $methode->label(),
            ])
            ->add('depthMm', IntegerType::class, [
                'label' => 'Profondeur (mm)',
                'required' => false,
                'help' => 'Pré-rempli depuis la fiche de l\'espèce, à ajuster si tu as fait autrement.',
                'attr' => ['min' => 0, 'max' => 150],
            ])
            ->add('seedLot', EntityType::class, [
                'label' => 'Sachet de graines',
                'class' => SeedLot::class,
                'required' => false,
                'placeholder' => 'Aucun sachet enregistré',
                'choice_label' => static fn (SeedLot $lot): string => (string) $lot,
                'query_builder' => static fn (SeedLotRepository $repository) => $repository
                    ->createQueryBuilder('l')
                    ->orderBy('l.id', 'DESC'),
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Ce que tu veux retenir de ce semis.'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Sowing::class]);
    }
}
