<?php

namespace App\Form;

use App\Constraint\UserPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'first_options' => [
                    'constraints' => [
                        new UserPassword()
                    ],
                    'label' => 'Password'
                ],
                'second_options' => [
                    'label' => 'Repeat Password'
                ],
                'invalid_message' => 'The password fields must match.'
            ])
        ;
    }
}
