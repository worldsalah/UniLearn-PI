<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnrollmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // No form fields needed - the enrollment will be created in controller
        // The course ID will be passed via URL parameter
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'enrollment-form',
                'class' => 'enrollment-form',
            ],
        ]);
    }
}
