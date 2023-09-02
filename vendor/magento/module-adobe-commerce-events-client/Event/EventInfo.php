<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Laminas\Code\Reflection\ClassReflection;
use Magento\AdobeCommerceEventsClient\Event\Collector\AggregatedEventList;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Util\ClassToArrayConverter;
use Magento\AdobeCommerceEventsClient\Util\ReflectionHelper;
use Magento\AdobeCommerceEventsClient\Util\EventCodeConverter;
use Psr\Log\LoggerInterface;

/**
 * Returns event info
 */
class EventInfo
{
    public const NESTED_LEVEL = ClassToArrayConverter::NESTED_LEVEL;

    /**
     * @var EventValidatorInterface
     */
    private EventValidatorInterface $eventCodeValidator;

    /**
     * @var EventCodeConverter
     */
    private EventCodeConverter $codeConverter;

    /**
     * @var AggregatedEventList
     */
    private AggregatedEventList $aggregatedEventList;

    /**
     * @var ReflectionHelper
     */
    private ReflectionHelper $reflectionHelper;

    /**
     * @var ClassToArrayConverter
     */
    private ClassToArrayConverter $classToArrayConverter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EventValidatorInterface $eventCodeValidator
     * @param EventCodeConverter $codeConverter
     * @param AggregatedEventList $aggregatedEventList
     * @param ReflectionHelper $reflectionHelper
     * @param ClassToArrayConverter $classToArrayConverter
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventValidatorInterface $eventCodeValidator,
        EventCodeConverter $codeConverter,
        AggregatedEventList $aggregatedEventList,
        ReflectionHelper $reflectionHelper,
        ClassToArrayConverter $classToArrayConverter,
        LoggerInterface $logger
    ) {
        $this->eventCodeValidator = $eventCodeValidator;
        $this->codeConverter = $codeConverter;
        $this->aggregatedEventList = $aggregatedEventList;
        $this->reflectionHelper = $reflectionHelper;
        $this->classToArrayConverter = $classToArrayConverter;
        $this->logger = $logger;
    }

    /**
     * Returns payload info for given event.
     *
     * @param Event $event
     * @param int $nestedLevel
     * @return array
     * @throws EventException if information can not be obtained
     * @throws ValidatorException if event name has wrong format
     */
    public function getInfo(Event $event, int $nestedLevel = self::NESTED_LEVEL): array
    {
        $this->eventCodeValidator->validate($event);

        if (strpos($event->getName(), EventSubscriber::EVENT_TYPE_OBSERVER) === 0) {
            return $this->getInfoForObserverEvent($event->getName(), $nestedLevel);
        }

        $className = $this->getClassNameFromEventName($event->getName());
        try {
            $interfaceReflection = new ClassReflection($className);
            $methodName = $this->codeConverter->extractMethodName($event->getName());
            $methodReflection = $interfaceReflection->getMethod($methodName);
        } catch (\ReflectionException $e) {
            $this->logger->error(sprintf(
                'Event %s is not defined: %s',
                $event->getName(),
                $e->getMessage()
            ));
            throw new EventException(__('Cannot get details for event %1', $event->getName()), $e);
        }

        if (strpos($className, 'ResourceModel') !== false) {
            $returnType = str_replace('\ResourceModel', '', $className);
        } else {
            $returnType = $this->reflectionHelper->getReturnType($methodReflection, $interfaceReflection);
        }

        if ($returnType === 'void') {
            $result = [];
        } elseif (in_array($returnType, ['bool', 'boolean'])) {
            $result = $this->getReturnBasedOnParameters($methodReflection);
        } else {
            $isArray = $this->reflectionHelper->isArray($returnType);
            if ($isArray) {
                $returnType = $this->reflectionHelper->arrayTypeToSingle($returnType);
            }

            if ($this->reflectionHelper->isSimple($returnType)) {
                $result[] = $returnType;
            } else {
                $result = $this->classToArrayConverter->convert($returnType, $nestedLevel);
            }

            if ($isArray) {
                $result = [$result];
            }
        }

        return $result;
    }

    /**
     * Returns event info in json format
     *
     * @param Event $event
     * @param int $nestedLevel
     * @return string
     * @throws EventException|ValidatorException
     */
    public function getJsonExample(Event $event, int $nestedLevel = self::NESTED_LEVEL): string
    {
        return stripslashes(json_encode($this->getInfo($event, $nestedLevel), JSON_PRETTY_PRINT));
    }

    /**
     * Add Interface suffix to `api` type plugins
     *
     * @param string $eventName
     * @return string
     */
    private function getClassNameFromEventName(string $eventName): string
    {
        $className = $this->codeConverter->convertToFqcn($eventName);
        if (strpos($eventName, 'resource_model') === false && strpos($eventName, '.api.') !== false) {
            $className .= 'Interface';
        }

        return $className;
    }

    /**
     * Returns info for observer event type
     *
     * @param string $eventName
     * @param int $nestedLevel
     * @return array
     * @throws EventException
     */
    public function getInfoForObserverEvent(string $eventName, int $nestedLevel = self::NESTED_LEVEL): array
    {
        $eventList = $this->aggregatedEventList->getList();
        if (!isset($eventList[$eventName])) {
            throw new EventException(__('Cannot get details about event %1', $eventName));
        }

        return $this->classToArrayConverter->convert(
            $eventList[$eventName]->getEventClassEmitter(),
            $nestedLevel
        );
    }

    /**
     * Returns result based on method parameters in case when plugin method returns bool
     *
     * @param $methodReflection
     * @param int $nestedLevel
     * @return array
     */
    public function getReturnBasedOnParameters($methodReflection, int $nestedLevel = self::NESTED_LEVEL): array
    {
        $methodParams = $this->reflectionHelper->getMethodParameters($methodReflection);

        $result = [];
        foreach ($methodParams as $param) {
            if ($this->reflectionHelper->isSimple($param['type'])) {
                $result[$param['name']] = $param['type'];
            } else {
                $result[$param['name']] = $this->classToArrayConverter->convert($param['type'], $nestedLevel);
            }
        }

        return $result;
    }
}
