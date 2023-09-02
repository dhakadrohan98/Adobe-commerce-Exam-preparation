<?php

namespace Magento\AdobeIoEventsClient\Api;

interface EventMetadataInterface
{
    /**
     * @return string[]
     */
    public function jsonSerialize(): array;

    /**
     * @return string
     */
    public function getEventCode(): string;


    /**
     * @return string
     */
    public function getDescription(): string;


    /**
     * @return string
     */
    public function getLabel(): string;
}
