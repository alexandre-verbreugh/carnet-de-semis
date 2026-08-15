<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Species;
use App\Enum\Exposure;
use App\Enum\SpeciesCategory;
use App\Enum\WaterNeed;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpeciesType extends AbstractType
{
    private const array MOIS = [
        'Janvier' => 1, 'Février' => 2, 'Mars' => 3, 'Avril' => 4,
        'Mai' => 5, 'Juin' => 6, 'Juillet' => 7, 'Août' => 8,
        'Septembre' => 9, 'Octobre' => 10, 'Novembre' => 11, 'Décembre' => 12,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Radis'],
            ])
            ->add('variety', TextType::class, [
                'label' => 'Variété',
                'required' => false,
                'attr' => ['placeholder' => '18 jours'],
            ])
            ->add('family', TextType::class, [
                'label' => 'Famille botanique',
                'required' => false,
                'help' => 'Sert à ne pas répéter la même famille deux saisons de suite dans un bac.',
                'attr' => ['placeholder' => 'Brassicacées'],
            ])
            ->add('category', EnumType::class, [
                'label' => 'Catégorie',
                'class' => SpeciesCategory::class,
                'choice_label' => static fn (SpeciesCategory $categorie): string => $categorie->label(),
            ])
            ->add('sowingDepthMm', IntegerType::class, [
                'label' => 'Profondeur de semis (mm)',
                'required' => false,
            ])
            ->add('spacingCm', IntegerType::class, [
                'label' => 'Espacement (cm)',
                'required' => false,
            ])
            ->add('sowingMonths', ChoiceType::class, [
                'label' => 'Mois de semis',
                'choices' => self::MOIS,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('germinationDaysMin', IntegerType::class, [
                'label' => 'Levée, au plus tôt (jours)',
                'required' => false,
            ])
            ->add('germinationDaysMax', IntegerType::class, [
                'label' => 'Levée, au plus tard (jours)',
                'required' => false,
                'help' => 'Ces deux valeurs déterminent la fenêtre de levée attendue sur le tableau de bord.',
            ])
            ->add('harvestDaysMin', IntegerType::class, [
                'label' => 'Récolte, au plus tôt (jours)',
                'required' => false,
            ])
            ->add('harvestDaysMax', IntegerType::class, [
                'label' => 'Récolte, au plus tard (jours)',
                'required' => false,
            ])
            ->add('germinationTempMinC', IntegerType::class, [
                'label' => 'Température minimale de germination (°C)',
                'required' => false,
            ])
            ->add('exposure', EnumType::class, [
                'label' => 'Exposition',
                'class' => Exposure::class,
                'required' => false,
                'placeholder' => 'Non précisée',
                'choice_label' => static fn (Exposure $exposition): string => $exposition->label(),
            ])
            ->add('waterNeed', EnumType::class, [
                'label' => 'Besoin en eau',
                'class' => WaterNeed::class,
                'required' => false,
                'placeholder' => 'Non précisé',
                'choice_label' => static fn (WaterNeed $besoin): string => $besoin->label(),
            ])
            ->add('directSow', CheckboxType::class, [
                'label' => 'Se sème directement en place',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Species::class]);
    }
}
