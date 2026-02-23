<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your email',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'adresse email est obligatoire.',
                    ]),
                    new Email([
                        'message' => 'L\'email "{{ value }}" n\'est pas une adresse email valide.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'L\'email ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your password',
                    'autocomplete' => 'current-password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le mot de passe est obligatoire.',
                    ]),
                    new Length([
                        'min' => 1,
                        'minMessage' => 'Le mot de passe ne peut pas être vide.',
                        'max' => 4096,
                        'maxMessage' => 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Disable CSRF protection for login form as it's handled by Symfony's security system
            'csrf_protection' => false,
        ]);
    }
}
