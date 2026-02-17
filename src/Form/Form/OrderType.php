<?php

namespace App\Form\Form;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('buyer', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Client',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'title',
                'label' => 'Produit/Service',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('totalPrice', MoneyType::class, [
                'currency' => 'EUR',
                'divisor' => 100,
                'label' => 'Montant total (€)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'pending',
                    'Payé' => 'paid',
                    'Terminé' => 'completed',
                    'Annulé' => 'cancelled',
                ],
                'label' => 'Statut de la commande',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'Note (1-5)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 5,
                    'placeholder' => '1 à 5'
                ]
            ])
            ->add('review', TextareaType::class, [
                'label' => 'Avis',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ajoutez votre avis...',
                    'rows' => 4
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
