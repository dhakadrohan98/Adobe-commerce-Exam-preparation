<?php

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;

class AdobeConsoleConfiguration
{
    private Project $project;

    /**
     * Return Project
     *
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * Set Project
     *
     * @param Project $project
     */
    public function setProject(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Return first set of Credentials
     *
     * @return Credentials
     * @throws InvalidConfigurationException
     */
    public function getFirstCredential(): Credentials
    {
        $credentials = $this->project->getWorkspace()->getDetails()->getCredentials();
        if (!array_key_exists(0, $credentials)) {
            throw new InvalidConfigurationException(
                __("Adobe I/O Workspace Configuration does not contain credentials for Service Account (JWT)")
            );
        }
        
        return $credentials[0];
    }
}
