<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your full name'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter your full name.'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Your full name must be at least {{ limit }} characters long.',
                        'max' => 100,
                        'maxMessage' => 'Your full name cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s]+$/',
                        'message' => 'Your full name should only contain letters and spaces.'
                    ])
                ]
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your username'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter a username.'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Your username must be at least {{ limit }} characters long.',
                        'max' => 50,
                        'maxMessage' => 'Your username cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9_]+$/',
                        'message' => 'Username can only contain letters, numbers, and underscores.'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your email'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter an email.'
                    ]),
                    new Assert\Email([
                        'message' => 'The email "{{ value }}" is not a valid email format.'
                    ]),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Your email cannot be longer than {{ limit }} characters.'
                    ])
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Enter your password'
                    ],
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Please enter a password.'
                        ]),
                        new Assert\Length([
                            'min' => 8,
                            'minMessage' => 'Your password must be at least {{ limit }} characters long.',
                            'max' => 4096,
                            'maxMessage' => 'Your password cannot be longer than {{ limit }} characters.'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                            'message' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
                        ])
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Confirm your password'
                    ]
                ],
                'invalid_message' => 'The password fields must match.',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'constraints' => [
                    new Assert\IsTrue([
                        'message' => 'You must agree to our terms and conditions.',
                    ]),
                    new Assert\NotBlank([
                        'message' => 'You must agree to our terms and conditions.'
                    ])
                ],
                'label' => 'I agree to the <a href="#" class="text-primary">Terms and Conditions</a> and <a href="#" class="text-primary">Privacy Policy</a>',
                'label_html' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
