<?php

namespace Lcouget\CustomAttribute\Block\Product\View;

use Lcouget\CustomAttribute\Helper\Constants;
use Lcouget\CustomAttribute\Helper\Config;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

class CustomAttribute extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Product
     */
    protected Product|null $product;

    /**
     * @var Registry
     */
    protected Registry|null $coreRegistry;


    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected ProductAttributeRepositoryInterface $productAttributeRepository;


    /**
     * @var Config
     */
    protected Config $config;


    /**
     * @param Context $context
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param Config $config
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        Config $config,
        Registry $registry,
        array $data = []
    ) {

        $this->product = null;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->coreRegistry = $registry;
        $this->config = $config;

        parent::__construct($context, $data);
    }

    /**
     * Get product
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        if (!$this->product) {
            $this->product = $this->coreRegistry->registry('product');
        }

        return $this->product;
    }

    /**
     * Get custom attribute
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCustomAttribute(): array
    {
        $data =[];

        if ($this->isCustomAttributeEnabled()) {
            try {
                $customAttribute = $this->productAttributeRepository->get(Constants::CUSTOM_ATTRIBUTE_CODE);

                $data = [
                    'label' => $customAttribute->getStoreLabel(),
                    'value' => $customAttribute->getFrontend()->getValue($this->getProduct()),
                    'code' => $customAttribute->getAttributeCode(),
                ];

            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                /*throw new NoSuchEntityException(
                    __('Custom attribute "%1" not found', Constants::CUSTOM_ATTRIBUTE_CODE)
                );*/
            }
        }

        return $data;
    }

    /**
     * Check if custom attribute is enabled
     *
     * @return bool
     */
    public function isCustomAttributeEnabled(): bool
    {
        return $this->config->isCustomAttributeEnabled();
    }


}
