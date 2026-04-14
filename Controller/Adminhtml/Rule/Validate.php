<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Validate extends Action
{
    const ADMIN_RESOURCE = 'Panth_SmartBadge::rule_save';

    private JsonFactory $jsonFactory;

    public function __construct(Context $context, JsonFactory $jsonFactory)
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $data = $this->getRequest()->getPostValue();

        $errors = [];
        if (empty($data['name'])) {
            $errors[] = __('Rule name is required.');
        }

        if (empty($errors)) {
            return $result->setData(['error' => false]);
        }

        return $result->setData([
            'error' => true,
            'messages' => $errors
        ]);
    }
}
