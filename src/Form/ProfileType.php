<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ProfileType extends AbstractType
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
                        'message' => 'Please enter your full name'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Your name should be at least {{ limit }} characters',
                        'maxMessage' => 'Your name should not be more than {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                        'message' => 'Your name can only contain letters, spaces, hyphens and apostrophes'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your email address'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter your email address'
                    ]),
                    new Assert\Email([
                        'message' => 'Please enter a valid email address'
                    ])
                ]
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Bio',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Tell us about yourself...'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'Your bio should not be more than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your phone number'
                ],
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[+]?[\d\s\-\(\)]+$/',
                        'message' => 'Please enter a valid phone number'
                    ])
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'City, Country'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Location should not be more than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('website', TextType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://yourwebsite.com'
                ],
                'constraints' => [
                    new Assert\Url([
                        'message' => 'Please enter a valid URL'
                    ])
                ]
            ])
            ->add('profileImage', FileType::class, [
                'label' => 'Profile Image',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'The image should not be larger than 2MB',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF, or WebP)'
                    ])
                ]
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current Password',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter current password to confirm changes'
                ],
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter your current password to confirm changes',
                        'groups' => ['password_change']
                    ])
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'New Password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Enter new password'
                    ],
                    'required' => false
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Confirm new password'
                    ],
                    'required' => false
                ],
                'mapped' => false,
                'constraints' => [
                    new Assert\Length([
                        'min' => 8,
                        'max' => 4096,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'maxMessage' => 'Your password should not be more than {{ limit }} characters',
                        'groups' => ['password_change']
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                        'message' => 'Your password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character',
                        'groups' => ['password_change']
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default', 'password_change']
        ]);
    }
}
