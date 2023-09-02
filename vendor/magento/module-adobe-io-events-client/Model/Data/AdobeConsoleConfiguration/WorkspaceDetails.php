<?php

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

class WorkspaceDetails
{
    /**
     * @var Credentials[]
     */
    private array $credentials;

    /**
     * @var Runtime
     */
    private Runtime $runtime;

    /**
     * @return Credentials[]
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * @param Credentials[] $credentials
     */
    public function setCredentials(array $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * @return Runtime
     */
    public function getRuntime(): Runtime
    {
        return $this->runtime;
    }

    /**
     * @param Runtime $runtime
     */
    public function setRuntime(Runtime $runtime): void
    {
        $this->runtime = $runtime;
    }
}
