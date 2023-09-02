<?php

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

class Workspace
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
    private string $title;

    /**
     * @var string
     */
    private string $actionUrl;

    /**
     * @var string
     */
    private string $appUrl;

    /**
     * @var WorkspaceDetails
     */
    private WorkspaceDetails $details;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

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
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getActionUrl(): string
    {
        return $this->actionUrl;
    }

    /**
     * @param string $actionUrl
     */
    public function setActionUrl(string $actionUrl): void
    {
        $this->actionUrl = $actionUrl;
    }

    /**
     * @return string
     */
    public function getAppUrl(): string
    {
        return $this->appUrl;
    }

    /**
     * @param string $appUrl
     */
    public function setAppUrl(string $appUrl): void
    {
        $this->appUrl = $appUrl;
    }

    /**
     * @return WorkspaceDetails
     */
    public function getDetails(): WorkspaceDetails
    {
        return $this->details;
    }

    /**
     * @param WorkspaceDetails $details
     */
    public function setDetails(WorkspaceDetails $details): void
    {
        $this->details = $details;
    }
}
