<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Hotel;
use App\Entity\Destination;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
            ])
            ->add('email', EmailType::class)

            // ─── ROLE SELECTION ─────────────────────────
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                ],
                'expanded' => true,   // checkboxes
                'multiple' => true,
                'label' => 'Account Role',
            ])

            // ─── PASSWORD ─────────────────────────────
            ->add('password', PasswordType::class, [
                'required' => !$isEdit,
                'label' => $isEdit
                    ? 'Password (leave blank to keep existing)'
                    : 'Password',
                'empty_data' => '',
                'mapped' => true,
            ])

            // ─── BOOKED HOTELS RELATION REMOVED TO FIX DOCTRINE MAPPING ISSUES ───
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
