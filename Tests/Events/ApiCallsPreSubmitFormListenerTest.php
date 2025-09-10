<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use Symfony\Component\Form\FormFactoryInterface;


class ApiCallsPreSubmitFormListenerTest extends MauticMysqlTestCase
{


    public function testPreSubmitListenerBypassesSanitizerAndStoresRawContent(): void
    {
        // Test data that would typically be sanitized by Mautic
        $rawBodyContent = '{"contact_email": "{contactfield=email}", "html_content": "<script>alert(\'test\')</script>", "special_chars":
  "&<>\"\'", "tokens": "{contactfield=firstname} {contactfield=lastname}"}';

        // Simulate form data that would come from the UI (potentially sanitized)
        $formData = [
            'url' => 'https://api.example.com/webhook',
            'method' => 'POST',
            'contentType' => 'application/json',
            'body' => 'This would be sanitized content', // This should be overwritten by listener
            'password' => '' // Empty password to test preservation logic
        ];

        // Mock $_POST data with the raw content that should bypass sanitization
        $_POST['campaignevent'] = [
            'type' => 'mautic.leuchtfeuer.api_request',
            'properties' => [
                'url' => 'https://api.example.com/webhook',
                'method' => 'POST',
                'contentType' => 'application/json',
                'body' => $rawBodyContent // Raw content from $_POST
            ]
        ];

        // Create form with existing password data (simulating editing existing campaign action)
        $formFactory = self::$container->get(FormFactoryInterface::class);
        $initialData = ['properties' => ['password' => 'existing_password_123']];
        $form = $formFactory->create(ApiRequestActionType::class, $initialData);

        // Submit form data (your listener should intercept this)
        $form->submit($formData);

        // Get the processed form data (after your listener processes it)
        /** @var array<string, mixed> $processedData */
        $processedData = $form->getData();

        // Verify that your listener correctly replaced the body with raw content from $_POST
        $this->assertEquals($rawBodyContent, $processedData['body'], 'Listener should use raw body content from $_POST');
        $this->assertNotEquals('This would be sanitized content', $processedData['body'], 'Original form body should be overwritten');

        // Verify password preservation logic works
        $this->assertEquals('existing_password_123', $processedData['password'], 'Existing password should be preserved when empty password
  is submitted');

        // Verify other fields remain unchanged
        $this->assertEquals('https://api.example.com/webhook', $processedData['url']);
        $this->assertEquals('POST', $processedData['method']);
        $this->assertEquals('application/json', $processedData['contentType']);

        // Clean up $_POST
        unset($_POST['campaignevent']);
    }


    public function testListenerHandlesMissingPostData(): void
    {
        // Test when $_POST data is missing or incomplete
        $formData = [
            'url' => 'https://api.example.com/webhook',
            'method' => 'POST',
            'contentType' => 'application/json',
            'body' => 'original body content'
        ];

        // Don't set $_POST['campaignevent'] to simulate missing data

        $formFactory = self::$container->get(FormFactoryInterface::class);
        $form = $formFactory->create(ApiRequestActionType::class);

        // This should not cause errors even without $_POST data
        $form->submit($formData);

        /** @var array<string, mixed> $processedData */
        $processedData = $form->getData();

        // Verify original body is preserved when no $_POST override exists
        $this->assertEquals('original body content', $processedData['body']);
    }

}

