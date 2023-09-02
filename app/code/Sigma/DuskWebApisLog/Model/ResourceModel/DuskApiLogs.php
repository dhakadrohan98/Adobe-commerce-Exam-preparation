<?php
/**
 * @category    Sigma
 * @package     Sigma_DuskWebApisLog
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Model\ResourceModel;

/**
 * Class DuskApiLogs
 */
class DuskApiLogs extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Class Constructor
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }
    
    /**
     * Class Construct
     */
    public function _construct()
    {
        $this->_init("sigma_dusk_api_logs", "id");
    }
}