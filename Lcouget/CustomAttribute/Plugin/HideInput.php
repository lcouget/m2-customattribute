<?php

namespace Lcouget\CustomAttribute\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\Form\ProductDataProvider;
use Lcouget\CustomAttribute\Helper\Constants;
use Lcouget\CustomAttribute\Helper\Config;

class HideInput
{

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Modify meta data to hide custom attribute field based on configuration
     *
     * @param ProductDataProvider $subject
     * @param array $meta
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetMeta(ProductDataProvider $subject, array $meta): array
    {
        if (!$this->config->isCustomAttributeEnabled() &&
            isset($meta['product-details']['children']['container_'. Constants::CUSTOM_ATTRIBUTE_CODE->value])) {
            $meta['product-details']
            ['children']['container_'. Constants::CUSTOM_ATTRIBUTE_CODE->value]['children']
            [Constants::CUSTOM_ATTRIBUTE_CODE->value]['arguments']['data']['config']['visible'] = false;
        }

        return $meta;
    }

}
