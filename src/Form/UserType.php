<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Hotel;
use App\Entity\Destination;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
            ])
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class, [
                'required' => false,
                'label' => 'Password (leave blank to keep existing)',
            ])
            ->add('bookedHotels', EntityType::class, [
                'class' => Hotel::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true, // checkboxes
                'required' => false,
                'label' => 'Booked Hotels',
            ])
            ->add('favoriteDestinations', EntityType::class, [
                'class' => Destination::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true, // checkboxes
                'required' => false,
                'label' => 'Favorite Destinations',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
