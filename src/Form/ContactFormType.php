<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Your name',
                'constraints' => [
                    new NotBlank(message: 'Please enter your name.'),
                    new Length(max: 120),
                ],
                'attr' => ['maxlength' => 120, 'autocomplete' => 'name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'Please enter your email.'),
                    new Email(message: 'Please enter a valid email address.'),
                ],
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'required' => false,
                'constraints' => [
                    new Length(max: 180),
                ],
                'attr' => ['maxlength' => 180],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [
                    new NotBlank(message: 'Please enter a message.'),
                    new Length(
                        min: 4,
                        max: 5000,
                        minMessage: 'Please enter at least {{ limit }} characters in your message.',
                    ),
                ],
                'attr' => ['rows' => 6],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
