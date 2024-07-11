<?php

namespace Lcouget\CustomAttribute\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if custom attribute is enabled
     *
     * @return bool
     */
    public function isCustomAttributeEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            Constants::XML_PATH_CUSTOMATTRIBUTE_ENABLE->value
        );
    }

}
