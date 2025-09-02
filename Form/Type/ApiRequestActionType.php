<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiRequestActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'Url',
                'required' => true,
            ])
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'POST'  => 'POST',
                    'PUT'   => 'PUT',
                    'PATCH' => 'PATCH',
                ],
                'label' => 'HTTP Method',
                'required' => true,
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Request Body',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'placeholder' => '{contactfield=firstname}, {contactfield=email}, ...'
                ]
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('integration');
    }
}
