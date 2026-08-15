<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creation de compte.
 *
 * Deux pieges a robots, sans captcha ni service tiers : ni l'un ni l'autre ne
 * demande le moindre effort a un visiteur, et aucun ne fait sortir de donnee
 * du serveur. C'est le seul type de protection compatible avec la promesse du
 * projet : aucune requete vers un tiers, et rien qui exige JavaScript.
 *
 * Ils arretent les robots qui remplissent aveuglement tous les formulaires
 * rencontres, c'est-a-dire l'immense majorite. Un robot ecrit specifiquement
 * pour cette application les contournerait ; a ce niveau d'acharnement, seule
 * la fermeture des inscriptions protege vraiment.
 */
class RegistrationForm extends AbstractType
{
    public const int LONGUEUR_MINIMALE = 12;

    /**
     * Nom du champ leurre.
     *
     * Volontairement credible : un robot qui inspecte les noms de champs
     * remplira « site_web » sans hesiter, alors qu'aucun humain ne le verra.
     */
    public const string CHAMP_LEURRE = 'siteWeb';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Identifiant',
                'help' => 'Un pseudonyme ou une adresse e-mail, au choix. Aucun message ne sera envoyé.',
                'attr' => [
                    'autocomplete' => 'username',
                    'autocapitalize' => 'none',
                    'spellcheck' => 'false',
                ],
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
                    'help' => \sprintf(
                        '%d caractères minimum. Note-le : sans adresse e-mail, il ne peut pas être réinitialisé tout seul.',
                        self::LONGUEUR_MINIMALE,
                    ),
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
                        max: 4096,
                        minMessage: 'Mot de passe trop court : {{ limit }} caractères minimum.',
                    ),
                ],
            ])
            // Champ leurre : masque par le CSS, retire du parcours au clavier
            // et des lecteurs d'ecran. Rempli, c'est un robot.
            ->add(self::CHAMP_LEURRE, TextType::class, [
                'label' => 'Ne pas remplir ce champ',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'tabindex' => '-1',
                    'aria-hidden' => 'true',
                ],
                'row_attr' => ['class' => 'piege'],
            ])
            // Horodatage de l'affichage du formulaire, signe pour ne pas etre
            // rejoue : un envoi en moins de trois secondes n'a pas ete saisi.
            ->add('affichage', HiddenType::class, [
                'mapped' => false,
                'data' => $options['jeton_temps'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
        $resolver->setRequired('jeton_temps');
        $resolver->setAllowedTypes('jeton_temps', 'string');
    }
}
