<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


class ApiCallsService
{
    public function __construct(private HttpClientInterface $client, private LeadModel $leadModel)
    {}

    /**
     * @param string $value
     * @param string $url
     * @param string $method
     * @param string $contentType
     * @param string $username
     * @param string $password
     */

    public function sendRequest(LeadEventLog $lead, string $value, string $url, string $method, string $contentType, string $username, string $password, string $contactField = ''): void
    {
        $originalParameters = '';

        if ($method === 'GET' && !empty($value)) {
            $originalParameters = $value;
            $separator = str_contains($url, '?') ? '&' : '?';
            $url = $url . $separator . $value;
        }

        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => $contentType,
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
        ];

        if ($method !== 'GET') {
            $options['body'] = $value;
        }

        if (!empty($username) && !empty($password)) {
            $options['auth_basic'] = [$username, $password];
        }

        $currentUrl = $url;
        $maxRedirects = 5;
        $redirectCount = 0;

        while ($redirectCount < $maxRedirects) {
            $response = $this->client->request($method, $currentUrl, $options);
            $statusCode = $response->getStatusCode();

            if (!in_array($statusCode, [301, 302, 303, 307, 308])) {
                break;
            }

            $locationHeader = $response->getHeaders(false)['location'][0] ?? null;
            if (!$locationHeader) {
                break;
            }

            if ($method === 'GET' && !empty($originalParameters)) {
                $separator = str_contains($locationHeader, '?') ? '&' : '?';
                $currentUrl = $locationHeader . $separator . $originalParameters;
            } else {
                $currentUrl = $locationHeader;
            }

            $redirectCount++;
        }

        if ($response !== null) {
            $this->checkIfResponseValid($response);
            $this->updateField($lead, $contactField, $response);
            $this->setMetadata($lead, $response, $method);
        }
    }


    /**
     * @param ResponseInterface $response
     */
    public function checkIfResponseValid($response):void
    {
        $response->getContent();
    }


    public function updateField(LeadEventLog $lead,  string $contactField, ResponseInterface $response):void
    {
        if (!empty($contactField)){
            $lead->getLead()->addUpdatedField($contactField, $response->getContent(false));
            $this->leadModel->saveEntity($lead->getLead());
        }
    }

    public function setMetadata(LeadEventLog $lead, ResponseInterface $response, string $method):void
    {
        $lead->setMetadata([
            'event' => 'api_calls',
            'object_description' => 'API-Request Response',
            'response_header' => $response->getHeaders(false),
            'response_body' => $response->getContent(false),
            'method' => $method
        ]);
    }



}