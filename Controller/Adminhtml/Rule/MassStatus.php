<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Panth\SmartBadge\Model\ResourceModel\Rule\CollectionFactory;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Psr\Log\LoggerInterface;

class MassStatus extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var RuleResource
     */
    protected $ruleResource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RuleResource $ruleResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        RuleResource $ruleResource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->ruleResource = $ruleResource;
        $this->logger = $logger;
    }

    /**
     * Execute mass status change action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            $status = (int)$this->getRequest()->getParam('status');

            // Validate status parameter
            if (!in_array($status, [0, 1], true)) {
                $this->messageManager->addErrorMessage(
                    __('Invalid status value. Status must be 0 (disabled) or 1 (enabled).')
                );
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('*/*/');
            }

            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();
            $updatedCount = 0;
            $errorCount = 0;

            foreach ($collection as $rule) {
                try {
                    $rule->setIsActive($status);
                    $this->ruleResource->save($rule);
                    $updatedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logger->critical(
                        __('Error updating status for badge rule ID %1: %2', $rule->getId(), $e->getMessage())
                    );
                }
            }

            if ($updatedCount > 0) {
                $statusText = $status ? __('enabled') : __('disabled');
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 badge rule(s) have been %2.', $updatedCount, $statusText)
                );
            }

            if ($errorCount > 0) {
                $this->messageManager->addErrorMessage(
                    __('Failed to update %1 badge rule(s). Please check the logs for details.', $errorCount)
                );
            }

            if ($updatedCount === 0 && $errorCount === 0) {
                $this->messageManager->addWarningMessage(
                    __('No badge rules were updated.')
                );
            }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while updating badge rule status. Please check the logs for details.')
            );
            $this->logger->critical($e);
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
