<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\ApiCallsPreSubmitFormListener;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;

final class ApiCallsPreSubmitFormListenerTest extends TestCase
{
    /** @var CampaignActionSecretService&MockObject */
    private CampaignActionSecretService $secretService;

    private ApiCallsPreSubmitFormListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secretService = $this->createMock(CampaignActionSecretService::class);
        $this->listener      = new ApiCallsPreSubmitFormListener($this->secretService);
    }

    public function testPreSetDataClearsStoredSecretsFromFormData(): void
    {
        $this->secretService->expects(self::exactly(2))
            ->method('sanitizeForFormDisplay')
            ->willReturn('');

        $event = new FormEvent($this->createMock(Form::class), [
            'password'              => 'cipher|vector',
            'authorization_header'  => 'legacy-secret',
            'url'                   => 'https://api.example.com',
        ]);

        $this->listener->preSetData($event);

        self::assertSame('', $event->getData()['password']);
        self::assertSame('', $event->getData()['authorization_header']);
        self::assertSame('https://api.example.com', $event->getData()['url']);
    }

    public function testPreservesEncryptedPasswordWhenSubmittedEmpty(): void
    {
        $rootForm = $this->createMock(Form::class);
        $rootForm->method('getData')->willReturn(['properties' => ['password' => 'cipher|vector']]);

        $form = $this->createMock(Form::class);
        $form->method('getRoot')->willReturn($rootForm);

        $this->secretService->expects(self::once())
            ->method('encryptIfNeeded')
            ->with('cipher|vector')
            ->willReturn('cipher|vector');

        $event = new FormEvent($form, [
            'url'      => 'https://api.example.com/webhook',
            'method'   => 'POST',
            'body'     => '{"data":"test"}',
            'password' => '',
        ]);

        $this->listener->preSubmitData($event);

        self::assertSame('cipher|vector', $event->getData()['password']);
        self::assertSame('{"data":"test"}', $event->getData()['body']);
    }

    public function testEncryptsSubmittedPassword(): void
    {
        $rootForm = $this->createMock(Form::class);
        $rootForm->method('getData')->willReturn([]);

        $form = $this->createMock(Form::class);
        $form->method('getRoot')->willReturn($rootForm);

        $this->secretService->expects(self::once())
            ->method('encryptIfNeeded')
            ->with('new_password')
            ->willReturn('encrypted|blob');

        $event = new FormEvent($form, [
            'password' => 'new_password',
            'body'     => '{"data":"test"}',
        ]);

        $this->listener->preSubmitData($event);

        self::assertSame('encrypted|blob', $event->getData()['password']);
    }

    public function testPreservesEncryptedAuthorizationHeaderWhenSubmittedEmpty(): void
    {
        $rootForm = $this->createMock(Form::class);
        $rootForm->method('getData')->willReturn(['properties' => ['authorization_header' => 'cipher|vector']]);

        $form = $this->createMock(Form::class);
        $form->method('getRoot')->willReturn($rootForm);

        $this->secretService->expects(self::once())
            ->method('encryptIfNeeded')
            ->with('cipher|vector')
            ->willReturn('cipher|vector');

        $event = new FormEvent($form, [
            'authorization_header' => '',
        ]);

        $this->listener->preSubmitData($event);

        self::assertSame('cipher|vector', $event->getData()['authorization_header']);
    }

    public function testFormTypeCleanMasksPreserveJsonBodyContent(): void
    {
        $rawBody = '{"contact_email": "{contactfield=email}", "html_content": "<script>alert(\'test\')</script>", "special_chars": "&<>\"\'"}';

        $data = [
            'properties' => [
                'body' => $rawBody,
                'url'  => 'https://api.example.com/webhook',
            ],
        ];

        $result = InputHelper::_($data, [
            'properties' => CampaignActionSubscriber::FORM_TYPE_CLEAN_MASKS,
        ]);

        self::assertSame($rawBody, $result['properties']['body']);
    }

    public function testDefaultCleanMaskStripsHtmlFromBody(): void
    {
        $rawBody = '{"html_content": "<script>alert(\'test\')</script>"}';

        $data = [
            'properties' => [
                'body' => $rawBody,
            ],
        ];

        $result = InputHelper::_($data, ['properties' => 'clean']);

        self::assertNotSame($rawBody, $result['properties']['body']);
    }
}
