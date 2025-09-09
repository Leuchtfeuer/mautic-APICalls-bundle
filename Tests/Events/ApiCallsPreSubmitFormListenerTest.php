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
        $rawBodyContent = '{"contact_email": "{contactfield=email}", "html_content": "<script>alert(\'test\')</script>", "special_chars": "&<>\"\'",
  "tokens": "{contactfield=firstname} {contactfield=lastname}"}';

        // Simulate form data that would come from the UI (potentially sanitized)
        $formData = [
            'url' => 'https://api.example.com/webhook',
            'method' => 'POST',
            'contentType' => 'application/json',
            'body' => 'This would be sanitized content' // This should be overwritten by listener
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

        // Create and submit the form
        $formFactory = self::$container->get(FormFactoryInterface::class);
        $form = $formFactory->create(ApiRequestActionType::class);

        // Submit form data (your listener should intercept this)
        $form->submit($formData);

        // Verify form is valid
        $this->assertTrue($form->isValid(), 'Form should be valid');

        // Get the processed form data (after your listener processes it)
        $processedData = $form->getData();

        // Verify that your listener correctly replaced the body with raw content from $_POST
        $this->assertEquals($rawBodyContent, $processedData['body'], 'Listener should use raw body content from $_POST');
        $this->assertNotEquals('This would be sanitized content', $processedData['body'], 'Original form body should be overwritten');

        // Verify other fields remain unchanged
        $this->assertEquals('https://api.example.com/webhook', $processedData['url']);
        $this->assertEquals('POST', $processedData['method']);
        $this->assertEquals('application/json', $processedData['contentType']);

        // Verify the raw content contains elements that would typically be sanitized
        $this->assertStringContainsString('<script>', $processedData['body'], 'HTML script tags should be preserved');
        $this->assertStringContainsString('&<>"\'', $processedData['body'], 'Special characters should be preserved');
        $this->assertStringContainsString('{contactfield=email}', $processedData['body'], 'Contact field tokens should be preserved');

        // Clean up $_POST
        unset($_POST['campaignevent']);
    }

    public function testListenerOnlyProcessesApiRequestActions(): void
    {
        // Test data for a different action type
        $formData = [
            'url' => 'https://api.example.com/webhook',
            'method' => 'POST',
            'contentType' => 'application/json',
            'body' => 'original body content'
        ];

        // Mock $_POST data for a different action type
        $_POST['campaignevent'] = [
            'type' => 'some.other.action', // Different action type
            'properties' => [
                'url' => 'https://api.example.com/webhook',
                'method' => 'POST',
                'contentType' => 'application/json',
                'body' => 'different body content'
            ]
        ];

        // Create a form for testing
        $formFactory = self::$container->get(FormFactoryInterface::class);
        $form = $formFactory->create(ApiRequestActionType::class);

        // Submit form data
        $form->submit($formData);

        // Get the processed form data
        $processedData = $form->getData();

        // Verify that your listener did NOT modify the data for non-API actions
        $this->assertEquals('original body content', $processedData['body'], 'Listener should not modify body for non-API actions');

        // Clean up $_POST
        unset($_POST['campaignevent']);
    }

    public function testListenerStoresComplexJsonWithTokensInDatabase(): void
    {
        // Create campaign and event
        $campaign = new Campaign();
        $campaign->setName('Test API Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        $event = new Event();
        $event->setName('API Request Action');
        $event->setType('mautic.leuchtfeuer.api_request');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setCampaign($campaign);
        $this->em->persist($event);
        $this->em->flush();

        // Complex JSON with tokens and special content that should be preserved
        $complexJsonBody = '{
              "contact": {
                  "email": "{contactfield=email}",
                  "firstName": "{contactfield=firstname}",
                  "lastName": "{contactfield=lastname}",
                  "customField": "{contactfield=custom_field}"
              },
              "metadata": {
                  "timestamp": "{date}",
                  "source": "mautic",
                  "html_snippet": "<div class=\\"custom\\"><p>Hello {contactfield=firstname}!</p></div>",
                  "special_chars": "Test with & < > \\" \' characters",
                  "script_content": "<script>console.log(\\"API call from Mautic\\");</script>"
              },
              "nested_tokens": {
                  "full_name": "{contactfield=firstname} {contactfield=lastname}",
                  "company_info": "{contactfield=company} - {contactfield=position}"
              }
          }';

        $formData = [
            'url' => 'https://api.example.com/webhook',
            'method' => 'POST',
            'contentType' => 'application/json',
            'body' => 'placeholder body'
        ];

        // Mock $_POST with complex JSON
        $_POST['campaignevent'] = [
            'type' => 'mautic.leuchtfeuer.api_request',
            'properties' => [
                'url' => 'https://api.example.com/webhook',
                'method' => 'POST',
                'contentType' => 'application/json',
                'body' => $complexJsonBody
            ]
        ];

        // Process form
        $formFactory = self::$container->get(FormFactoryInterface::class);
        $form = $formFactory->create(ApiRequestActionType::class);
        $form->submit($formData);

        // Update event with processed data
        $event->setProperties($form->getData());
        $this->em->persist($event);
        $this->em->flush();
        $this->em->clear();

        // Verify complex content is stored correctly in database
        $storedEvent = $this->em->getRepository(Event::class)->find($event->getId());
        $storedProperties = $storedEvent->getProperties();

        // Verify all complex content is preserved
        $this->assertStringContainsString('{contactfield=email}', $storedProperties['body']);
        $this->assertStringContainsString('<div class="custom">', $storedProperties['body']);
        $this->assertStringContainsString('<script>console.log', $storedProperties['body']);
        $this->assertStringContainsString('& < > " \'', $storedProperties['body']);
        $this->assertStringContainsString('{contactfield=firstname} {contactfield=lastname}', $storedProperties['body']);

        // Verify JSON structure is maintained
        $decodedBody = json_decode($storedProperties['body'], true);
        $this->assertIsArray($decodedBody, 'Stored body should be valid JSON');
        $this->assertEquals('{contactfield=email}', $decodedBody['contact']['email']);
        $this->assertStringContainsString('<script>', $decodedBody['metadata']['script_content']);

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
        $processedData = $form->getData();

        // Verify original body is preserved when no $_POST override exists
        $this->assertEquals('original body content', $processedData['body']);
    }
}

