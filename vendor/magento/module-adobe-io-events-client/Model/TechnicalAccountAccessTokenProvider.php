<?php

namespace Magento\AdobeIoEventsClient\Model;

use Magento\AdobeIms\Model\OAuth\TokenResponse;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterfaceFactory;
use Magento\AdobeIoEventsClient\Api\AccessTokenProviderInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Jwt\JwkFactory;
use Magento\Framework\Jwt\Jws\JwsSignatureJwks;
use Magento\Framework\Jwt\JwtManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\JwtFrameworkAdapter\Model\JwsFactory;
use Magento\Store\Model\ScopeInterface;

class TechnicalAccountAccessTokenProvider implements AccessTokenProviderInterface
{
    public const XML_PATH_IMS_JWT_EXPIRATION_INTERVAL = "adobe_io_events/integration/ims_jwt_expiration_interval";
    public const XML_ADOBE_IO_PATH_ENVIRONMENT = 'adobe_io_events/integration/adobe_io_environment';
    
    public const ENV_STAGING = 'staging';

    private const IMS_JWT_URL_PROD = 'https://adobeid-na1.services.adobe.com/ims/exchange/jwt';
    private const IMS_JWT_URL_STAGE = 'https://adobeid-na1-stg1.services.adobe.com/ims/exchange/jwt';

    private const IMS_BASE_URL_JWT_TOKEN_PROD = 'https://ims-na1.adobelogin.com';
    private const IMS_BASE_URL_JWT_TOKEN_STAGE = 'https://ims-na1-stg1.adobelogin.com';

    /**
     * @var JwtManagerInterface
     */
    private JwtManagerInterface $jwtManager;

    /**
     * @var JwkFactory
     */
    private JwkFactory $jwkFactory;

    /**
     * @var JwsFactory
     */
    private JwsFactory $jwsFactory;

    /**
     * @var CurlFactory
     */
    private CurlFactory $curlFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var TokenResponseInterfaceFactory
     */
    private TokenResponseInterfaceFactory $tokenResponseFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var TokenResponse|null
     */
    private ?TokenResponse $lastToken = null;

    /**
     * @param JwtManagerInterface $jwtManager
     * @param JwkFactory $jwkFactory
     * @param JwsFactory $jwsFactory
     * @param CurlFactory $curlFactory
     * @param Json $json
     * @param TokenResponseInterfaceFactory $tokenResponseFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     * @param AdobeIOConfigurationProvider $configurationProvider
     */
    public function __construct(
        JwtManagerInterface $jwtManager,
        JwkFactory $jwkFactory,
        JwsFactory $jwsFactory,
        CurlFactory $curlFactory,
        Json $json,
        TokenResponseInterfaceFactory $tokenResponseFactory,
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime,
        AdobeIOConfigurationProvider $configurationProvider
    ) {
        $this->jwtManager = $jwtManager;
        $this->jwkFactory = $jwkFactory;
        $this->jwsFactory = $jwsFactory;
        $this->curlFactory = $curlFactory;
        $this->json = $json;
        $this->tokenResponseFactory = $tokenResponseFactory;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * Call IMS to fetch Access Token from Technical Account JWT
     *
     * @return TokenResponse
     * @throws AuthorizationException|InvalidConfigurationException
     */
    public function getAccessToken(): TokenResponse
    {
        if ($this->lastToken != null) {
            return $this->lastToken;
        }

        $privateKey = $this->configurationProvider->getPrivateKey();
        $configuration = $this->configurationProvider->getConfiguration();

        try {
            $jwk = $this->jwkFactory->createSignRs256($privateKey->getData(), null);
        } catch (\Throwable $e) {
            throw new InvalidConfigurationException(
                __('Service Account Private Key is invalid. Error: %1', $e->getMessage())
            );
        }
        $EncSettings = new JwsSignatureJwks($jwk);

        $firstCredentials = $configuration->getFirstCredential();

        $payload = [
            "exp" => $this->getExpirationTimestamp(),
            "iss" => $configuration->getProject()->getOrganization()->getImsOrgId(),
            "sub" => $firstCredentials->getJwt()->getTechnicalAccountId(),
            "aud" => $this->getImsBaseUrlJwtToken() . "/c/" . $firstCredentials->getJwt()->getClientId()
        ];

        foreach ($firstCredentials->getJwt()->getMetaScopes() as $metaScope) {
            $payload[$this->getImsBaseUrlJwtToken() . "/s/" . $metaScope] = true;
        }

        $jws = $this->jwsFactory->create(
            [
                "alg" => "RS256",
            ],
            $this->json->serialize($payload),
            null
        );

        $token = $this->jwtManager->create($jws, $EncSettings);

        $curl = $this->curlFactory->create();

        $curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $curl->addHeader('cache-control', 'no-cache');

        $curl->post(
            $this->getImsJwtUrl(),
            [
                'client_id' => $firstCredentials->getJwt()->getClientId(),
                'client_secret' => $firstCredentials->getJwt()->getClientSecret(),
                'jwt_token' => $token
            ]
        );

        $response = $this->json->unserialize($curl->getBody());

        if (!is_array($response) || empty($response['access_token'])) {
            throw new AuthorizationException(__('Could not login to Adobe IMS.'));
        }

        $this->lastToken =  $this->tokenResponseFactory->create(['data' => $response]);

        return $this->lastToken;
    }

    /**
     * Compute expiration timestamp
     *
     * @return int
     */
    protected function getExpirationTimestamp(): int
    {
        return $this->dateTime->timestamp() + $this->scopeConfig->getValue(
            self::XML_PATH_IMS_JWT_EXPIRATION_INTERVAL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve IMS Base URL
     *
     * @return string
     */
    private function getImsBaseUrlJwtToken(): string
    {
        return $this->scopeConfig->getValue(self::XML_ADOBE_IO_PATH_ENVIRONMENT) === self::ENV_STAGING
            ? self::IMS_BASE_URL_JWT_TOKEN_STAGE
            : self::IMS_BASE_URL_JWT_TOKEN_PROD;
    }

    /**
     * Retrieve IMS JWT Exchange URL
     *
     * @return string
     */
    private function getImsJwtUrl(): string
    {
        return $this->scopeConfig->getValue(self::XML_ADOBE_IO_PATH_ENVIRONMENT) === self::ENV_STAGING
            ? self::IMS_JWT_URL_STAGE
            : self::IMS_JWT_URL_PROD;
    }
}
