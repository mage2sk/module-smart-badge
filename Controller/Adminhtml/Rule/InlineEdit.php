<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\SmartBadge\Model\RuleFactory;
use Panth\SmartBadge\Model\ResourceModel\Rule as RuleResource;
use Psr\Log\LoggerInterface;

class InlineEdit extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

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
     * @param JsonFactory $jsonFactory
     * @param RuleFactory $ruleFactory
     * @param RuleResource $ruleResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        RuleFactory $ruleFactory,
        RuleResource $ruleResource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->logger = $logger;
    }

    /**
     * Execute inline edit
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $ruleId) {
            try {
                $rule = $this->ruleFactory->create();
                $this->ruleResource->load($rule, $ruleId);

                if (!$rule->getId()) {
                    $messages[] = __('Rule with ID "%1" does not exist.', $ruleId);
                    $error = true;
                    continue;
                }

                $ruleData = $postItems[$ruleId];

                // Validate and sanitize data
                if (isset($ruleData['is_active'])) {
                    $ruleData['is_active'] = (int)$ruleData['is_active'];
                }

                if (isset($ruleData['priority'])) {
                    $ruleData['priority'] = (int)$ruleData['priority'];
                    if ($ruleData['priority'] < 0) {
                        $messages[] = __('Priority must be a non-negative number for rule ID "%1".', $ruleId);
                        $error = true;
                        continue;
                    }
                }

                if (isset($ruleData['name']) && empty(trim($ruleData['name']))) {
                    $messages[] = __('Rule name cannot be empty for rule ID "%1".', $ruleId);
                    $error = true;
                    continue;
                }

                if (isset($ruleData['badge_color']) && !empty($ruleData['badge_color'])) {
                    // Validate hex color format
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $ruleData['badge_color'])) {
                        $messages[] = __('Invalid color format for rule ID "%1". Use hex format (e.g., #FF0000).', $ruleId);
                        $error = true;
                        continue;
                    }
                }

                $rule->setData(array_merge($rule->getData(), $ruleData));
                $this->ruleResource->save($rule);

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = __('[Rule ID: %1] %2', $ruleId, $e->getMessage());
                $error = true;
                $this->logger->critical($e);
            } catch (\RuntimeException $e) {
                $messages[] = __('[Rule ID: %1] %2', $ruleId, $e->getMessage());
                $error = true;
                $this->logger->critical($e);
            } catch (\Exception $e) {
                $messages[] = __('[Rule ID: %1] Something went wrong while saving the rule.', $ruleId);
                $error = true;
                $this->logger->critical($e);
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
