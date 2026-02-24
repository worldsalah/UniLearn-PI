<?php

namespace App\Form;

use App\Entity\Checkout;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Jean',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Dupont',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'jean.dupont@example.com',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+33 1 23 45 67 89',
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '123 Rue Principale',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Paris',
                ],
            ])
            ->add('state', TextType::class, [
                'label' => 'État/Province',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Île-de-France',
                ],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '75001',
                ],
            ])
            ->add('country', ChoiceType::class, [
                'label' => 'Pays',
                'choices' => [
                    'France' => 'France',
                    'Canada' => 'Canada',
                    'Royaume-Uni' => 'United Kingdom',
                    'Australie' => 'Australia',
                    'États-Unis' => 'United States',
                    'Allemagne' => 'Germany',
                    'Espagne' => 'Spain',
                    'Italie' => 'Italy',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('cardNumber', TextType::class, [
                'label' => 'Numéro de carte',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '1234 5678 9012 3456',
                ],
            ])
            ->add('expiryDate', TextType::class, [
                'label' => 'Date d\'expiration',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'MM/AA',
                ],
            ])
            ->add('cvv', TextType::class, [
                'label' => 'CVV',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '123',
                ],
            ])
            ->add('cardholderName', TextType::class, [
                'label' => 'Nom du titulaire de la carte',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Jean Dupont',
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Méthode de paiement',
                'choices' => [
                    'Carte de crédit' => 'credit_card',
                    'PayPal' => 'paypal',
                    'Apple Pay' => 'apple_pay',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions générales et la politique de confidentialité',
                'required' => false,
            ])
            ->add('totalAmount', TextType::class, [
                'label' => 'Montant total',
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true,
                    'value' => $options['total_amount'] ?? '0.00',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Passer la commande',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg w-100',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Checkout::class,
            'total_amount' => '0.00',
        ]);
    }
}
