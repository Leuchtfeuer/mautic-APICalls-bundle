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
    public function __construct(private HttpClientInterface       $client,
                                private LeadModel                 $leadModel,
                                private HttpRequestBuilderService $httpRequestBuilderService,
                                private TokenReplacementService   $tokenReplacementService,
                                private UrlBuilderService         $urlBuilderService,
                                private PropertySearchService     $propertySearchService)
    {}

    /**
     * @param ApiCallPropertiesDTO $dto
     * @param LeadEventLog $lead
     */
    public function sendRequest(LeadEventLog $lead, ApiCallPropertiesDTO $dto): void
    {
        $tokenizedValue = $this->tokenReplacementService->getTokenizedValue($lead, $dto);
        $urlAndOptions = $this->httpRequestBuilderService->buildUrlAndOptions($tokenizedValue, $dto, $lead);

        $currentUrl = $urlAndOptions['url'];
        $options = $urlAndOptions['options'];

        $response = $this->handleRedirects($dto, $currentUrl, $options, $tokenizedValue);

        $this->checkIfResponseValid($response);

        if (!empty($dto->contactField) && $dto->method === 'GET') {

            $content = $response->getContent(false);

            $decoded = json_decode($content);

            if (json_last_error() === JSON_ERROR_NONE && $dto->valueKey) {

                $content = $this->propertySearchService->getValue($decoded, $dto->valueKey, $dto->objectKey);
            }

            $this->updateField($lead, $dto->contactField, $content, $dto->regex);
        }

        $this->setMetadata($lead, $response, $dto->method);
    }

    /**
     * @param ResponseInterface $response
     */
    public function checkIfResponseValid(ResponseInterface $response):void
    {
        $response->getContent();
    }

    public function updateField(LeadEventLog $lead, string $contactField, string $content, string $regex): void
    {

        if (!empty($regex)) {
            if (preg_match_all($regex, $content, $matches)) {
                if (isset($matches[1]) && !empty($matches[1])) {
                    $content = implode(' ', $matches[1]);
                } else {
                    $content = implode(' ', $matches[0]);
                }
            }
        }

        if (!empty($content)) {
            // @phpstan-ignore-next-line
            $lead->getLead()->addUpdatedField($contactField, $content);

            if($lead->getLead()){
                $this->leadModel->saveEntity($lead->getLead());
            }
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

    /**
     * @param array<mixed> $options
     */

    private function handleRedirects(ApiCallPropertiesDTO $dto, string $currentUrl, array $options, string $tokenizedValue): ResponseInterface
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

            $currentUrl = $this->urlBuilderService->appendQueryString($dto, $locationHeader, $tokenizedValue);

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


}