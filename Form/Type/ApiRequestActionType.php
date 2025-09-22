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
            ->add('contact_field', ChoiceType::class, [
                'choices' => $this->getTextFields(),
                'label' => 'leuchtfeuer.api.contact.field.stored',
                'label_attr' => ['class' => 'control-label'],
                'placeholder' => 'Choose a text field...',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'leuchtfeuer.api.select.text.field'
                ],
            ])
            ->add('url', TextType::class, [
                'label' => 'leuchtfeuer.api.url.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'leuchtfeuer.api.url.required']),
                    new Assert\Url(['message' => 'leuchtfeuer.api.url.invalid']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'leuchtfeuer.api.url.placeholder'
                ],
            ])
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'GET'  =>  'GET',
                    'POST'  => 'POST',
                    'PUT'   => 'PUT',
                    'PATCH' => 'PATCH',
                ],
                'label' => 'leuchtfeuer.api.method.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'leuchtfeuer.api.http.request.method'
                ],
            ])
            ->add('url_parameters', TextType::class, [
                'label' => 'URL parameters (only for GET)',
                'label_attr' => ['class' => 'control-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'email={contactfield=email}&category=7',
                    'tooltip' => 'leuchtfeuer.api.get.request.format'
                ],
                'constraints' => [
                    new Assert\Callback([$this, 'validateUrlParameters']),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'leuchtfeuer.api.username.label',
                'label_attr' => ['class' => 'control-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'preaddon' => 'ri-user-6-fill'
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'leuchtfeuer.api.password.label',
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
                'label' => 'leuchtfeuer.api.content_type.label',
                'required' => true,
            ])
            ->add('body', TextareaType::class, [
                'label' => 'leuchtfeuer.api.body.label',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'leuchtfeuer.api.json.placeholder',
                ],
                'constraints' => [
                    new Assert\Callback([$this, 'validateBodyByContentType']),
                ],
            ])
            ->add('regex', TextType::class, [
                'label' => 'Pre-Store Regex for Contact Field',
                'label_attr' => ['class' => 'control-label'],
                'required' => false,
                'constraints' => [
                    new Assert\Callback([$this, 'validateRegexByContentType']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '/[a-zA-Z]+/',
                    'tooltip' => 'leuchtfeuer.api.regex.example'
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
            $context->buildViolation('leuchtfeuer.api.get.method.body.required')
                ->addViolation();
            return;
        }

        if ($method === 'GET' && !empty($body)) {
            $context->buildViolation('leuchtfeuer.api.get.method.body.empty')
                ->addViolation();
            return;
        }

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

    public function validateUrlParameters(string|null $parameters, ExecutionContextInterface $context): void
    {
        // @phpstan-ignore-next-line
        $data = $context->getRoot()->getData();
        $method = $data['properties']['method'] ?? null;

        if (empty($parameters)) {
            return;
        }

        if ($method !== 'GET') {
            $context->buildViolation('leuchtfeuer.api.get.method')
                ->addViolation();
            return;
        }

        if (str_starts_with($parameters, '?')) {
            $context->buildViolation('leuchtfeuer.api.get.format.question.mark')
                ->addViolation();
            return;
        }

        $pairs = explode('&', $parameters);

        foreach ($pairs as $pair) {
            if (!str_contains($pair, '=')) {
                $context->buildViolation('leuchtfeuer.api.get.format')
                    ->addViolation();
                return;
            }
        }

    }


    public function validateRegexByContentType(string|null $parameters, ExecutionContextInterface $context): void
    {
        // @phpstan-ignore-next-line
        $data = $context->getRoot()->getData();
        $method = $data['properties']['method'] ?? null;

        if (empty($parameters)) {
            return;
        }

        if ($method !== 'GET') {
            $context->buildViolation('leuchtfeuer.api.get.method.regex')
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



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('integration');
    }
}
