<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\Rule\Rule;
use Magento\AdobeIoEventsClient\Console\CreateEventProvider;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for subscribing to events
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventSubscribeCommand extends Command
{
    private const ARGUMENT_EVENT_CODE = 'event-code';
    private const OPTION_FIELDS = 'fields';
    private const OPTION_FORCE = 'force';
    private const OPTION_PARENT = 'parent';
    private const OPTION_RULES = 'rules';
    private const RULE_FORMAT = 'field|operator|value';

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventSubscriberInterface
     */
    private EventSubscriberInterface $eventSubscriber;

    /**
     * @var EventFactory
     */
    private EventFactory $eventFactory;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventSubscriberInterface $eventSubscriber
     * @param EventFactory $eventFactory
     * @param string|null $name
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        EventSubscriberInterface $eventSubscriber,
        EventFactory $eventFactory,
        string $name = null
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->eventSubscriber = $eventSubscriber;
        $this->eventFactory = $eventFactory;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('events:subscribe')
            ->setDescription('Subscribes to the event')
            ->addArgument(
                self::ARGUMENT_EVENT_CODE,
                InputArgument::REQUIRED,
                'Event code'
            )
            ->addOption(
                self::OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Forces the specified event to be subscribed, even if it hasn\'t been defined locally.'
            )
            ->addOption(
                self::OPTION_FIELDS,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'The list of fields in the event data payload.'
            )
            ->addOption(
                self::OPTION_PARENT,
                null,
                InputOption::VALUE_REQUIRED,
                'The parent event code for an event subscription with rules.'
            )
            ->addOption(
                self::OPTION_RULES,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf(
                    'The list of rules for the event subscription, where each rule is formatted as "%s".',
                    self::RULE_FORMAT
                )
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if (!$this->configurationProvider->isConfigured()) {
                $output->writeln(
                    sprintf(
                        "<error>No event provider is configured, please run bin/magento %s</error>",
                        CreateEventProvider::COMMAND_NAME
                    )
                );
                return Cli::RETURN_FAILURE;
            }

            $eventCode = $input->getArgument(self::ARGUMENT_EVENT_CODE);
            $fields = $input->getOption(self::OPTION_FIELDS);
            $isForced = $input->getOption(self::OPTION_FORCE);
            $parent = $input->getOption(self::OPTION_PARENT);
            $rules = $input->getOption(self::OPTION_RULES);

            if (empty($fields)) {
                $output->writeln('<error>You must specify at least one field.</error>');
                return Cli::RETURN_FAILURE;
            }

            if (empty($parent) != empty($rules)) {
                $output->writeln('<error>The "parent" and "rules" options must be used together.</error>');
                return Cli::RETURN_FAILURE;
            }

            $event = $this->eventFactory->create([
                Event::EVENT_NAME => $eventCode,
                Event::EVENT_FIELDS => $fields,
                Event::EVENT_PARENT => $parent,
                Event::EVENT_RULES => $this->convertRules($rules)
            ]);

            $this->eventSubscriber->subscribe($event, $isForced);
            $output->writeln(sprintf('The subscription %s was successfully created', $event->getName()));

            if ($isForced) {
                $output->writeln(
                    'You must generate or regenerate the AdobeCommerceEvents module and compile after ' .
                    'forcing a subscription. Run the following commands:' . PHP_EOL .
                    'bin/magento events:generate:module' . PHP_EOL . 'bin/magento setup:di:compile'
                );
            }
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }

        return CLI::RETURN_SUCCESS;
    }

    /**
     * Converts an array of strings represented rules to an array with the rule structure expected for Event objects.
     *
     * Example:
     *      ['field_id|operator|value'] => [[field=>'field_id', operator=>'operator', value=>'value']]
     *
     * @param array $rules
     * @return array
     * @throws \Exception
     */
    private function convertRules(array $rules): array
    {
        $convertedRules = [];

        foreach ($rules as $rule) {
            $ruleComponents = explode('|', trim($rule, '\'\"'), 3);
            if (count($ruleComponents) != 3) {
                throw new \Exception(
                    sprintf(
                        'Input rules must be formatted as "%s"',
                        self::RULE_FORMAT
                    )
                );
            }

            $convertedRules[] = array_combine(
                [Rule::RULE_FIELD, Rule::RULE_OPERATOR, Rule::RULE_VALUE],
                $ruleComponents
            );
        }

        return $convertedRules;
    }
}
