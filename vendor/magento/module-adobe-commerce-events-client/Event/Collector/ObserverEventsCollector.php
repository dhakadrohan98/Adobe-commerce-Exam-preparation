<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\AdobeCommerceEventsClient\Event\EventSubscriber;
use Magento\AdobeCommerceEventsClient\Util\FileOperator;
use Magento\Framework\App\Utility\ReflectionClassFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use SplFileInfo;

/**
 * Collects observer events list
 */
class ObserverEventsCollector implements CollectorInterface
{
    /**
     * Array of events for Magento\Framework\Model\AbstractModel class
     *
     * @var array
     */
    private array $abstractModelEvents = [
        '_save_commit_after',
        '_save_after',
        '_delete_after',
        '_delete_commit_after',
    ];

    /**
     * @var FileOperator
     */
    private FileOperator $fileOperator;

    /**
     * @var DriverInterface
     */
    private DriverInterface $filesystem;

    /**
     * @var NameFetcher
     */
    private NameFetcher $nameFetcher;

    /**
     * @var EventDataFactory
     */
    private EventDataFactory $eventDataFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ReflectionClassFactory
     */
    private ReflectionClassFactory $reflectionClassFactory;

    /**
     * @var string
     */
    private string $excludeDirPattern;

    /**
     * @param FileOperator $fileOperator
     * @param DriverInterface $filesystem
     * @param NameFetcher $nameFetcher
     * @param EventDataFactory $eventDataFactory
     * @param LoggerInterface $logger
     * @param ReflectionClassFactory $reflectionClassFactory
     * @param string $excludeDirPattern
     */
    public function __construct(
        FileOperator $fileOperator,
        DriverInterface $filesystem,
        NameFetcher $nameFetcher,
        EventDataFactory $eventDataFactory,
        LoggerInterface $logger,
        ReflectionClassFactory $reflectionClassFactory,
        string $excludeDirPattern = '/^((?!test|Test|dev).)*$/'
    ) {
        $this->fileOperator = $fileOperator;
        $this->filesystem = $filesystem;
        $this->nameFetcher = $nameFetcher;
        $this->eventDataFactory = $eventDataFactory;
        $this->logger = $logger;
        $this->reflectionClassFactory = $reflectionClassFactory;
        $this->excludeDirPattern = $excludeDirPattern;
    }

    /**
     * @inheritDoc
     */
    public function collect(string $modulePath): array
    {
        $result = [];

        $regexIterator = $this->fileOperator->getRecursiveFileIterator(
            $modulePath,
            ['/\.php$/', $this->excludeDirPattern]
        );

        foreach ($regexIterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            try {
                $fileContent = $this->filesystem->fileGetContents($fileInfo->getPathname());
                if (strpos($fileContent, '$_eventPrefix') !== false) {
                    $result = array_merge($result, $this->collectEventsForEventPrefixes($fileInfo, $fileContent));
                } else if (strpos($fileContent, '->dispatch(') !== false) {
                    $result = array_merge($result, $this->collectEventsFromDispatchMethod($fileInfo, $fileContent));
                }
            } catch (FileSystemException $e) {
                $this->logger->error(sprintf(
                    'Unable to get file content during observer events collecting. File %s. Error: %s',
                    $fileInfo->getPathname(),
                    $e->getMessage()
                ));
                continue;
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Unable to collect observer events from the file %s. Error: %s',
                    $fileInfo->getPathname(),
                    $e->getMessage()
                ));
                continue;
            }
        }

        return $result;
    }

    /**
     * Collects events for classes that contains $_eventPrefix variable
     * and instance of Magento\Framework\Model\AbstractModel.
     * If the class is not an instance of mentioned above class we can't generate event codes for it.
     *
     * @param SplFileInfo $fileInfo
     * @param string $fileContent
     * @return array
     * @throws \Exception
     */
    private function collectEventsForEventPrefixes(SplFileInfo $fileInfo, string $fileContent): array
    {
        $events = [];

        $className = $this->nameFetcher->getNameFromFile($fileInfo, $fileContent);
        $refClass = $this->reflectionClassFactory->create($className);

        preg_match('/\$_eventPrefix\s=\s\'(?<eventPrefix>.*?)\';/im', $fileContent, $matches);
        if (!isset($matches['eventPrefix'])) {
            throw new \Exception('Event prefix name cannot be fetched from the file: ' . $fileInfo->getPathname());
        }

        $prefix = EventSubscriber::EVENT_TYPE_OBSERVER . '.' . $matches['eventPrefix'];
        if ($refClass->isSubclassOf(AbstractModel::class)) {
            foreach ($this->abstractModelEvents as $eventSuffix) {
                $eventName = $prefix . $eventSuffix;
                $events[$eventName] = $this->eventDataFactory->create([
                    EventData::EVENT_NAME => $eventName,
                    EventData::EVENT_CLASS_EMITTER => $className,
                ]);
            }
        }

        return $events;
    }

    /**
     * Parses and returns array of event names from dispatch methods.
     *
     * @param SplFileInfo $fileInfo
     * @param string $fileContent
     * @return array
     * @throws \Exception
     */
    private function collectEventsFromDispatchMethod(SplFileInfo $fileInfo, string $fileContent): array
    {
        $events = [];

        preg_match_all('/->dispatch\([^\)\.]*?\n?[^\)\.]*?\'(?<eventName>.*?)\'/im', $fileContent, $matches);

        if (!empty($matches['eventName'])) {
            $className = $this->nameFetcher->getNameFromFile($fileInfo, $fileContent);
            foreach ($matches['eventName'] as $eventName) {
                if (strpos($eventName, '_before') === false) {
                    $eventName = EventSubscriber::EVENT_TYPE_OBSERVER . '.' . $eventName;
                    $events[$eventName] = $this->eventDataFactory->create([
                        EventData::EVENT_NAME => $eventName,
                        EventData::EVENT_CLASS_EMITTER => $className,
                    ]);
                }
            }
        }

        return $events;
    }
}
