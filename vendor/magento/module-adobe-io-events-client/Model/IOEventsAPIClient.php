<?php

namespace Magento\AdobeIoEventsClient\Model;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\AdobeIoEventsClient\Api\AccessTokenProviderInterface;
use Magento\AdobeIoEventsClient\Exception\NoRegistrationException;
use Magento\AdobeIoEventsClient\Exception\RequestThrottledException;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\EventMetadata;
use Magento\AdobeIoEventsClient\Model\Data\EventMetadataFactory;
use Magento\AdobeIoEventsClient\Model\Data\EventProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RemoteServiceUnavailableException;
use Magento\Framework\Math\Random;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Store\Model\ScopeInterface;

class IOEventsAPIClient
{
    private const XML_PATH_ADOBE_IO_PROVIDER_URL = 'adobe_io_events/integration/adobe_io_provider_url';
    private const XML_PATH_ADOBE_IO_PROVIDER_LIST_URL = 'adobe_io_events/integration/adobe_io_provider_list_url';
    private const XML_PATH_ADOBE_IO_EVENTS_CREATION_URL = 'adobe_io_events/integration/adobe_io_event_creation_url';
    private const XML_PATH_ADOBE_IO_EVENTS_TYPE_LIST_URL = 'adobe_io_events/integration/adobe_io_event_type_list_url';
    private const XML_PATH_ADOBE_IO_EVENTS_TYPE_DELETE_URL = 'adobe_io_events/integration/adobe_io_event_type_delete_url';
    private const XML_ADOBE_IO_PATH_ENVIRONMENT = 'adobe_io_events/integration/adobe_io_environment';

    private const EVENT_INGRESS_URL_PROD = 'https://eventsingress.adobe.io';
    private const EVENT_INGRESS_URL_STAGE = 'https://eventsingress-stage.adobe.io';

    private const API_URL_PROD = 'https://api.adobe.io';
    private const API_URL_STAGE = 'https://api-stage.adobe.io';

    public const ENV_STAGING = 'staging';

    /**
     * @var ResponseFactory
     */
    private ResponseFactory $responseFactory;

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Random
     */
    private Random $mathRandom;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var EventProviderFactory
     */
    private EventProviderFactory $eventProviderFactory;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventMetadataFactory
     */
    private EventMetadataFactory $eventMetadataFactory;

    /**
     * @var AccessTokenProviderInterface
     */
    private AccessTokenProviderInterface $accessTokenProvider;

    /**
     * @param ResponseFactory $responseFactory
     * @param ClientFactory $clientFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Random $mathRandom
     * @param Json $json
     * @param EventProviderFactory $eventProviderFactory
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventMetadataFactory $eventMetadataFactory
     * @param AccessTokenProviderInterface $accessTokenProvider
     */
    public function __construct(
        ResponseFactory $responseFactory,
        ClientFactory $clientFactory,
        ScopeConfigInterface $scopeConfig,
        Random $mathRandom,
        Json $json,
        EventProviderFactory $eventProviderFactory,
        AdobeIOConfigurationProvider $configurationProvider,
        EventMetadataFactory $eventMetadataFactory,
        AccessTokenProviderInterface $accessTokenProvider
    ) {
        $this->responseFactory = $responseFactory;
        $this->clientFactory = $clientFactory;
        $this->scopeConfig = $scopeConfig;
        $this->mathRandom = $mathRandom;
        $this->json = $json;
        $this->eventProviderFactory = $eventProviderFactory;
        $this->configurationProvider = $configurationProvider;
        $this->eventMetadataFactory = $eventMetadataFactory;
        $this->accessTokenProvider = $accessTokenProvider;
    }

    /**
     * @param string $eventCode
     * @param array $payload
     * @return void
     * @throws AuthenticationException
     * @throws NoRegistrationException
     * @throws RemoteServiceUnavailableException
     * @throws RequestThrottledException
     * @throws AuthorizationException
     * @throws LocalizedException
     */
    public function publishEvent(
        string $eventCode,
        array $payload
    ) {
        $accessToken = $this->accessTokenProvider->getAccessToken();
        $configuration = $this->configurationProvider->getConfiguration();

        $provider = $this->configurationProvider->retrieveProvider();
        $uri = $this->scopeConfig->getValue(self::XML_ADOBE_IO_PATH_ENVIRONMENT) === self::ENV_STAGING
            ? self::EVENT_INGRESS_URL_STAGE
            : self::EVENT_INGRESS_URL_PROD;

        $params = [
            "json" => [
                "datacontenttype" => "application/json",
                "specversion" => "1.0",
                "source" => "urn:uuid:" . $provider->getId(),
                "type" => "$eventCode",
                "id" => $this->mathRandom->getUniqueHash("$eventCode"),
                "data" => $payload
            ],
            "headers" => [
                "x-adobe-event-code" => "$eventCode",
                "x-adobe-event-provider-id" => $provider->getId(),
            ]
        ];

        $response = $this->doRequest(Request::HTTP_METHOD_POST, $uri, $accessToken, $configuration, $params);

        if ($response->getStatusCode() == 204) {
            throw new NoRegistrationException();
        }

        if ($response->getStatusCode() == 429) {
            throw new RequestThrottledException();
        }

        if ($response->getStatusCode() == 401) {
            throw new AuthenticationException(new Phrase("Access Token is not valid anymore"));
        }

        if ($response->getStatusCode() != 200) {
            throw new RemoteServiceUnavailableException(new Phrase("Request failed"));
        }
    }

