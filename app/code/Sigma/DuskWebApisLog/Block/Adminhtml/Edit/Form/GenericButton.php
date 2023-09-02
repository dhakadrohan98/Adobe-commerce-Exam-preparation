<?php
/**
 * @category    Sigma
 * @package     Sigma_DuskWebApisLog
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Block\Adminhtml\Edit\Form;

/**
 * Class GenericButton
 * @package Sigma_DuskWebApisLog
 */
class GenericButton 
{
    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    private $context;

    /**
     * Class Constructor
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context  $context
    ) {
       $this->context = $context;
    }

    /**
     * @return object
     */
    public function getUrlBuilder()
    {
        return $this->context->getUrlBuilder();
    }
}
