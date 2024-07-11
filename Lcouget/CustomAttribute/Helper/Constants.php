<?php
namespace Lcouget\CustomAttribute\Helper;

enum Constants: string
{
    case CUSTOM_ATTRIBUTE_CODE = 'lcouget_custom_attribute';
    case ATTR_VALUE = 'value';
    case QUEUE_TOPIC = 'lcouget.customattribute.topic';
    case ATTR_ENABLE = 'enable';
    case XML_PATH_CUSTOMATTRIBUTE_ENABLE = 'lcouget_customattribute/general_settings/enable';
    case ATTR_DISABLE = 'disable';
    case ATTR_ASYNC = 'async';
    case ATTR_PRODUCT_SKU = 'sku';
}
