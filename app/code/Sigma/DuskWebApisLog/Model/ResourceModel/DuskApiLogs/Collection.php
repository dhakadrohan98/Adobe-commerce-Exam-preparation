<?php
/**
 * @category    Sigma
 * @package     Sigma_DuskWebApisLog
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Model\ResourceModel\DuskApiLogs;

use Sigma\DuskWebApisLog\Model\ResourceModel\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var $_idFieldName
     */
    protected $_idFieldName = 'id';
    
    /**
     * Class Constructor
     */
    protected function _construct()
    {
        $this->_init(
            \Sigma\DuskWebApisLog\Model\DuskApiLogs::class,
            \Sigma\DuskWebApisLog\Model\ResourceModel\DuskApiLogs::class
        );
    }
}