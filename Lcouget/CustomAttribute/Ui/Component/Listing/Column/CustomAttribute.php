<?php
namespace Lcouget\CustomAttribute\Ui\Component\Listing\Column;

use Lcouget\CustomAttribute\Helper\Constants;
use Lcouget\CustomAttribute\Helper\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CustomAttribute extends Column

{
    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        Config $config,
        array $components = [],
        array $data = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->config = $config;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Column prepare
     *
     * @return void
     * @throws LocalizedException
     * @throws LocalizedException
     */
    public function prepare(): void
    {
        if (!$this->config->isCustomAttributeEnabled()) {
            $this->_data['config']['componentDisabled'] = true;
        }

        parent::prepare();
    }

    /**
     * Column data source prepare
     *
     * @param array $dataSource
     * @return array
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $product = $this->productRepository->getById($item['entity_id']);
                $attrCode = Constants::CUSTOM_ATTRIBUTE_CODE;

                $item[$attrCode] = $product->getCustomAttribute($attrCode) ?
                    'Value: ' . $product->getCustomAttribute($attrCode)->getValue() :
                    '';
            }
        }
        return $dataSource;
    }
}
