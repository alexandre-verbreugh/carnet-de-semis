<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creation du tout premier compte, a l'installation.
 *
 * Le formulaire n'est pas lie a l'entite User : le mot de passe en clair ne
 * doit exister que le temps du hachage, jamais sur un objet persiste.
 */
class FirstAccountForm extends AbstractType
{
    public const int LONGUEUR_MINIMALE = 12;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Identifiant',
                'help' => 'Un pseudonyme ou une adresse e-mail, au choix. Aucun message ne sera envoyé.',
                'attr' => ['autocomplete' => 'username', 'autocapitalize' => 'none', 'spellcheck' => 'false'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Choisis un identifiant.'),
                    new Assert\Length(min: 2, max: 180),
                    new Assert\Regex(
                        pattern: '/^[a-zA-Z0-9._@+-]+$/',
                        message: 'Lettres, chiffres et les caractères . _ - + @ uniquement.',
                    ),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'help' => \sprintf('%d caractères minimum. Cette instance est accessible depuis internet.', self::LONGUEUR_MINIMALE),
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirmation',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'constraints' => [
                    new Assert\NotBlank(message: 'Choisis un mot de passe.'),
                    new Assert\Length(
                        min: self::LONGUEUR_MINIMALE,
                        minMessage: 'Mot de passe trop court : {{ limit }} caractères minimum.',
                        // Au-dela, bcrypt ignore silencieusement les caracteres
                        // supplementaires : mieux vaut refuser que laisser croire
                        // a une securite qui n'existe pas.
                        max: 4096,
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
