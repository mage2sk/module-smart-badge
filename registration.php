<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Smart Product Badge & Label System
 *
 * Automatically displays beautiful badges on products to increase urgency and conversions
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Panth_SmartBadge',
    __DIR__
);
