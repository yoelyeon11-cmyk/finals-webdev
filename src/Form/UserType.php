<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'required' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'required' => !$options['is_edit'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                        'groups' => $options['is_edit'] ? [] : ['Default'],
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
                'help' => $options['is_edit'] ? 'Leave blank to keep current password' : null,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Staff' => 'ROLE_STAFF',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
        ;

        $builder->get('roles')->addModelTransformer(new CallbackTransformer(
            function (?array $storedRoles): string {
                $storedRoles = $storedRoles ?? [];
                if (\in_array('ROLE_ADMIN', $storedRoles, true)) {
                    return 'ROLE_ADMIN';
                }
                if (\in_array('ROLE_STAFF', $storedRoles, true)) {
                    return 'ROLE_STAFF';
                }

                return 'ROLE_STAFF';
            },
            function (?string $selected): array {
                return match ($selected) {
                    'ROLE_ADMIN', 'ROLE_STAFF' => [$selected],
                    default => ['ROLE_STAFF'],
                };
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
