<?php

namespace App\Form;

use App\Entity\Inventory;
use App\Entity\Products;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Products::class,
                'choice_label' => 'name',  // shows product name in select box
                'placeholder' => 'Select a product',
                'required' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Enter quantity to add',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inventory::class,
        ]);
    }
}
