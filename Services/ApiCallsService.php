<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiCallsService
{
    private const MAX_REDIRECTS = 5;

    /**
     * Maximum number of bytes stored in campaign event metadata for the API response body.
     */
    public const MAX_METADATA_RESPONSE_BODY_LENGTH = 65536;

    public function __construct(private HttpClientInterface $client,
        private LeadModel $leadModel,
        private HttpRequestBuilderService $httpRequestBuilderService,
        private TokenReplacementService $tokenReplacementService,
        private UrlBuilderService $urlBuilderService,
        private PropertySearchService $propertySearchService)
    {
    }

    public function sendRequest(LeadEventLog $lead, ApiCallPropertiesDTO $dto): void
    {
        $tokenizedValue = $this->tokenReplacementService->getTokenizedValue($lead, $dto);
        $urlAndOptions  = $this->httpRequestBuilderService->buildUrlAndOptions($tokenizedValue, $dto, $lead);

        $currentUrl = $urlAndOptions['url'];
        $options    = $urlAndOptions['options'];

        $response = $this->handleRedirects($dto, $currentUrl, $options, $tokenizedValue);

        $this->checkIfResponseValid($response);

        if (!empty($dto->contactField) && 'GET' === $dto->method) {
            $content = $response->getContent(false);

            $decoded = json_decode($content);

            if (JSON_ERROR_NONE === json_last_error() && !empty($dto->valueKey)) {
                $content = $this->propertySearchService->getValue($decoded, $dto->valueKey, $dto->objectKey);
            }

            $this->updateField($lead, $dto->contactField, $content, $dto->regex);
        }

        $this->setMetadata($lead, $response, $dto->method);
    }

    public function checkIfResponseValid(ResponseInterface $response): void
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
            $leadEntity = $lead->getLead();
            if (null !== $leadEntity) {
                $leadEntity->addUpdatedField($contactField, $content);
                $this->leadModel->saveEntity($leadEntity);
            }
        }
    }

    public function setMetadata(LeadEventLog $lead, ResponseInterface $response, string $method): void
    {
        $responseBody = $response->getContent(false);
        $metadata     = [
            'event'              => 'api_calls',
            'object_description' => 'API-Request Response',
            'response_header'    => $response->getHeaders(false),
            'response_body'      => $responseBody,
            'method'             => $method,
        ];

        if (strlen($responseBody) > self::MAX_METADATA_RESPONSE_BODY_LENGTH) {
            $metadata['response_body']                 = substr($responseBody, 0, self::MAX_METADATA_RESPONSE_BODY_LENGTH);
            $metadata['response_body_truncated']       = true;
            $metadata['response_body_original_length'] = strlen($responseBody);
        }

        $lead->setMetadata($metadata);
    }

    /**
     * @param array<mixed> $options
     */
    private function handleRedirects(ApiCallPropertiesDTO $dto, string $currentUrl, array $options, string $tokenizedValue): ResponseInterface
    {
        $redirectCount = 0;
        $response      = null;

        while ($redirectCount < self::MAX_REDIRECTS) {
            $response = $this->client->request($dto->method, $currentUrl, $options);

            if (!$this->isRedirectResponse($response)) {
                break;
            }

            $locationHeader = $this->getLocationHeader($response);

            if (!$locationHeader) {
                break;
            }

            $redirectUrl = $this->urlBuilderService->appendQueryString($dto, $locationHeader, $tokenizedValue);

            if (!$this->isSameOrigin($currentUrl, $redirectUrl)) {
                $options = $this->stripCredentialsFromOptions($options);
            }

            $currentUrl = $redirectUrl;

            ++$redirectCount;
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function stripCredentialsFromOptions(array $options): array
    {
        unset($options['auth_basic']);

        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach (array_keys($options['headers']) as $headerName) {
                if (0 === strcasecmp($headerName, 'Authorization')) {
                    unset($options['headers'][$headerName]);
                }
            }
        }

        return $options;
    }

    private function isSameOrigin(string $fromUrl, string $toUrl): bool
    {
        $from = parse_url($fromUrl);
        $to   = parse_url($toUrl);

        if (!is_array($from) || !is_array($to)) {
            return false;
        }

        $fromScheme = strtolower($from['scheme'] ?? '');
        $toScheme   = strtolower($to['scheme'] ?? '');
        $fromHost   = strtolower($from['host'] ?? '');
        $toHost     = strtolower($to['host'] ?? '');
        $fromPort   = $from['port'] ?? $this->getDefaultPortForScheme($fromScheme);
        $toPort     = $to['port'] ?? $this->getDefaultPortForScheme($toScheme);

        return $fromScheme === $toScheme && $fromHost === $toHost && $fromPort === $toPort;
    }

    private function getDefaultPortForScheme(string $scheme): int
    {
        return 'https' === $scheme ? 443 : 80;
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
