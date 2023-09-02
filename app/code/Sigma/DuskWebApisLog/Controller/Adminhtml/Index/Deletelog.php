<?php
/**
 * @category    Sigma
 * @package     Sigma_DuskWebApisLog
 * @copyright   Copyright (c) Sigma Infosolutions (https://www.sigmainfo.net/)
 * @license     https://www.sigmainfo.net/LICENSE.txt
 */
namespace Sigma\DuskWebApisLog\Controller\Adminhtml\Complaints;

/**
 * Class Delete
 */
class Deletelog extends \Magento\Backend\App\Action
{      
    /**
     * @var \Sigma\DuskWebApisLog\Model\DuskApiLogsFactory
     */
    private $duskApiLogFactory;

    /**
     * Class constructor.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Sigma\DuskWebApisLog\Model\DuskApiLogsFactory $duskApiLogFactory
    ) {
        $this->duskApiLogFactory = $duskApiLogFactory;
        parent::__construct($context);
    }
    
    /**
     * Main Function
     */
    public function execute() 
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        
        if($id){
            $duskApiLogModel = $this->duskApiLogFactory->create();
            $duskApiLogModel->load($id);

            if($duskApiLogModel->getId()){
                try{
                    $duskApiLogModel->delete();
                    $this->messageManager->addSuccessMessage(__('The log has been delete successfully'));
                } catch (\Exception $ex) {
                    $this->messageManager->addErrorMessage(__('Something wento to wrong while Delete'));
                }

                return $resultRedirect->setPath('*/*/index');
            }
        }

        $this->messageManager->addErrorMessage(__('The log does not exits'));

        return $resultRedirect->setPath('*/*/index');
    }
}
