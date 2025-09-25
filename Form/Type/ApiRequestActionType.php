<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\ApiCallsPreSubmitFormListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ApiRequestActionType extends AbstractType
{

    public function __construct(private FieldModel $fieldModel){}
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {

        $builder
            ->add('url', TextType::class, [
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.url.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'leuchtfeuer.mautic-apicalls-bundle.url.required']),
                    new Assert\Callback([$this, 'validateUrlAllowingPlaceholders']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'leuchtfeuer.mautic-apicalls-bundle.url.placeholder'
                ],
            ])
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'GET'  =>  'GET',
                    'POST'  => 'POST',
                    'PUT'   => 'PUT',
                    'PATCH' => 'PATCH',
                ],
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.method.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'leuchtfeuer.mautic-apicalls-bundle.method.tooltip'
                ],
            ])
            ->add('url_parameters', TextType::class, [
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.url.parameters.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'leuchtfeuer.mautic-apicalls-bundle.url.parameters.placeholder',
                    'tooltip' => 'leuchtfeuer.mautic-apicalls-bundle.url.parameters.tooltip'
                ],
                'constraints' => [
                    new Assert\Callback([$this, 'validateUrlParameters']),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.username.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'preaddon' => 'ri-user-6-fill'
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.password.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'preaddon' => 'ri-lock-fill',
                    'autocomplete' => 'off'
                ],
            ])
            ->add('contentType', ChoiceType::class, [
                'choices' => [
                    'application/json' => 'application/json',
                    'application/xml' => 'application/xml',
                    'text/xml' => 'text/xml',
                    'application/soap+xml' => 'application/soap+xml',
                    'application/vnd.api+json' => 'application/vnd.api+json',
                    'application/x-www-form-urlencoded' => 'application/x-www-form-urlencoded',
                ],
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.content_type.label',
                'required' => true,
            ])
            ->add('body', TextareaType::class, [
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.body.label',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'leuchtfeuer.mautic-apicalls-bundle.body.placeholder',
                ],
                'constraints' => [
                    new Assert\Callback([$this, 'validateBodyByContentType']),
                ],
            ])
            ->add('contact_field', ChoiceType::class, [
                'choices' => $this->getTextFields(),
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.contactfield.label',
                'label_attr' => ['class' => 'control-label'],
                'placeholder' => 'leuchtfeuer.mautic-apicalls-bundle.contactfield.placeholder',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'leuchtfeuer.mautic-apicalls-bundle.contactfield.tooltip'
                ],
                'constraints' => [
                    new Assert\Callback([$this, 'validateByContentType']),
                ],
            ])
            ->add('regex', TextType::class, [
                'label' => 'leuchtfeuer.mautic-apicalls-bundle.regex.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => false,
                'constraints' => [
                    new Assert\Callback([$this, 'validateByContentType']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '/"myvalue"\s*:\s*"([^"]+)"/',
                    'tooltip' => 'leuchtfeuer.mautic-apicalls-bundle.regex.tooltip'
                ],
            ]);

        $builder->addEventSubscriber(new ApiCallsPreSubmitFormListener());
    }

    public function validateBodyByContentType(string|null $body, ExecutionContextInterface $context): void
    {
        // @phpstan-ignore-next-line
        $data = $context->getRoot()->getData();
        $contentType = $data['properties']['contentType'] ?? null;
        $method = $data['properties']['method'] ?? null;

        if (in_array($method, ['POST', 'PUT', 'PATCH']) && empty($body)) {
            $context->buildViolation('leuchtfeuer.mautic-apicalls-bundle.method.body.required')
                ->addViolation();
            return;
        }

        if ($method === 'GET' && !empty($body)) {
            $context->buildViolation('leuchtfeuer.mautic-apicalls-bundle.method.body.must.be.empty')
                ->addViolation();
            return;
        }

        if (in_array($contentType, ['application/json', 'application/vnd.api+json'])) {
            $validator = $context->getValidator();
            $violations = $validator->validate($body, new Assert\Json(['message' =>
                'leuchtfeuer.mautic-apicalls-bundle.body.invalid_json']));

            foreach ($violations as $violation) {
                $context->buildViolation($violation->getMessage())
                    ->addViolation();
            }
        }
    }

    public function validateUrlParameters(string|null $parameters, ExecutionContextInterface $context): void
    {
        // @phpstan-ignore-next-line
        $data = $context->getRoot()->getData();
        $method = $data['properties']['method'] ?? null;

        if (empty($parameters)) {
            return;
        }

        if ($method !== 'GET') {
            $context->buildViolation('leuchtfeuer.mautic-apicalls-bundle.get.method.required')
                ->addViolation();
            return;
        }

        if (str_starts_with($parameters, '?')) {
            $context->buildViolation('leuchtfeuer.mautic-apicalls-bundle.get.method.format.question.mark')
                ->addViolation();
            return;
        }

        $pairs = explode('&', $parameters);

        foreach ($pairs as $pair) {
            if (!str_contains($pair, '=')) {
                $context->buildViolation('leuchtfeuer.mautic-apicalls-bundle.get.method.format.required')
                    ->addViolation();
                return;
            }
        }

    }


    public function validateByContentType(string|null $parameters, ExecutionContextInterface $context): void
    {
        // @phpstan-ignore-next-line
        $data = $context->getRoot()->getData();
        $method = $data['properties']['method'] ?? null;

        if (empty($parameters)) {
            return;
        }

        if ($method !== 'GET') {
            $context->buildViolation('leuchtfeuer.mautic-apicalls-bundle.get.method.required')
                ->addViolation();
        }
    }


    /** @return array<string> */

    public function getTextFields(): array
    {
        $fieldChoices = [];

        $textFields = $this->fieldModel->getFieldsProperties([
            'isPublished' => true,
            'object' => 'lead'
        ]);

        $textTypeFields = array_filter($textFields, function($field) {
            return is_array($field) && isset($field['type']) && in_array($field['type'], ['text', 'textarea']);
        });

        foreach ($textTypeFields as $alias => $field) {
            if(is_array($field)){
                $fieldChoices[$field['label']] = $alias;
            }
        }

        return  $fieldChoices;
    }


    public function validateUrlAllowingPlaceholders(?string $url, ExecutionContextInterface $context): void
    {
        if (empty($url)) {
            return;
        }

        $urlForValidation = preg_replace('/\{[^}]*\}/', 'token', $url);

        $violations = $context->getValidator()->validate($urlForValidation, new Assert\Url());

        foreach ($violations as $violation) {
            $context->buildViolation('leuchtfeuer.mautic-apicalls-bundle.url.invalid')->addViolation();
        }
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('integration');
    }
}
