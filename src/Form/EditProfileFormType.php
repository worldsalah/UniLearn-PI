<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EditProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your full name'
                ],
                'constraints' => [
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Your full name must be at least {{ limit }} characters long.',
                        'max' => 100,
                        'maxMessage' => 'Your full name cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s]+$/',
                        'message' => 'Full name can only contain letters and spaces.'
                    ])
                ]
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your first name'
                ],
                'constraints' => [
                    new Assert\Length([
                        'min' => 2,
                        'minMessage' => 'Your first name must be at least {{ limit }} characters long.',
                        'max' => 50,
                        'maxMessage' => 'Your first name cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z]+$/',
                        'message' => 'First name can only contain letters.'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your last name'
                ],
                'constraints' => [
                    new Assert\Length([
                        'min' => 2,
                        'minMessage' => 'Your last name must be at least {{ limit }} characters long.',
                        'max' => 50,
                        'maxMessage' => 'Your last name cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s-]+$/',
                        'message' => 'Your last name should only contain letters, spaces, and hyphens.'
                    ])
                ]
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your username'
                ],
                'constraints' => [
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
                        'message' => 'Please enter your email.'
                    ]),
                    new Assert\Email([
                        'message' => 'The email "{{ value }}" is not a valid email format.'
                    ]),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Your email cannot be longer than {{ limit }} characters.'
                    ])
                ],
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
                        'pattern' => '/^[\+]?[0-9\s\-\(\)]+$/',
                        'message' => 'Please enter a valid phone number.'
                    ]),
                    new Assert\Length([
                        'max' => 20,
                        'maxMessage' => 'Phone number cannot be longer than {{ limit }} characters.'
                    ])
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your location'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Location cannot be longer than {{ limit }} characters.'
                    ])
                ]
            ])
            ->add('aboutMe', TextareaType::class, [
                'label' => 'About Me',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'Tell us about yourself'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'About me cannot be longer than {{ limit }} characters.'
                    ])
                ]
            ])
            ->add('facebookUsername', TextType::class, [
                'label' => 'Facebook Username',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your Facebook username'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Facebook username cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9.]+$/',
                        'message' => 'Facebook username can only contain letters, numbers, and periods.'
                    ])
                ]
            ])
            ->add('twitterUsername', TextType::class, [
                'label' => 'Twitter Username',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your Twitter username'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 15,
                        'maxMessage' => 'Twitter username cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9_]+$/',
                        'message' => 'Twitter username can only contain letters, numbers, and underscores.'
                    ])
                ]
            ])
            ->add('instagramUsername', TextType::class, [
                'label' => 'Instagram Username',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your Instagram username'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 30,
                        'maxMessage' => 'Instagram username cannot be longer than {{ limit }} characters.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9_.]+$/',
                        'message' => 'Instagram username can only contain letters, numbers, underscores, and periods.'
                    ])
                ]
            ])
            ->add('youtubeUrl', TextType::class, [
                'label' => 'YouTube URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your YouTube channel URL'
                ],
                'constraints' => [
                    new Assert\Url([
                        'message' => 'Please enter a valid YouTube URL.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^https?:\/\/(www\.)?youtube\.com\/.*/',
                        'message' => 'Please enter a valid YouTube URL.'
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'YouTube URL cannot be longer than {{ limit }} characters.'
                    ])
                ]
            ])
            ->add('profilePicture', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF)',
                        'maxSizeMessage' => 'File size should not exceed 2MB'
                    ])
                ],
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
