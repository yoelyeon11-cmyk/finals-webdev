<?php

namespace App\Form;

use App\Entity\CustomCosplayRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomCosplayRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Customer Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter customer name'],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Customer Email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'customer@example.com'],
            ])
            ->add('customerPhone', TelType::class, [
                'label' => 'Contact Number',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '+1234567890'],
            ])
            ->add('cosplayCharacter', TextType::class, [
                'label' => 'Character/Subject',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g., Cloud Strife from Final Fantasy VII'],
            ])
            ->add('designNotes', TextareaType::class, [
                'label' => 'Design & Material Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Detailed description of materials, design elements, accessories, etc.'
                ],
            ])
            ->add('bust', NumberType::class, [
                'label' => 'Bust (inches)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => '36.00'],
            ])
            ->add('waist', NumberType::class, [
                'label' => 'Waist (inches)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => '28.00'],
            ])
            ->add('hip', NumberType::class, [
                'label' => 'Hip (inches)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => '38.00'],
            ])
            ->add('shoulderWidth', NumberType::class, [
                'label' => 'Shoulder Width (inches)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => '16.00'],
            ])
            ->add('inseam', NumberType::class, [
                'label' => 'Inseam (inches)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => '30.00'],
            ])
            ->add('height', NumberType::class, [
                'label' => 'Height (inches)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => '68.00'],
            ])
            ->add('customMeasurements', TextareaType::class, [
                'label' => 'Additional Custom Measurements',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Any additional measurements for accessories, props, etc.'
                ],
            ])
            ->add('estimatedCost', MoneyType::class, [
                'label' => 'Estimated Cost/Quote',
                'required' => false,
                'currency' => 'USD',
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'New Request' => 'new_request',
                    'Quote Sent' => 'quote_sent',
                    'Awaiting Customer Approval' => 'awaiting_approval',
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                ],
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomCosplayRequest::class,
        ]);
    }
}
