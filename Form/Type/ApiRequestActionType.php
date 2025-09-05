<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type;

use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ApiRequestActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'leuchtfeuer.api.url.label',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'leuchtfeuer.api.url.required']),
                    new Assert\Url(['message' => 'leuchtfeuer.api.url.invalid']),
                ],
                'attr' => [
                    'placeholder' => 'leuchtfeuer.api.url.placeholder'
                ],
            ])
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'POST'  => 'POST',
                    'PUT'   => 'PUT',
                    'PATCH' => 'PATCH',
                ],
                'label' => 'leuchtfeuer.api.method.label',
                'required' => true,
            ])
            ->add('body', TextareaType::class, [
                'label' => 'leuchtfeuer.api.body.label',
                'required' => true,
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'leuchtfeuer.api.body.placeholder'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'leuchtfeuer.api.body.required']),
                ]
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('integration');
    }
}
