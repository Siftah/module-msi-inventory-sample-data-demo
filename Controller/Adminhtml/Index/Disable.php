<?php
/**
 * Created by PhpStorm.
 * User: jbritts
 * Date: 3/5/19
 * Time: 4:40 PM
 */
namespace MagentoEse\MsiInventorySampleDataDemo\Controller\Adminhtml\Index;

use MagentoEse\MsiInventorySampleDataDemo\Model\DisableMsiDemoInventory;

class Disable extends \Magento\Backend\App\Action
{

    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $_pageFactory;

    /** @var DisableMsiDemoInventory  */
    protected $disableDemoInventory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        DisableMsiDemoInventory $disableDemoInventory)
    {
        $this->disableDemoInventory = $disableDemoInventory;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->disableDemoInventory->apply();
        echo "MSI Disableddd";
        exit;
    }

}


