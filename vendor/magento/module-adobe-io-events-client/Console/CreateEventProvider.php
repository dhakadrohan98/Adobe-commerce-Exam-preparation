<?php

namespace Magento\AdobeIoEventsClient\Console;

use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataRegistry;
use Magento\AdobeIoEventsClient\Model\IOEventsAPIClient;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateEventProvider extends Command
{
    public const COMMAND_NAME = 'events:create-event-provider';

    public const OPTION_PROVIDER_LABEL = 'label';
    public const OPTION_PROVIDER_DESCRIPTION = 'description';

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventMetadataRegistry
     */
    private EventMetadataRegistry $eventMetadataRegistry;

    /**
     * @var IOEventsAPIClient
     */
    private IOEventsAPIClient $IOEventsAPIClient;

    /**
     * @var EventProviderFactory
     */
    private EventProviderFactory $eventProviderFactory;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventMetadataRegistry $eventMetadataRegistry
     * @param IOEventsAPIClient $IOEventsAPIClient
     * @param EventProviderFactory $eventProviderFactory
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        EventMetadataRegistry $eventMetadataRegistry,
        IOEventsAPIClient $IOEventsAPIClient,
        EventProviderFactory $eventProviderFactory
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->eventMetadataRegistry = $eventMetadataRegistry;
        $this->IOEventsAPIClient = $IOEventsAPIClient;
        $this->eventProviderFactory = $eventProviderFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(
            "Create a custom event provider in Adobe I/O Events for this instance. ".
            "If you do not specify the label and description options, they must be defined in the " .
            "system " . EventMetadataRegistry::PATH_TO_IO_EVENTS_DECLARATION . " file."
        );
        $this->setAliases(['events:provider:create ']);
        $this->addOption(
            self::OPTION_PROVIDER_LABEL,
            null,
            InputOption::VALUE_OPTIONAL,
            'A label to define your custom provider.'
        );
        $this->addOption(
            self::OPTION_PROVIDER_DESCRIPTION,
            null,
            InputOption::VALUE_OPTIONAL,
            'A description of your provider.'
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void|null
     * @throws AlreadyExistsException
     * @throws AuthorizationException
     * @throws InputException
     * @throws AuthenticationException
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = $this->configurationProvider->retrieveProvider();
        $instanceId = $this->configurationProvider->retrieveInstanceId();

        if ($provider !== null) {
            $output->writeln("Already found an event provider configured with ID " . $provider->getId());
            return Cli::RETURN_FAILURE;
        }

        $output->writeln("No event provider found, a new event provider will be created");

        try {
            $provider = $this->IOEventsAPIClient->createEventProvider(
                $instanceId,
                $this->getProvider($input)
            );
        } catch (LocalizedException $exception) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                $exception->getMessage()
            ));

            return Cli::RETURN_FAILURE;
        }

        $this->configurationProvider->saveProvider($provider);
        $output->writeln("A new event provider has been created with ID " . $provider->getId());

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Creates provider object.
     * If input option provider-label is not empty than creates provider from options otherwise creates
     * provider from the configuration file EventMetadataRegistry::PATH_TO_IO_EVENTS_DECLARATION
     *
     * @param InputInterface $input
     * @return EventProviderInterface
     */
    private function getProvider(InputInterface $input): EventProviderInterface
    {
        $providerLabel = $input->getOption(self::OPTION_PROVIDER_LABEL);
        if (!empty($providerLabel)) {
            $provider = $this->eventProviderFactory->create([
                'data' => [
                    'label' => $providerLabel,
                    'description' => $input->getOption(self::OPTION_PROVIDER_DESCRIPTION)
                ]
            ]);
        } else {
            $provider = $this->eventMetadataRegistry->getDeclaredEventProvider();
        }

        return $provider;
    }
}
