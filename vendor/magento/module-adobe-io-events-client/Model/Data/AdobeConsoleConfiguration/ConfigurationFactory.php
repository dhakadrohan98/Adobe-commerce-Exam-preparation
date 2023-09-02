<?php

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

class ConfigurationFactory
{
    /**
     * @var AdobeConsoleConfigurationFactory
     */
    private AdobeConsoleConfigurationFactory $adobeConsoleConfigurationFactory;

    /**
     * @var CredentialsFactory
     */
    private CredentialsFactory $credentialsFactory;

    /**
     * @var JWTFactory
     */
    private JWTFactory $JWTFactory;

    /**
     * @var OrganizationFactory
     */
    private OrganizationFactory $organizationFactory;

    /**
     * @var ProjectFactory
     */
    private ProjectFactory $projectFactory;

    /**
     * @var RuntimeFactory
     */
    private RuntimeFactory $runtimeFactory;

    /**
     * @var RuntimeNamespaceFactory
     */
    private RuntimeNamespaceFactory $runtimeNamespaceFactory;

    /**
     * @var WorkspaceFactory
     */
    private WorkspaceFactory $workspaceFactory;

    /**
     * @var WorkspaceDetailsFactory
     */
    private WorkspaceDetailsFactory $workspaceDetailsFactory;

    /**
     * @param AdobeConsoleConfigurationFactory $adobeConsoleConfigurationFactory
     * @param CredentialsFactory $credentialsFactory
     * @param JWTFactory $JWTFactory
     * @param OrganizationFactory $organizationFactory
     * @param ProjectFactory $projectFactory
     * @param RuntimeFactory $runtimeFactory
     * @param RuntimeNamespaceFactory $runtimeNamespaceFactory
     * @param WorkspaceFactory $workspaceFactory
     * @param WorkspaceDetailsFactory $workspaceDetailsFactory
     */
    public function __construct(
        AdobeConsoleConfigurationFactory $adobeConsoleConfigurationFactory,
        CredentialsFactory $credentialsFactory,
        JWTFactory $JWTFactory,
        OrganizationFactory $organizationFactory,
        ProjectFactory $projectFactory,
        RuntimeFactory $runtimeFactory,
        RuntimeNamespaceFactory $runtimeNamespaceFactory,
        WorkspaceFactory $workspaceFactory,
        WorkspaceDetailsFactory $workspaceDetailsFactory
    ) {
        $this->adobeConsoleConfigurationFactory = $adobeConsoleConfigurationFactory;
        $this->credentialsFactory = $credentialsFactory;
        $this->JWTFactory = $JWTFactory;
        $this->organizationFactory = $organizationFactory;
        $this->projectFactory = $projectFactory;
        $this->runtimeFactory = $runtimeFactory;
        $this->runtimeNamespaceFactory = $runtimeNamespaceFactory;
        $this->workspaceFactory = $workspaceFactory;
        $this->workspaceDetailsFactory = $workspaceDetailsFactory;
    }

    /**
     * Create Adobe Console Configuration from API Response Data
     *
     * @param array $data
     * @return AdobeConsoleConfiguration
     */
    public function create(array $data): AdobeConsoleConfiguration
    {
        $configuration = $this->adobeConsoleConfigurationFactory->create();

        $projectData = $data["project"];
        $project = $this->projectFactory->create();
        $configuration->setProject($project);
        $project->setId($projectData["id"]);
        $project->setName($projectData["name"]);
        $project->setTitle($projectData["title"]);

        $orgData = $projectData["org"];
        $org = $this->organizationFactory->create();
        $project->setOrganization($org);

        $org->setName($orgData["name"]);
        $org->setId($orgData["id"]);
        $org->setImsOrgId($orgData["ims_org_id"]);

        $workspaceData = $projectData["workspace"];
        $workspace = $this->workspaceFactory->create();
        $workspace->setId($workspaceData["id"]);
        $workspace->setName($workspaceData["name"]);
        $workspace->setTitle($workspaceData["title"]);
        $workspace->setActionUrl($workspaceData["action_url"]);
        $workspace->setAppUrl($workspaceData["app_url"]);
        $project->setWorkspace($workspace);

        $detailsData = $workspaceData["details"];
        $details = $this->workspaceDetailsFactory->create();
        $workspace->setDetails($details);

        $credentialsArray = [];
        foreach ($detailsData["credentials"] as $credentialData) {
            if (!isset($credentialData["jwt"])) {
                continue;
            }

            $credentials = $this->credentialsFactory->create();
            $credentials->setId($credentialData["id"]);
            $credentials->setName($credentialData["name"]);
            $credentials->setIntegrationType($credentialData["integration_type"]);

            $jwtData = $credentialData["jwt"];
            $jwt = $this->JWTFactory->create();
            $jwt->setClientId($jwtData["client_id"]);
            $jwt->setClientSecret($jwtData["client_secret"]);
            $jwt->setTechnicalAccountEmail($jwtData["technical_account_email"]);
            $jwt->setTechnicalAccountId($jwtData["technical_account_id"]);
            $jwt->setMetaScopes($jwtData["meta_scopes"]);
            $credentials->setJwt($jwt);

            $credentialsArray[] = $credentials;
        }
        $details->setCredentials($credentialsArray);

        return $configuration;
    }
}
