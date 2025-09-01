<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Event;

use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventSubscriber\CampaignActionSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiRequestExecutor implements EventSubscriberInterface
{

    private HttpClientInterface $httpClient;
    private LeadModel $leadModel;

    public function __construct(HttpClientInterface $httpClient, LeadModel $leadModel)
    {
        $this->httpClient = $httpClient;
        $this->leadModel = $leadModel;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignActionSubscriber::class => ['onExecuteApiRequest', 0],
        ];
    }

    public function onExecuteApiRequest(PendingEvent $event): void
    {
        $config = $event->getConfig();
        $method = strtoupper($config['method'] ?? 'POST');
        $body   = $config['body'] ?? '';

        foreach ($event->getLeadIds() as $leadId) {
            $lead = $this->leadModel->getEntity($leadId);

            if (!$lead) {
                continue;
            }

            // Replace tokens {contactfield=firstname}
            $replacedBody = preg_replace_callback('/\{contactfield=(.*?)\}/', function ($matches) use ($lead) {
                $field = $matches[1];
                return $lead->getFieldValue($field) ?? '';
            }, $body);

            try {
                $this->httpClient->request($method, 'https://your.api.endpoint/here', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $replacedBody,
                ]);

                // Mark this contact's log as successful
                if ($log = $event->findLogByContactId($leadId)) {
                    $event->pass($log);
                }
            } catch (\Throwable $e) {
                if ($log = $event->findLogByContactId($leadId)) {
                    $event->fail($log);
                }
            }
        }
    }
}