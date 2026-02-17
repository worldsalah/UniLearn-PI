<?php

namespace App\Form;

use App\Entity\QuizSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuizSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('points', NumberType::class, [
                'label' => 'Points',
                'attr' => [
                    'class' => 'form-control',
                    'value' => 1
                ]
            ])
            ->add('timeLimit', NumberType::class, [
                'label' => 'Limite de temps (minutes)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Optionnel'
                ]
            ])
            ->add('passingScore', NumberType::class, [
                'label' => 'Score de réussite (%)',
                'attr' => [
                    'class' => 'form-control',
                    'value' => 70
                ]
            ])
            ->add('maxAttempts', NumberType::class, [
                'label' => 'Nombre maximum de tentatives',
                'attr' => [
                    'class' => 'form-control',
                    'value' => 3
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer les paramètres',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuizSettings::class,
        ]);
    }
}
