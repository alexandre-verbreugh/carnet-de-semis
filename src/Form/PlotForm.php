<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Plot;
use App\Enum\Exposure;
use App\Enum\PlotType;
use App\Enum\Shelter;
use App\Enum\SubstrateComponent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Nomme PlotForm et non PlotType : le nom est deja pris par l'enum decrivant
 * la nature de l'emplacement.
 */
class PlotForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'help' => 'Ce que tu dis pour en parler : « bac du muret », « grande jardinière ».',
                'attr' => ['placeholder' => 'Jardinière du balcon'],
            ])
            // Affiche en boutons radio et non en liste deroulante : c'est le
            // seul moyen de montrer l'explication de chaque type, et « butte »
            // ou « lasagne » ne parlent pas a tout le monde.
            ->add('type', EnumType::class, [
                'label' => 'Type',
                'class' => PlotType::class,
                'expanded' => true,
                'label_html' => true,
                'choice_label' => static fn (PlotType $type): string => \sprintf(
                    '<span class="choice__title">%s</span><span class="choice__hint">%s</span>',
                    htmlspecialchars($type->label(), \ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($type->description(), \ENT_QUOTES, 'UTF-8'),
                ),
            ])
            ->add('shelter', EnumType::class, [
                'label' => 'Abri',
                'class' => Shelter::class,
                'choice_label' => static fn (Shelter $abri): string => $abri->label(),
                'help' => 'Sous serre ou sous châssis, les relevés de pluie ne disent rien de ce que reçoit le semis.',
            ])
            ->add('location', TextType::class, [
                'label' => 'Emplacement',
                'required' => false,
                'attr' => ['placeholder' => 'Balcon sud, fond du jardin…'],
            ])
            ->add('exposure', EnumType::class, [
                'label' => 'Exposition',
                'class' => Exposure::class,
                'required' => false,
                'placeholder' => 'Non précisée',
                'choice_label' => static fn (Exposure $exposition): string => $exposition->label(),
            ])
            ->add('lengthCm', IntegerType::class, [
                'label' => 'Longueur (cm)',
                'required' => false,
            ])
            ->add('widthCm', IntegerType::class, [
                'label' => 'Largeur (cm)',
                'required' => false,
            ])
            ->add('depthCm', IntegerType::class, [
                'label' => 'Profondeur (cm)',
                'required' => false,
                'help' => 'Pour un contenant seulement. Sert à calculer le volume de substrat.',
            ])
            ->add('substrateComponents', EnumType::class, [
                'label' => 'Composition du substrat',
                'class' => SubstrateComponent::class,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (SubstrateComponent $composant): string => $composant->label(),
                'help' => 'Tout ce qui compose le remplissage : un substrat est presque toujours un mélange.',
            ])
            ->add('topLayer', EnumType::class, [
                'label' => 'Couche de surface',
                'class' => SubstrateComponent::class,
                'required' => false,
                'placeholder' => 'Non précisée',
                'choice_label' => static fn (SubstrateComponent $composant): string => $composant->label(),
                'help' => 'Ce qui recouvre la graine. C\'est le facteur qui pèse le plus sur la levée, et la seule base de comparaison honnête entre emplacements.',
            ])
            ->add('substrateNote', TextareaType::class, [
                'label' => 'Notes sur le substrat',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Proportions, provenance, amendements…'],
            ])
            ->add('hasDrainage', CheckboxType::class, [
                'label' => 'Drainage en fond (trous, billes d\'argile)',
                'required' => false,
            ])
            ->add('filledAt', DateType::class, [
                'label' => 'Rempli le',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('isArchived', CheckboxType::class, [
                'label' => 'Retiré du suivi',
                'required' => false,
                'help' => 'Reste consultable, mais n\'accepte plus de nouveau semis.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Plot::class]);
    }
}
