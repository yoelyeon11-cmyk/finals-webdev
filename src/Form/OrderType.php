<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Customer Name',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Customer Email',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('customerPhone', TelType::class, [
                'label' => 'Contact Number',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('itemsDescription', TextareaType::class, [
                'label' => 'Items/Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Brief summary of items (e.g., Custom Dragon Slayer Armor)'
                ],
            ])
            ->add('totalAmount', MoneyType::class, [
                'label' => 'Total Amount',
                'currency' => 'USD',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Cash' => 'cash',
                    'Invoice' => 'invoice',
                    'Credit Card' => 'credit_card',
                    'PayPal' => 'paypal',
                    'Bank Transfer' => 'bank_transfer',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Shipping Address',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Full shipping address'
                ],
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
