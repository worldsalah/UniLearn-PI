<?php

namespace App\Form\Form;

use App\Entity\Job;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'offre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le titre de l\'offre d\'emploi'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Décrivez l\'offre d\'emploi en détail...',
                    'rows' => 6
                ]
            ])
            ->add('budget', MoneyType::class, [
                'label' => 'Budget (€)',
                'currency' => 'EUR',
                'divisor' => 100,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouvert' => 'open',
                    'Attribué' => 'assigned',
                    'Terminé' => 'completed',
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ex: Remote, Paris, France'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'Temps plein' => 'full-time',
                    'Temps partiel' => 'part-time',
                    'Contrat' => 'contract',
                    'Freelance' => 'freelance',
                    'Stage' => 'internship',
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('experienceLevel', ChoiceType::class, [
                'label' => 'Niveau d\'expérience',
                'choices' => [
                    'Débutant' => 'entry',
                    'Intermédiaire' => 'mid',
                    'Senior' => 'senior',
                    'Expert' => 'expert',
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('duration', TextType::class, [
                'label' => 'Durée',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ex: 3 mois, 6 semaines'
                ]
            ])
            ->add('requirements', TextareaType::class, [
                'label' => 'Exigences',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Listez les compétences et qualifications spécifiques...',
                    'rows' => 4
                ]
            ])
            ->add('skills', TextType::class, [
                'label' => 'Compétences requises',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ex: PHP, JavaScript, Gestion de projet'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Job::class,
        ]);
    }
}
