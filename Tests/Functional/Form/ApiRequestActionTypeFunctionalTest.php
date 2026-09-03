<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Functional\Form;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Factory\ApiCallPropertiesDTOFactory;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class ApiRequestActionTypeFunctionalTest extends MauticMysqlTestCase
{
    private function createSecretService(): CampaignActionSecretService
    {
        /** @var EncryptionHelper $encryptionHelper */
        $encryptionHelper = self::getContainer()->get(EncryptionHelper::class);

        return new CampaignActionSecretService($encryptionHelper);
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function createCampaignPropertiesForm(array $properties = []): FormInterface
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);

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
            'url'            => 'https://api.example.com/contacts',
            'method'         => 'GET',
            'contentType'    => 'application/json',
            'url_parameters' => 'email={contactfield=email}',
            'regex'          => $regex,
        ];
    }

    public function testFormAcceptsValidRegex(): void
    {
        $form = $this->createCampaignPropertiesForm($this->getValidGetProperties());

        $form->submit(['properties' => $this->getValidGetProperties()]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true, false));
        $this->assertCount(0, $form->get('properties')->get('regex')->getErrors(true));
    }

    public function testFormRejectsInvalidRegex(): void
    {
        $properties = $this->getValidGetProperties('[invalid');
        $form       = $this->createCampaignPropertiesForm($properties);

        $form->submit(['properties' => $properties]);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());

        $regexErrors = $form->get('properties')->get('regex')->getErrors(true);
        $this->assertGreaterThan(0, $regexErrors->count());

        $messages = array_map(static fn (\Symfony\Component\Form\FormError|\Symfony\Component\Form\FormErrorIterator $error): string => $error->getMessage(), iterator_to_array($regexErrors));
        $this->assertTrue((bool) array_filter(
            $messages,
            static fn (string $message): bool => str_contains($message, 'regex.invalid')
                || str_contains($message, 'valid PHP regular expression')
                || str_contains($message, 'PHP-Regular-Ausdruck')
        ), 'Expected regex validation message, got: '.implode(', ', $messages));
    }

    public function testFormAllowsEmptyRegex(): void
    {
        $properties          = $this->getValidGetProperties();
        $properties['regex'] = '';

        $form = $this->createCampaignPropertiesForm($properties);
        $form->submit(['properties' => $properties]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true, false));
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function createStandaloneForm(array $properties = []): FormInterface
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);

        return $formFactory
            ->createBuilder(ApiRequestActionType::class, $properties, ['csrf_protection' => false])
            ->getForm();
    }

    public function testStandaloneFormRejectsUrlParametersForNonGetMethod(): void
    {
        $properties = [
            'url'            => 'https://api.example.com/contacts',
            'method'         => 'POST',
            'contentType'    => 'application/json',
            'body'           => '{"email":"test@example.com"}',
            'url_parameters' => 'email=test@example.com',
        ];

        $form = $this->createStandaloneForm($properties);
        $form->submit($properties);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('url_parameters')->getErrors(true)->count());
    }

    public function testStandaloneFormRejectsBodyForGetMethod(): void
    {
        $properties = [
            'url'         => 'https://api.example.com/contacts',
            'method'      => 'GET',
            'contentType' => 'application/json',
            'body'        => '{"email":"test@example.com"}',
        ];

        $form = $this->createStandaloneForm($properties);
        $form->submit($properties);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('body')->getErrors(true)->count());
    }

    public function testFormEncryptsSecretsOnSubmit(): void
    {
        $secretService = $this->createSecretService();

        $properties = [
            'url'                   => 'https://api.example.com/contacts',
            'method'                => 'POST',
            'contentType'           => 'application/json',
            'body'                  => '{"email":"test@example.com"}',
            'password'              => 'plain-password',
            'authorization_header'  => 'Authorization: Bearer token',
        ];

        $form = $this->createCampaignPropertiesForm();
        $form->submit(['properties' => $properties]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true, false));

        $stored = $form->get('properties')->getNormData();
        $this->assertTrue($secretService->isEncrypted($stored['password']));
        $this->assertTrue($secretService->isEncrypted($stored['authorization_header']));
        $this->assertNotSame('plain-password', $stored['password']);
        $this->assertNotSame('Authorization: Bearer token', $stored['authorization_header']);
    }

    public function testFormDoesNotDisplayStoredSecretsWhenEditing(): void
    {
        $secretService = $this->createSecretService();

        $encryptedPassword = $secretService->encrypt('stored-password');
        $encryptedHeader   = $secretService->encrypt('Authorization: Bearer stored');

        $form = $this->createCampaignPropertiesForm([
            'url'                  => 'https://api.example.com/contacts',
            'method'               => 'POST',
            'contentType'          => 'application/json',
            'body'                 => '{"email":"test@example.com"}',
            'password'             => $encryptedPassword,
            'authorization_header' => $encryptedHeader,
        ]);

        $this->assertSame('', $form->get('properties')->get('password')->getViewData());
        $this->assertSame('', $form->get('properties')->get('authorization_header')->getViewData());
        $this->assertSame('leuchtfeuer.mautic-apicalls-bundle.secret.stored.placeholder', $form->get('properties')->get('password')->getConfig()->getOption('attr')['placeholder']);
    }

    public function testFormPreservesEncryptedSecretsWhenFieldsLeftEmpty(): void
    {
        $secretService = $this->createSecretService();

        $encryptedPassword = $secretService->encrypt('stored-password');
        $encryptedHeader   = $secretService->encrypt('Authorization: Bearer stored');

        $existing = [
            'url'                  => 'https://api.example.com/contacts',
            'method'               => 'POST',
            'contentType'          => 'application/json',
            'body'                 => '{"email":"test@example.com"}',
            'password'             => $encryptedPassword,
            'authorization_header' => $encryptedHeader,
        ];

        $form = $this->createCampaignPropertiesForm($existing);
        $form->submit([
            'properties' => [
                'url'                  => $existing['url'],
                'method'               => $existing['method'],
                'contentType'          => $existing['contentType'],
                'body'                 => $existing['body'],
                'password'             => '',
                'authorization_header' => '',
            ],
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true, false));

        $stored = $form->get('properties')->getNormData();
        $this->assertSame($encryptedPassword, $stored['password']);
        $this->assertSame($encryptedHeader, $stored['authorization_header']);
    }

    public function testFormDoesNotDisplayLegacyPlaintextSecretsWhenEditing(): void
    {
        $form = $this->createCampaignPropertiesForm([
            'url'                  => 'https://api.example.com/contacts',
            'method'               => 'POST',
            'contentType'          => 'application/json',
            'body'                 => '{"email":"test@example.com"}',
            'password'             => 'legacy-plain-password',
            'authorization_header' => 'Authorization: Bearer legacy-token',
        ]);

        $this->assertSame('', $form->get('properties')->get('password')->getViewData());
        $this->assertSame('', $form->get('properties')->get('authorization_header')->getViewData());
    }

    public function testFactoryDecryptsStoredSecretsForRuntime(): void
    {
        $secretService = $this->createSecretService();
        $factory       = new ApiCallPropertiesDTOFactory($secretService);

        $dto = $factory->createFromProperties([
            'url'                  => 'https://api.example.com/contacts',
            'method'               => 'POST',
            'contentType'          => 'application/json',
            'password'             => $secretService->encrypt('runtime-password'),
            'authorization_header' => $secretService->encrypt('Authorization: Bearer runtime'),
        ]);

        $this->assertSame('runtime-password', $dto->password);
        $this->assertSame('Authorization: Bearer runtime', $dto->authorizationHeader);
    }
}
