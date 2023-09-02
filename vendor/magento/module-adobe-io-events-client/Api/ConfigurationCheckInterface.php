<?php

namespace Magento\AdobeIoEventsClient\Api;

interface ConfigurationCheckInterface
{
    /**
     * @return \Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterface
     */
    public function checkConfiguration(): ConfigurationCheckResultInterface;
}
