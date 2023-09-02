<?php
namespace Magento\Staging\Model\Event\Manager;

/**
 * Interceptor class for @see \Magento\Staging\Model\Event\Manager
 */
class Interceptor extends \Magento\Staging\Model\Event\Manager implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Event\InvokerInterface $invoker, \Magento\Framework\Event\ConfigInterface $eventConfig, \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory, $bannedEvents = [], $bannedObservers = [])
    {
        $this->___init();
        parent::__construct($invoker, $eventConfig, $versionManagerFactory, $bannedEvents, $bannedObservers);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, array $data = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        return $pluginInfo ? $this->___callPlugins('dispatch', func_get_args(), $pluginInfo) : parent::dispatch($eventName, $data);
    }
}
