<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Magento\AdobeCommerceEventsClient\Event\Config as EventsConfig;
use Magento\AdobeIoEventsClient\Api\AccessTokenProviderInterface;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;

/**
 * Client class for Commerce Events
 */
class Client
{
    /**
     * @var Config
     */
    private EventsConfig $config;

    /**
     * @var AccessTokenProviderInterface
     */
    private AccessTokenProviderInterface $accessTokenProvider;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @param Config $config
     * @param AccessTokenProviderInterface $accessTokenProvider
     * @param ClientFactory $clientFactory
     * @param AdobeIOConfigurationProvider $configurationProvider
     */
    public function __construct(
        EventsConfig $config,
        AccessTokenProviderInterface $accessTokenProvider,
        ClientFactory $clientFactory,
        AdobeIOConfigurationProvider $configurationProvider
    ) {
        $this->config = $config;
        $this->accessTokenProvider = $accessTokenProvider;
        $this->clientFactory = $clientFactory;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * Sends a batch of event data to the Events Service.
     *
     * @param array $messages
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws InvalidConfigurationException
     */
    public function sendEventDataBatch(array $messages): ResponseInterface
    {
        $url = sprintf(
            '%s/v1/publish-batch',
            $this->config->getEndpointUrl(),
        );

        try {
            return $this->doRequest('POST', $url, [
                'http_errors' => false,
                RequestOptions::JSON => [
                    'merchantId' => $this->config->getMerchantId(),
                    'environmentId' => $this->config->getEnvironmentId(),
                    'messages' => $messages,
                    'instanceId' => $this->config->getInstanceId()
                ]
            ]);
        } catch (AuthorizationException|NotFoundException $exception) {
            throw new InvalidConfigurationException(__($exception->getMessage()));
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return ResponseInterface
     * @throws AuthorizationException
     * @throws GuzzleException
     * @throws NotFoundException
     * @throws InvalidConfigurationException
     */
    private function doRequest(
        string $method,
        string $uri,
        array $params = []
    ): ResponseInterface {
        $token = $this->accessTokenProvider->getAccessToken();
        $configuration = $this->configurationProvider->getConfiguration();
        $client = $this->clientFactory->create();

        try {
            $credentials = $configuration->getFirstCredential();
        } catch (\Exception $exception) {
            throw new InvalidConfigurationException(__($exception->getMessage()));
        }

        $params['headers']['x-api-key'] = $credentials->getJwt()->getClientId();
        $params['headers']['Authorization'] = 'Bearer ' . $token->getAccessToken();
        $params['headers']['x-ims-org-id'] = $configuration->getProject()->getOrganization()->getImsOrgId();

        return $client->request($method, $uri, $params);
    }
}
