<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\ApiCallsPreSubmitFormListener;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;

final class ApiCallsPreSubmitFormListenerTest extends TestCase
{
    private ApiCallsPreSubmitFormListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new ApiCallsPreSubmitFormListener();
    }

    public function testPreservesPasswordWhenSubmittedEmpty(): void
    {
        $rootForm = $this->createMock(Form::class);
        $rootForm->method('getData')->willReturn(['properties' => ['password' => 'existing_password_123']]);

        $form = $this->createMock(Form::class);
        $form->method('getRoot')->willReturn($rootForm);

        $event = new FormEvent($form, [
            'url'      => 'https://api.example.com/webhook',
            'method'   => 'POST',
            'body'     => '{"data":"test"}',
            'password' => '',
        ]);

        $this->listener->preSubmitData($event);

        self::assertSame('existing_password_123', $event->getData()['password']);
        self::assertSame('{"data":"test"}', $event->getData()['body']);
    }

    public function testDoesNotModifyDataWhenPasswordIsSubmitted(): void
    {
        $form = $this->createMock(Form::class);
        $form->expects(self::never())->method('getRoot');

        $event = new FormEvent($form, [
            'password' => 'new_password',
            'body'     => '{"data":"test"}',
        ]);

        $this->listener->preSubmitData($event);

        self::assertSame('new_password', $event->getData()['password']);
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
