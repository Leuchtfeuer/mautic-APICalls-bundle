<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
            ->add('contentType', ChoiceType::class, [
                'choices' => [
                    'JSON' => 'application/json',
                    'XML' => 'application/xml',
                    'Text XML' => 'text/xml',
                    'SOAP XML' => 'application/soap+xml',
                    'JSON:API' => 'application/vnd.api+json',
                    'Form URL Encoded' => 'application/x-www-form-urlencoded',
                ],
                'label' => 'leuchtfeuer.api.content_type.label',
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
                    new Assert\Callback([$this, 'validateBodyByContentType']),
                ]
            ]);
    }

    public function validateBodyByContentType(string $body, ExecutionContextInterface $context): void
    {
        // @phpstan-ignore-next-line
        $data = $context->getRoot()->getData();
        $contentType = $data['properties']['contentType'] ?? null;

        if (in_array($contentType, ['application/json', 'application/vnd.api+json'])) {
            $validator = $context->getValidator();
            $violations = $validator->validate($body, new Assert\Json(['message' =>
                'leuchtfeuer.api.body.invalid_json']));

            foreach ($violations as $violation) {
                $context->buildViolation($violation->getMessage())
                    ->addViolation();
            }
        }
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('integration');
    }
}
