<?php
/**
 * Created by PhpStorm.
 * User: jbritts
 * Date: 3/5/19
 * Time: 4:40 PM
 */
namespace MagentoEse\MsiInventorySampleDataDemo\Controller\Adminhtml\Index;


class Indexed extends \Magento\Backend\App\Action
{

    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $_pageFactory;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory)
    {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        echo "Index Page";
        exit;
    }

}


