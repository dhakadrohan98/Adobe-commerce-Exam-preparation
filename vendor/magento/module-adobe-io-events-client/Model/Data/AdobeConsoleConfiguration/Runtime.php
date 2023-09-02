<?php

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

class Runtime
{
    /**
     * @var RuntimeNamespace[]
     */
    private array $namespaces;

    /**
     * @return RuntimeNamespace[]
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * @param RuntimeNamespace[] $namespaces
     */
    public function setNamespaces(array $namespaces): void
    {
        $this->namespaces = $namespaces;
    }
}
