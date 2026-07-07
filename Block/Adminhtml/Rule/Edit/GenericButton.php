<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Widget\Context;

abstract class GenericButton
{
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getRuleId()
    {
        return $this->context->getRequest()->getParam('rule_id');
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
