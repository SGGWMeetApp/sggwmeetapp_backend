<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PrivateEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('publicEventId', IntegerType::class)
            ->add('name', TextType::class)
            ->add('locationId', IntegerType::class)
            ->add('description', TextType::class)
            ->add('startDate', JsonDateTimeType::class)
        ;
    }

}