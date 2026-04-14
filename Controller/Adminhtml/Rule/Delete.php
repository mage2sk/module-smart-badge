<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\SmartBadge\Model\RuleFactory;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_delete';

    protected $ruleFactory;
    protected $ruleResource;

    public function __construct(
        Context $context,
        RuleFactory $ruleFactory,
        RuleResource $ruleResource
    ) {
        parent::__construct($context);
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $ruleId = $this->getRequest()->getParam('rule_id');

        if ($ruleId) {
            try {
                $rule = $this->ruleFactory->create();
                $this->ruleResource->load($rule, $ruleId);
                $this->ruleResource->delete($rule);

                $this->messageManager->addSuccessMessage(__('Badge rule deleted successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
