<?php

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

class Credentials
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $integrationType;

    /**
     * @var JWT
     */
    private JWT $jwt;

    /**
     * Return ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Return Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return Integration Type
     *
     * @return string
     */
    public function getIntegrationType(): string
    {
        return $this->integrationType;
    }

    /**
     * Set Integration Type
     *
     * @param string $integrationType
     */
    public function setIntegrationType(string $integrationType): void
    {
        $this->integrationType = $integrationType;
    }

    /**
     * Return JWT
     *
     * @return JWT
     */
    public function getJwt(): JWT
    {
        return $this->jwt;
    }

    /**
     * Set JWT
     *
     * @param JWT $jwt
     */
    public function setJwt(JWT $jwt): void
    {
        $this->jwt = $jwt;
    }
}
