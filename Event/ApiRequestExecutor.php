<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Event;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiRequestExecutor implements EventSubscriberInterface
{
    public function __construct(private HttpClientInterface $httpClient, private LeadModel $leadModel){}


    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            ApiRequestExecutor::class => ['onExecuteApiRequest', 0],
        ];
    }
    public function onExecuteApiRequest(PendingEvent $event): void
    {
        $config = $event->getConfig();
        $method = strtoupper($config['method'] ?? 'POST');
        $body   = $config['body'] ?? '';

/*        foreach ($event->getLeadIds() as $leadId) {
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
        }*/
    }
}