    /**
     * @param string $instanceId
     * @param EventProvider $provider
     * @return EventProvider
     * @throws AlreadyExistsException
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws InputException
     */
    public function createEventProvider(
        string $instanceId,
        EventProvider $provider
    ): EventProvider {
        $accessToken = $this->accessTokenProvider->getAccessToken();
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->withApiURI(
            str_replace(
                ["#{ims_org_id}", "#{project_id}", "#{workspace_id}"],
                [
                    $configuration->getProject()->getOrganization()->getId(),
                    $configuration->getProject()->getId(),
                    $configuration->getProject()->getWorkspace()->getId()
                ],
                $this->scopeConfig->getValue(self::XML_PATH_ADOBE_IO_PROVIDER_URL)
            )
        );

        $params = [
            "json" => [
                "instance_id" => $instanceId,
                "label" => $provider->getLabel(),
                "description" => sprintf("%s (Instance %s)", $provider->getDescription(), $instanceId)
            ]
        ];

        $eventProviderMetadata = $this->configurationProvider->getEventProviderMetadata();
        if ($eventProviderMetadata) {
            $params['json']['provider_metadata'] = $eventProviderMetadata;
        }

        $response = $this->doRequest(Request::HTTP_METHOD_POST, $uri, $accessToken, $configuration, $params);

        if ($response->getStatusCode() == 409) {
            throw new AlreadyExistsException(new Phrase("An event provider with the same instance ID already exists."));
        }

        if ($response->getStatusCode() == 401) {
            throw new AuthenticationException(new Phrase("Access Token is not valid anymore"));
        }

        if ($response->getStatusCode() != 201) {
            throw new InputException(new Phrase($response->getReasonPhrase()));
        }

        $body = $response->getBody()->getContents();
        $data = $this->json->unserialize($body);

        return $this->eventProviderFactory->create(["data" => $data]);
    }

    /**
     * @param EventProvider $provider
     * @param EventMetadata $eventMetadata
     * @return void
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws InputException
     */
    public function createEventMetadata(
        EventProvider $provider,
        EventMetadata $eventMetadata
    ) {
        $accessToken = $this->accessTokenProvider->getAccessToken();
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->getEventMetadataCreationUri($configuration, $provider);

        $params = [
            'json' => $eventMetadata->jsonSerialize()
        ];

        $response = $this->doRequest(Request::HTTP_METHOD_POST, $uri, $accessToken, $configuration, $params);

        if ($response->getStatusCode() == 401) {
            throw new AuthenticationException(new Phrase("Access Token is not valid anymore"));
        }

        if ($response->getStatusCode() != 201) {
            throw new InputException(new Phrase($response->getReasonPhrase()));
        }
    }

    /**
     * @param EventProvider $provider
     * @return array
     * @throws AuthorizationException|NotFoundException
     */
    public function listRegisteredEventMetadata(
        EventProvider $provider
    ): array {
        $accessToken = $this->accessTokenProvider->getAccessToken();
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->getEventMetadataListUri($configuration, $provider);

        $response = $this->doRequest(Request::HTTP_METHOD_GET, $uri, $accessToken, $configuration);

        if ($response->getStatusCode() == 404) {
            throw new NotFoundException(new Phrase("EventMetadata list was not found"));
        }

        $data = $this->json->unserialize($response->getBody()->getContents());
        $eventMetadataList = [];
        foreach ($data["_embedded"]["eventmetadata"] as $eventMetadataData) {
            $eventType = $this->eventMetadataFactory->create(["data" => $eventMetadataData]);

            $eventMetadataList[] = $eventType;
        }

        return $eventMetadataList;
    }

    /**
     * @return EventProvider[]
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws InputException
     * @throws NotFoundException
     */
    public function listEventProvider(): array
    {
        $accessToken = $this->accessTokenProvider->getAccessToken();
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->withApiURI(
            str_replace(
                ["#{ims_org_id}"],
                [
                    $configuration->getProject()->getOrganization()->getId(),
                ],
                $this->scopeConfig->getValue(self::XML_PATH_ADOBE_IO_PROVIDER_LIST_URL)
            )
        );

        $response = $this->doRequest(Request::HTTP_METHOD_GET, $uri, $accessToken, $configuration);

        if ($response->getStatusCode() == 401) {
            throw new AuthenticationException(new Phrase("Access Token is not valid anymore"));
        }

        if ($response->getStatusCode() != 200) {
            throw new InputException(new Phrase($response->getReasonPhrase()));
        }

        $data = $this->json->unserialize($response->getBody()->getContents());
        $providers = [];
        $eventProviderMetadata = $this->configurationProvider->getEventProviderMetadata();
        foreach ($data["_embedded"]["providers"] as $eventMetadataData) {
            if (!$eventProviderMetadata || $eventProviderMetadata == $eventMetadataData["provider_metadata"]) {
                $eventType = $this->eventProviderFactory->create(["data" => $eventMetadataData]);

                $providers[] = $eventType;
            }
        }

        return $providers;
    }

