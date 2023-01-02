<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateUserNotificationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('eventNotification', CheckboxType::class)
            ->add('groupAddNotification', CheckboxType::class)
            ->add('groupRemoveNotification', CheckboxType::class)
        ;
    }
}
