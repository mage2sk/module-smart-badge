<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class AssignProducts extends Template
{
    protected $_template = 'Panth_SmartBadge::rule/assign-products.phtml';

    public function getProductGridUrl(): string
    {
        return $this->getUrl('smartbadge/rule/productsgrid');
    }

    public function getCategoryTreeUrl(): string
    {
        return $this->getUrl('smartbadge/rule/categorytree');
    }

    public function getProductUrlSuffix(): string
    {
        return (string) $this->_scopeConfig->getValue(
            'catalog/seo/product_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCategoryUrlSuffix(): string
    {
        return (string) $this->_scopeConfig->getValue(
            'catalog/seo/category_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBaseUrl(): string
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
