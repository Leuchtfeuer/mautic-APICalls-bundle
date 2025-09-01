<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeatureSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {}


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('integration');
    }
}
