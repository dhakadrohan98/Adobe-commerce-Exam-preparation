<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Generator;

/**
 * Stores information needed for module files generation
 */
class Module
{
    /**
     * @var string
     */
    private string $vendor;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array
     */
    private array $plugins = [];

    /**
     * @var array
     */
    private array $observerEventPlugin = [];

    /**
     * @var array
     */
    private array $observerEvents = [];

    /**
     * @var array
     */
    private array $dependencies = [];

    /**
     * @param string $vendor
     * @param string $name
     */
    public function __construct(string $vendor, string $name)
    {
        $this->vendor = $vendor;
        $this->name = $name;
    }

    /**
     * @param array $plugins
     * @return void
     */
    public function setPlugins(array $plugins): void
    {
        $this->plugins = $plugins;
    }

    /**
     * @param array $observerEventPlugin
     * @return void
     */
    public function setObserverEventPlugin(array $observerEventPlugin): void
    {
        $this->observerEventPlugin = $observerEventPlugin;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * @return array
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @return array
     */
    public function getObserverEventPlugin(): array
    {
        return $this->observerEventPlugin;
    }

    /**
     * @param array $observerEvents
     * @return void
     */
    public function setObserverEvents(array $observerEvents): void
    {
        $this->observerEvents = $observerEvents;
    }

    /**
     * Returns list of observer events
     *
     * @return array
     */
    public function getObserverEvents(): array
    {
        return $this->observerEvents;
    }

    /**
     * @param array $dependencies
     * @return void
     */
    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }

    /**
     * Returns list of module dependencies
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
