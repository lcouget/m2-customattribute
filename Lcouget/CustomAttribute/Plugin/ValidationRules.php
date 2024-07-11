<?php

namespace Lcouget\CustomAttribute\Plugin;

use Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules;
use Closure;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Lcouget\CustomAttribute\Helper\Constants;

class ValidationRules
{
    /**
     * AroundBuild
     *
     * @param CatalogEavValidationRules $rulesObject
     * @param Closure $proceed
     * @param ProductAttributeInterface $attribute
     * @param array $data
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundBuild(
        CatalogEavValidationRules $rulesObject,
        Closure $proceed,
        ProductAttributeInterface $attribute,
        array $data
    ): array {
        $rules = $proceed($attribute, $data);
        if ($attribute->getAttributeCode() === Constants::CUSTOM_ATTRIBUTE_CODE->value) {

            $rules = [
                'no-whitespace' => true,
                'alphanumeric' => true
            ];
        }
        return $rules;
    }
}
