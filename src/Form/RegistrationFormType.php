<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\CallbackTransformer;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => ['placeholder' => 'Enter your first name'],
                'constraints' => [new NotBlank()],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['placeholder' => 'Enter your last name'],
                'constraints' => [new NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'Enter your email'],
                'constraints' => [new NotBlank()],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false, // important — we hash it manually
                'label' => 'Password',
                'attr' => ['placeholder' => 'Enter your password'],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 6]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Register as',
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => false,
            ]);

            $builder->get('roles')
        ->addModelTransformer(new CallbackTransformer(
        function ($rolesArray) {
            // Transform array to a single string (for the form)
            return count($rolesArray) ? $rolesArray[0] : null;
        },
        function ($roleString) {
            // Transform string back to array (for the entity)
            return [$roleString];
        }
    ));


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
