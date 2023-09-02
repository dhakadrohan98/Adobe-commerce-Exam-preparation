<?php

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

class RuntimeNamespace
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $auth;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getAuth(): string
    {
        return $this->auth;
    }

    /**
     * @param string $auth
     */
    public function setAuth(string $auth): void
    {
        $this->auth = $auth;
    }
}
