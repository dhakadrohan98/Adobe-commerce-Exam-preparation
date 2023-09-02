<?php

namespace Cloudways\RestApi\Model\Api;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface;

class Custom
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventManager
     */
    private $_eventManager;

    /**
     * Custom constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        EventManager $eventManager
    )
    {
        $this->logger = $logger;
        $this->_eventManager = $eventManager;
    }

    /**
     * @param $api_endpoint
     * @param $request_body
     * @param $response_body
     * @param $status_code
     * @return string
     */
    public function getData($api_endpoint,$request_body,$response_body,$status_code)
    {
        $response = ['success' => false];
        try {
            // Implement Your Code here
            $response = [
                'success' => true,
                'api_endpoint' => $api_endpoint,
                'request_body' => $request_body,
                'response_body' => $response_body,
                'status_code' =>  $status_code
            ];
       } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
            $this->logger->info($e->getMessage());
        }
        $returnArray = json_encode($response);
        //dispatching event
        $this->_eventManager->dispatch('dusklog_event',
            [
                'record' => $response
            ]
        );
        return $returnArray;
    }
}