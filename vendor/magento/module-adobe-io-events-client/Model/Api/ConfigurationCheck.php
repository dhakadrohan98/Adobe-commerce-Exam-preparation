<?php

namespace Magento\AdobeIoEventsClient\Model\Api;

use Magento\AdobeIoEventsClient\Api\ConfigurationCheckInterface;
use Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterface;
use Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterfaceFactory;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\IOEventsAPIClient;
use Magento\Framework\Exception\NotFoundException;

class ConfigurationCheck implements ConfigurationCheckInterface
{
    /**
     * @var IOEventsAPIClient
     */
    private IOEventsAPIClient $APIClient;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var ConfigurationCheckResultInterfaceFactory
     */
    private ConfigurationCheckResultInterfaceFactory $configurationCheckResultFactory;

    /**
     * @param IOEventsAPIClient $APIClient
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param ConfigurationCheckResultInterfaceFactory $configurationCheckResultFactory
     */
    public function __construct(
        IOEventsAPIClient $APIClient,
        AdobeIOConfigurationProvider $configurationProvider,
        ConfigurationCheckResultInterfaceFactory $configurationCheckResultFactory
    ) {
        $this->APIClient = $APIClient;
        $this->configurationProvider = $configurationProvider;
        $this->configurationCheckResultFactory = $configurationCheckResultFactory;
    }

    /**
     * @return ConfigurationCheckResultInterface
     */
    public function checkConfiguration(): ConfigurationCheckResultInterface
    {
        $data = [];
        $status = 'ok';

        try {
            $this->configurationProvider->getPrivateKey();
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CONFIGURED] = true;
        } catch (NotFoundException $exception) {
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CONFIGURED] = false;
            $status = 'error';
        }

        try {
            $providers =  $this->APIClient->listEventProvider();
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CAN_CONNECT] = true;
        } catch (\Exception $e) {
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CAN_CONNECT] = false;
            $status = 'error';
        }

        $providerId = $this->configurationProvider->retrieveProvider();

        if (is_null($providerId)) {
            $status = 'error';
            $data[ConfigurationCheckResultInterface::PROVIDER_ID_CONFIGURED] = '';
            $data[ConfigurationCheckResultInterface::PROVIDER_ID_VALID] = false;
        } else {
            $data[ConfigurationCheckResultInterface::PROVIDER_ID_CONFIGURED] = $providerId->getId();

            try {
                $events = $this->APIClient->listRegisteredEventMetadata($providerId);
                $data[ConfigurationCheckResultInterface::PROVIDER_ID_VALID] = true;
            } catch (\Exception $e) {
                $data[ConfigurationCheckResultInterface::PROVIDER_ID_VALID] = false;
                $status = 'error';
            }
        }

        $data[ConfigurationCheckResultInterface::STATUS] = $status;

        return $this->configurationCheckResultFactory->create(['data' => $data ]);
    }
}
