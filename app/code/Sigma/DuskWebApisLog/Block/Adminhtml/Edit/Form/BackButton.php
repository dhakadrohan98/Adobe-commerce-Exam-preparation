<?php
/**
 * @category    Sigma
 * @package     Sigma_DuskWebApisLog
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Block\Adminhtml\Edit\Form;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class BackButton
 * @package Sigma_DuskWebApisLog
 */
class BackButton  extends GenericButton implements ButtonProviderInterface
{
    /**
     *
     * @return array
     */
    public function getButtonData(): array {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrlBuilder()->getUrl('*/*/index')),
            'class' => 'back',
            'sort_order' => 10
        ];        
    }
}
