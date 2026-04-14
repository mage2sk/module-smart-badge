<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Widget\Context;

abstract class GenericButton
{
    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Return rule ID
     *
     * @return int|null
     */
    public function getRuleId()
    {
        return $this->context->getRequest()->getParam('rule_id');
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
