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

    public function sendRequest(LeadEventLog $lead, string $value, string $url, string $method, string $contentType, string $username, string $password, string $contactField, string $regex): void
    {
        //build url with GET parameters
        if ($method === 'GET' && !empty($value)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url = $url . $separator . $value;
        }
        //options for sending request
        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => $contentType,
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
        ];

        //if not GET method then set body
        if ($method !== 'GET') {
            $options['body'] = $value;
        }
        //if there are uer and password then auth_basic
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

            if ($method === 'GET' && !empty($value)) {
                $separator = str_contains($locationHeader, '?') ? '&' : '?';
                $currentUrl = $locationHeader . $separator . $value;
            } else {
                $currentUrl = $locationHeader;
            }

            $redirectCount++;
        }

        if ($response !== null) {

            $this->checkIfResponseValid($response);

            if (!empty($contactField) && $method === 'GET'){
                 $this->updateField($lead, $contactField, $response, $regex);
            }

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

    public function updateField(LeadEventLog $lead, string $contactField, ResponseInterface $response, string $regex): void
    {
        $content = $response->getContent(false);

        if (!empty($regex)) {
            if (preg_match_all($regex, $content, $matches)) {
                $content = implode(' ', $matches[0]);
            }
        }

        $lead->getLead()->addUpdatedField($contactField, $content);
        $this->leadModel->saveEntity($lead->getLead());
    }

    public function setMetadata(LeadEventLog $lead, ResponseInterface $response, string $method):void
    {
        $lead->setMetadata([
            'event' => 'api_calls',
            'object_description' => 'API-Request Response',
            'response_header' => $response->getHeaders(false) ?? 'No Headers',
            'response_body' => $response->getContent(false) ?? 'No Body',
            'method' => $method
        ]);
    }



}