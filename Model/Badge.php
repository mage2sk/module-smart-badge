<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Model;

use Magento\Framework\Model\AbstractModel;

class Badge extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Panth\SmartBadge\Model\ResourceModel\Badge::class);
    }

    public function getName(): ?string
    {
        return $this->getData('name');
    }

    public function getType(): ?string
    {
        return $this->getData('type');
    }

    public function getLabelText(): ?string
    {
        return $this->getData('label_text');
    }

    public function getBackgroundColor(): ?string
    {
        return $this->getData('background_color');
    }

    public function getTextColor(): ?string
    {
        return $this->getData('text_color');
    }

    public function getPosition(): ?string
    {
        return $this->getData('position');
    }

    public function getPriority(): int
    {
        return (int)$this->getData('priority');
    }

    public function isActive(): bool
    {
        return (bool)$this->getData('is_active');
    }

    public function getCssClass(): ?string
    {
        return $this->getData('css_class');
    }

    public function getAnimation(): ?string
    {
        return $this->getData('animation');
    }
}
