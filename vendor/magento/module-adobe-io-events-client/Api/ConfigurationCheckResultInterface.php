<?php

namespace Magento\AdobeIoEventsClient\Api;

interface ConfigurationCheckResultInterface
{
    const STATUS = 'status';
    const TECHNICAL_SERVICE_ACCOUNT_CONFIGURED = 'technical_service_account_configured';
    const TECHNICAL_SERVICE_ACCOUNT_CAN_CONNECT = 'technical_service_account_can_connect';
    const PROVIDER_ID_CONFIGURED = 'provider_id_configured';
    const PROVIDER_ID_VALID = 'provider_id_valid';

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @return bool
     */
    public function getTechnicalServiceAccountConfigured(): bool;

    /**
     * @return bool
     */
    public function getTechnicalServiceAccountCanConnectToIoEvents(): bool;

    /**
     * @return string
     */
    public function getProviderIdConfigured(): string;

    /**
     * @return bool
     */
    public function getProviderIdValid(): bool;
}
