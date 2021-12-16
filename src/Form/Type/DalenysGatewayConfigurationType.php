<?php

namespace Onatera\PayumDalenysPlugin\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class DalenysGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('identifier', TextType::class);
        $builder->add('password', TextType::class);
    }
}
