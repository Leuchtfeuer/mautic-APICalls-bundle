<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


class ApiCallsService
{

    private  const MAX_REDIRECTS = 5;
    public function __construct(private HttpClientInterface $client, private LeadModel $leadModel, private HttpRequestBuilderService $httpRequestBuilderService, private TokenReplacementService $tokenReplacementService)
    {}

    /**
     * @param ApiCallPropertiesDTO $dto
     * @param LeadEventLog $lead
     */
    public function sendRequest(LeadEventLog $lead, ApiCallPropertiesDTO $dto): void
    {
        $tokenizedValue = $this->tokenReplacementService->getTokenizedValue($lead, $dto);
        $urlAndOptions = $this->httpRequestBuilderService->buildUrlAndOptions($tokenizedValue, $dto);

        $currentUrl = $urlAndOptions['url'];
        $options = $urlAndOptions['options'];

        $response = $this->handleRedirects($dto, $currentUrl, $options, $tokenizedValue);

        if ($response !== null) {
            $this->checkIfResponseValid($response);

            if (!empty($dto->contactField) && $dto->method === 'GET') {
                $this->updateField($lead, $dto->contactField, $response, $dto->regex);
            }

            $this->setMetadata($lead, $response, $dto->method);
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


    private function handleRedirects(ApiCallPropertiesDTO $dto, string $currentUrl, array $options, string $tokenizedValue): ?ResponseInterface
    {
        $redirectCount = 0;
        $response = null;

        while ($redirectCount < self::MAX_REDIRECTS) {

            $response = $this->client->request($dto->method, $currentUrl, $options);

            if (!$this->isRedirectResponse($response)) {
                break;
            }

            $locationHeader = $this->getLocationHeader($response);

            if (!$locationHeader) {
                break;
            }

            $currentUrl = $this->buildRedirectUrl($dto, $locationHeader, $tokenizedValue);

            $redirectCount++;
        }

        return $response;
    }

    private function isRedirectResponse(ResponseInterface $response): bool
    {
        return in_array($response->getStatusCode(), [301, 302, 303, 307, 308]);
    }

    private function getLocationHeader(ResponseInterface $response): ?string
    {
        return $response->getHeaders(false)['location'][0] ?? null;
    }

    private function buildRedirectUrl(ApiCallPropertiesDTO $dto, string $locationHeader, string $tokenizedValue): string
    {
        if ($dto->method === 'GET' && !empty($tokenizedValue)) {
            $separator = str_contains($locationHeader, '?') ? '&' : '?';
            return $locationHeader . $separator . $tokenizedValue;
        }

        return $locationHeader;
    }


}