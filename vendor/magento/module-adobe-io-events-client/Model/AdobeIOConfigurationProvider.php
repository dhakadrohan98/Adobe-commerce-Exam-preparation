<?php

namespace Magento\AdobeIoEventsClient\Model;

use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\ConfigurationFactory;
use Magento\AdobeIoEventsClient\Model\Data\EventProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\Data\PrivateKey;
use Magento\AdobeIoEventsClient\Model\Data\PrivateKeyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;

class AdobeIOConfigurationProvider
{
    private const XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID = "adobe_io_events/integration/provider_id";
    private const XML_PATH_ADOBE_IO_EVENT_INSTANCE_ID = "adobe_io_events/integration/instance_id";
    private const XML_PATH_ADOBE_IO_SERVICE_ACCOUNT_PRIVATE_KEY = "adobe_io_events/integration/private_key";
    private const XML_PATH_ADOBE_IO_EVENT_CONSOLE_CONFIGURATION = "adobe_io_events/integration/workspace_configuration";
    private const XML_PATH_ADOBE_IO_EVENT_PROVIDER_METADATA = "adobe_io_events/integration/adobe_io_event_provider_metadata";

    /**
     * @var EventProviderFactory
     */
    private EventProviderFactory $eventProviderFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ConfigurationFactory
     */
    private ConfigurationFactory $configurationFactory;

    /**
     * @var PrivateKeyFactory
     */
    private PrivateKeyFactory $privateKeyFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;

    /**
     * @var AdobeConsoleConfiguration|null
     */
    private ?AdobeConsoleConfiguration $configuration = null;

    /**
     * @param EventProviderFactory $eventProviderFactory
     * @param Json $json
     * @param ConfigurationFactory $configurationFactory
     * @param PrivateKeyFactory $privateKeyFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     */
    public function __construct(
        EventProviderFactory $eventProviderFactory,
        Json $json,
        ConfigurationFactory $configurationFactory,
        PrivateKeyFactory $privateKeyFactory,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer
    ) {
        $this->eventProviderFactory = $eventProviderFactory;
        $this->json = $json;
        $this->configurationFactory = $configurationFactory;
        $this->privateKeyFactory = $privateKeyFactory;
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
    }

    /**
     * Retrieve Instance ID
     *
     * @return string
     * @throws NotFoundException
     */
    public function retrieveInstanceId(): string
    {
        $instanceId = $this->scopeConfig->getValue(
            self::XML_PATH_ADOBE_IO_EVENT_INSTANCE_ID,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if (is_array($instanceId) || !$instanceId) {
            throw new NotFoundException(new Phrase("Instance ID not found in configuration"));
        }

        return $instanceId;
    }

    /**
     * Retrieve Event Provider ID
     *
     * @return EventProvider|null
     */
    public function retrieveProvider(): ?EventProvider
    {
        $providerId = $this->scopeConfig->getValue(
            self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if (is_array($providerId) || !$providerId) {
            return null;
        }

        return $this->eventProviderFactory->create(['data' => ['id' => $providerId]]);
    }

    /**
     * Helper function to check if a provider has been configured at all
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        $providerId = $this->scopeConfig->getValue(
            self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if (is_array($providerId) || !$providerId) {
            return false;
        }
        return true;
    }

    /**
     * @return PrivateKey
     * @throws NotFoundException
     */
    public function getPrivateKey(): PrivateKey
    {
        $privateKeyData = $this->scopeConfig->getValue(
            self::XML_PATH_ADOBE_IO_SERVICE_ACCOUNT_PRIVATE_KEY,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if (is_array($privateKeyData) || !$privateKeyData) {
            throw new NotFoundException(new Phrase("Private Key not found in configuration"));
        }

        $privateKey = $this->privateKeyFactory->create();
        $privateKey->setData($privateKeyData);

        return $privateKey;
    }

    /**
     * @return AdobeConsoleConfiguration
     * @throws NotFoundException
     * @throws InvalidConfigurationException
     */
    public function getConfiguration(): AdobeConsoleConfiguration
    {
        if ($this->configuration === null) {
            $configuration = $this->scopeConfig->getValue(
                self::XML_PATH_ADOBE_IO_EVENT_CONSOLE_CONFIGURATION,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );

            if (is_array($configuration) || !$configuration) {
                throw new NotFoundException(new Phrase("Could not find Adobe I/O Workspace Configuration information"));
            }

            try {
                $data = $this->json->unserialize($configuration);
            } catch (\InvalidArgumentException $exception) {
                throw new InvalidConfigurationException(
                    __('Could not fetch Adobe I/O Workspace Configuration: %1', $exception->getMessage())
                );
            }
            $this->configuration = $this->configurationFactory->create($data);
        }

        return $this->configuration;
    }

    /**
     * @param EventProvider $eventProvider
     * @return void
     */
    public function saveProvider(EventProvider $eventProvider)
    {
        $this->writer->save(
            self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID,
            $eventProvider->getId()
        );
    }

    /**
     * @return string
     */
    public function getEventProviderMetadata(): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_METADATA,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if (is_array($value) || !$value) {
            return null;
        }

        return $value;
    }
}
