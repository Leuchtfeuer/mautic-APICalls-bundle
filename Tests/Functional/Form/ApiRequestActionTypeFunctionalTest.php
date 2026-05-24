<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Functional\Form;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use PHPUnit\Framework\Assert;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class ApiRequestActionTypeFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @param array<string, mixed> $properties
     */
    private function createCampaignPropertiesForm(array $properties = []): FormInterface
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::$container->get(FormFactoryInterface::class);

        return $formFactory
            ->createBuilder(FormType::class, ['properties' => $properties], ['csrf_protection' => false])
            ->add('properties', ApiRequestActionType::class)
            ->getForm();
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidGetProperties(string $regex = '/"email"\s*:\s*"([^"]+)"/'): array
    {
        return [
            'url'          => 'https://api.example.com/contacts',
            'method'       => 'GET',
            'contentType'  => 'application/json',
            'url_parameters' => 'email={contactfield=email}',
            'regex'        => $regex,
        ];
    }

    public function testFormAcceptsValidRegex(): void
    {
        $form = $this->createCampaignPropertiesForm($this->getValidGetProperties());

        $form->submit(['properties' => $this->getValidGetProperties()]);

        Assert::assertTrue($form->isSubmitted());
        Assert::assertTrue($form->isValid(), (string) $form->getErrors(true, false));
        Assert::assertCount(0, $form->get('properties')->get('regex')->getErrors(true));
    }

    public function testFormRejectsInvalidRegex(): void
    {
        $properties = $this->getValidGetProperties('[invalid');
        $form       = $this->createCampaignPropertiesForm($properties);

        $form->submit(['properties' => $properties]);

        Assert::assertTrue($form->isSubmitted());
        Assert::assertFalse($form->isValid());

        $regexErrors = $form->get('properties')->get('regex')->getErrors(true);
        Assert::assertGreaterThan(0, $regexErrors->count());

        $messages = array_map(static fn ($error) => $error->getMessage(), iterator_to_array($regexErrors));
        Assert::assertTrue(
            (bool) array_filter(
                $messages,
                static fn (string $message): bool => str_contains($message, 'regex.invalid')
                    || str_contains($message, 'valid PHP regular expression')
                    || str_contains($message, 'PHP-Regular-Ausdruck')
            ),
            'Expected regex validation message, got: '.implode(', ', $messages)
        );
    }

    public function testFormAllowsEmptyRegex(): void
    {
        $properties         = $this->getValidGetProperties();
        $properties['regex'] = '';

        $form = $this->createCampaignPropertiesForm($properties);
        $form->submit(['properties' => $properties]);

        Assert::assertTrue($form->isSubmitted());
        Assert::assertTrue($form->isValid(), (string) $form->getErrors(true, false));
    }
}
