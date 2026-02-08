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
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
            ])
            ->add('aboutMe', TextareaType::class, [
                'label' => 'About Me',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('facebookUsername', TextType::class, [
                'label' => 'Facebook Username',
                'required' => false,
            ])
            ->add('twitterUsername', TextType::class, [
                'label' => 'Twitter Username',
                'required' => false,
            ])
            ->add('instagramUsername', TextType::class, [
                'label' => 'Instagram Username',
                'required' => false,
            ])
            ->add('youtubeUrl', TextType::class, [
                'label' => 'YouTube URL',
                'required' => false,
            ])
            ->add('profilePicture', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG)',
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