    /**
     * @param EventProvider $provider
     * @param EventMetadata $eventType
     * @return bool
     * @throws AuthorizationException
     */
    public function deleteEventMetadata(
        EventProvider $provider,
        EventMetadata $eventType
    ): bool {
        $accessToken = $this->accessTokenProvider->getAccessToken();
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->getEventMetadataDeleteUri($configuration, $provider, $eventType->getEventCode());

        $response = $this->doRequest(Request::HTTP_METHOD_DELETE, $uri, $accessToken, $configuration);

        return $response->getStatusCode() == 204;
    }

    private function doRequest(
        string $method,
        string $uri,
        TokenResponseInterface $token,
        AdobeConsoleConfiguration $configuration,
        array $params = []
    ): Response {
        $client = $this->clientFactory->create();
        $credentials = $configuration->getFirstCredential();

        if (!array_key_exists('headers', $params)) {
            $params['headers'] = [];
        }

        $params['headers']['x-api-key'] = $credentials->getJwt()->getClientId();
        $params['headers']['Authorization'] = 'Bearer ' . $token->getAccessToken();

        try {
            $response = $client->request($method, $uri, $params);
        } catch (GuzzleException $exception) {
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => sprintf(
                    'Unsuccessful request: `%s %s` resulted in a `%s %s` response:',
                    $method,
                    $uri,
                    $exception->getResponse()->getStatusCode(),
                    $exception->getResponse()->getReasonPhrase()
                ) . PHP_EOL . $exception->getResponse()->getBody()->getContents()
            ]);
        }

        return $response;
    }

    /**
     * Adds a base URI to the path
     *
     * @param string $uri
     * @return string
     */
    private function withApiURI(string $uri): string
    {
        $apiURL = $this->scopeConfig->getValue(self::XML_ADOBE_IO_PATH_ENVIRONMENT) === self::ENV_STAGING
            ? self::API_URL_STAGE
            : self::API_URL_PROD;

        return $apiURL . '/' . $uri;
    }

    /**
     * Compute Event Metadata Delete URI
     *
     * @param AdobeConsoleConfiguration $configuration
     * @param EventProvider $provider
     * @param string $eventCode
     * @return string
     */
    private function getEventMetadataDeleteUri(
        AdobeConsoleConfiguration $configuration,
        EventProvider $provider,
        string $eventCode
    ): string {
        return str_replace(
            ["#{ims_org_id}", "#{project_id}", "#{workspace_id}", "#{provider_id}", "#{event_code}"],
            [
                $configuration->getProject()->getOrganization()->getId(),
                $configuration->getProject()->getId(),
                $configuration->getProject()->getWorkspace()->getId(),
                $provider->getId(),
                $eventCode
            ],
            $this->withApiURI(
                $this->scopeConfig->getValue(
                    self::XML_PATH_ADOBE_IO_EVENTS_TYPE_DELETE_URL,
                    ScopeInterface::SCOPE_STORE
                )
            )
        );
    }

    /**
     * Compute Event Metadata List URI
     *
     * @param AdobeConsoleConfiguration $configuration
     * @param EventProvider $provider
     * @return string
     */
    private function getEventMetadataListUri(AdobeConsoleConfiguration $configuration, EventProvider $provider): string
    {
        return str_replace(
            ["#{ims_org_id}", "#{project_id}", "#{workspace_id}", "#{provider_id}"],
            [
                $configuration->getProject()->getOrganization()->getId(),
                $configuration->getProject()->getId(),
                $configuration->getProject()->getWorkspace()->getId(),
                $provider->getId()
            ],
            $this->withApiURI(
                $this->scopeConfig->getValue(
                    self::XML_PATH_ADOBE_IO_EVENTS_TYPE_LIST_URL,
                    ScopeInterface::SCOPE_STORE
                )
            )
        );
    }

    /**
     * Compute Event Metadata Creation URI
     *
     * @param AdobeConsoleConfiguration $configuration
     * @param EventProvider $provider
     * @return string
     */
    private function getEventMetadataCreationUri(AdobeConsoleConfiguration $configuration, EventProvider $provider): string
    {
        return str_replace(
            ["#{ims_org_id}", "#{project_id}", "#{workspace_id}", "#{provider_id}"],
            [
                $configuration->getProject()->getOrganization()->getId(),
                $configuration->getProject()->getId(),
                $configuration->getProject()->getWorkspace()->getId(),
                $provider->getId()
            ],
            $this->withApiURI(
                $this->scopeConfig->getValue(
                    self::XML_PATH_ADOBE_IO_EVENTS_CREATION_URL,
                    ScopeInterface::SCOPE_STORE
                )
            )
        );
    }
}
