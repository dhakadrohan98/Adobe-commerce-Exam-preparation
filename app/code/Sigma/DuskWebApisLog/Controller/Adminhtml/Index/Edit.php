<?php
/**
 * @category    Sigma
 * @package     Sigma_Customercomplaints
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Controller\Adminhtml\Index;

/**
 * Class Edit
 */
class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @var \Sigma\DuskWebApisLog\Model\DuskApiLogsFactory
     */
    private $duskApiLogFactory;
    
    /**
     * Class constructor.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Sigma\DuskWebApisLog\Model\DuskApiLogsFactory $duskApiLogFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry  
    ) {
        $this->duskApiLogFactory = $duskApiLogFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * Main Function
     */
    public function execute() 
    {
        $duskApiLogModel= $this->duskApiLogFactory->create();

        $id = $this->getRequest()->getParam('id');
        if($id){
            $duskApiLogModel->load($id);
            if(!$duskApiLogModel->getId()){
               $resultRedirect =  $this->resultRedirectFactory->create();
               return $resultRedirect->setPath('*/*/index');
            }            
        }
        
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->setKeywords(__('View Log'));
        /*$resultPage->setActiveMenu('Sigma_Customercomplaints::list_sm_complaints');  */ 
        $resultPage->getConfig()->getTitle()->prepend('View Log');
        $pageTitltPrefix = __('View Log');
        $resultPage->getConfig()->getTitle()->prepend($pageTitltPrefix);

        return $resultPage;
    }
}
