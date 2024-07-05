<?php

namespace Lcouget\CustomAttribute\Block\Product\View;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CustomAttribute extends \Magento\Framework\View\Element\Template
{

    private const string CUSTOM_ATTRIBUTE_CODE = 'lcouget_custom_attribute';
    private const string XML_PATH_CUSTOMATTRIBUTE_ENABLE = 'lcouget_customattribute/general_settings/enable';
    /**
     * @var Product
     */
    protected $product = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;


    /**
     * @var ProductRepositoryInterface
     *
     */
    protected $productAttributeRepository;


    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->coreRegistry = $registry;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context, $data);
    }

    public function getProduct()
    {
        if (!$this->product) {
            $this->product = $this->coreRegistry->registry('product');
        }
        return $this->product;
    }

    public function getCustomAttribute()
    {
        $data =[];
        if ($this->isEnabled()) {
            try {
                $customAttribute = $this->productAttributeRepository->get(self::CUSTOM_ATTRIBUTE_CODE);

                $data = [
                    'label' => $customAttribute->getStoreLabel(),
                    'value' => $customAttribute->getFrontend()->getValue($this->getProduct()),
                    'code' => $customAttribute->getAttributeCode(),
                ];

            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                //nothing
            }
        }

        return $data;
    }

    /***
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMATTRIBUTE_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
