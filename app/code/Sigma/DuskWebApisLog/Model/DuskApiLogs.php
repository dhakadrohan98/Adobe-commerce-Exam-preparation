<?php
/**
 * @category    Sigma
 * @package     Sigma_DuskWebApisLog
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Model;

/**
 * Class DuskApiLogs
 */
class DuskApiLogs extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{   

    const ENTITY_ID = 'id';
    const NOROUTE_ENTITY_ID = 'no-route';
    const CACHE_TAG = 'sigma_dusk_api_logs';
    
    public function _construct()
    {
        $this->_init(\Sigma\DuskWebApisLog\Model\ResourceModel\DuskApiLogs::class);
    }

    /**
     * Route
     */
    public function noRoute()
    {
        return $this->load(self::NOROUTE_ENTITY_ID, $this->getIdFieldName());
    }

    /**
     * @return Array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * @return Array
     */
    public function getId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * @return Array
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }
}