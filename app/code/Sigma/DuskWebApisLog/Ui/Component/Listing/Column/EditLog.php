<?php
/**
 * @category    Sigma
 * @package     Sigma_DuskWebApisLog
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\UrlInterface;

/**
 * Class EditComplaint
 */
class EditLog extends \Magento\Ui\Component\Listing\Columns\Column
{
    const EDIT_PAGE_URL_PATH ="duskapilogs/index/edit";
    
    const DELETE_PAGE_URL_PATH ="duskapilogs/index/deletelog";

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * Class Constructor
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,   
        array $components = array(),
        array $data = array()) 
    {
       parent::__construct($context, $uiComponentFactory, $components, $data);
       $this->urlBuilder = $urlBuilder;
    }

    /**
     * Add Url Actions to Json data of Ui grid listing Data Source
     */
    public function prepareDataSource(array $dataSource)
    {
        if(isset($dataSource['data']['items'])){
            foreach ($dataSource['data']['items'] as & $item){
                if($item['id']){
                    $item[$this->getData('name')] =[
                        'edit' =>[
                            'label' => __('View'),
                            'href' => $this->urlBuilder->getUrl(
                                    self::EDIT_PAGE_URL_PATH,
                                       ['id' => $item['id']]
                                    )
                        ],
                        'delete' => [
                            'label' => __('Delete'),
                            'href' => $this->urlBuilder->getUrl(
                                    self::DELETE_PAGE_URL_PATH,
                                       ['id' => $item['id']]
                                    ),
                            'post' => true ,
                            'confirm' =>[
                               'title' => __('Delete Log'),
                                'message' => __('Are you sure you want to delete this log?'),
                            ]
                        ]
                    ];                    
                }

            }
            
        }

        return $dataSource;
    }
}
