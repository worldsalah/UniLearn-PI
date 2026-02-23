<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Course;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Constraints\Image as ImageConstraint;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Step 1: Basic Information
            ->add('title', TextType::class, [
                'label' => 'Course title',
                'attr' => [
                    'placeholder' => 'Enter course title',
                    'class' => 'form-control',
                ],
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Short description',
                'attr' => [
                    'placeholder' => 'Enter keywords',
                    'rows' => 2,
                    'class' => 'form-control',
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => 'Course category',
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select category',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Course level',
                'choices' => [
                    'All level' => 'All level',
                    'Beginner' => 'Beginner',
                    'Intermediate' => 'Intermediate',
                    'Advance' => 'Advance',
                ],
                'placeholder' => 'Select course level',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Course price',
                'attr' => [
                    'placeholder' => 'Enter course price',
                    'class' => 'form-control',
                ],
            ])

            // Step 2: Media - These are virtual properties for file upload
            ->add('thumbnailFile', FileType::class, [
                'label' => 'Course thumbnail image',
                'required' => false,
                'mapped' => false, // This is a virtual field, not mapped to entity
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new ImageConstraint([
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser {{ limit }}.',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG ou GIF).',
                    ]),
                ],
            ])
            ->add('videoFile', FileType::class, [
                'label' => 'Course introduction video',
                'required' => false,
                'mapped' => false, // This is a virtual field, not mapped to entity
                'attr' => [
                    'accept' => 'video/mp4,video/webm,video/ogg',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new FileConstraint([
                        'maxSize' => '500M',
                        'maxSizeMessage' => 'La vidéo ne doit pas dépasser {{ limit }}.',
                        'mimeTypes' => ['video/mp4', 'video/webm', 'video/ogg'],
                        'mimeTypesMessage' => 'Veuillez télécharger une vidéo valide (MP4, WebM ou OGG).',
                    ]),
                ],
            ])

            // Step 3: Additional Information
            ->add('language', ChoiceType::class, [
                'label' => 'Course language',
                'choices' => [
                    'English' => 'en',
                    'Spanish' => 'es',
                    'French' => 'fr',
                    'German' => 'de',
                    'Italian' => 'it',
                    'Portuguese' => 'pt',
                    'Chinese' => 'zh',
                    'Japanese' => 'ja',
                ],
                'placeholder' => 'Select language',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('duration', NumberType::class, [
                'label' => 'Estimated duration (hours)',
                'attr' => [
                    'placeholder' => 'e.g., 10',
                    'class' => 'form-control',
                ],
            ])
            ->add('requirements', TextareaType::class, [
                'label' => 'Requirements',
                'required' => false,
                'attr' => [
                    'placeholder' => 'What are the prerequisites for this course?',
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
            ->add('learningOutcomes', TextareaType::class, [
                'label' => 'Learning outcomes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'What will students learn after completing this course?',
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
            ->add('targetAudience', TextareaType::class, [
                'label' => 'Target audience',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Who is this course for?',
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
