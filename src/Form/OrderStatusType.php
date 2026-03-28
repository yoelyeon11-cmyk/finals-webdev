<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Order Status',
                'choices' => [
                    'New Order' => 'new_order',
                    'Preparing Order' => 'preparing',
                    'Ready to Ship' => 'ready_to_ship',
                    'Shipping' => 'shipping',
                    'Delivered/Completed' => 'delivered',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('shippingCarrier', TextType::class, [
                'label' => 'Shipping Carrier',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., FedEx, UPS, USPS'
                ],
            ])
            ->add('trackingNumber', TextType::class, [
                'label' => 'Tracking Number',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter tracking number'
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
