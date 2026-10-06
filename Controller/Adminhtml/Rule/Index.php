<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule';

    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_SmartBadge::badge_rules');
        $resultPage->getConfig()->getTitle()->prepend(__('Smart Badge Rules'));

        return $resultPage;
    }
}
