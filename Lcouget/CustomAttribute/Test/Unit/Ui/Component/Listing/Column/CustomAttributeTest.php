<?php

namespace Lcouget\CustomAttribute\Test\Unit\Ui\Component\Listing\Column;

use Lcouget\CustomAttribute\Helper\Constants;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\AttributeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Ui\Component\Listing\Columns\Column;
use Lcouget\CustomAttribute\Ui\Component\Listing\Column\CustomAttribute as CustomAttributeUiComponent;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Lcouget\CustomAttribute\Helper\Config as ConfigHelper;

class CustomAttributeTest extends TestCase
{
    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var UiComponentFactory
     */
    private $uiComponentFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductInterface
     */
    private $productInterface;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var AttributeInterface|MockObject
     */
    private $attributeInterface;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var Column
     */
    private $columnUiComponent;

    /**
     * @var CustomAttributeUiComponent
     */
    private $customAttributeUiComponent;

    protected function setUp(): void
    {
        $this->context = $this
            ->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->uiComponentFactory = $this
            ->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder = $this
            ->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeInterface = $this
            ->getMockBuilder(AttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(Constants::CUSTOM_ATTRIBUTE_CODE->value);

        $this->productInterface = $this
            ->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productInterface
            ->expects($this->any())
            ->method('getCustomAttribute')
            ->willReturn($this->attributeInterface);

        $this->productRepository = $this
            ->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository
            ->expects($this->any())
            ->method('getById')
            ->willReturn($this->productInterface);

        $this->configHelper = $this
            ->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configHelper
            ->expects($this->any())
            ->method('isCustomAttributeEnabled')
            ->willReturn(true);

        $this->columnUiComponent = $this
            ->getMockBuilder(Column::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customAttributeUiComponent = new CustomAttributeUiComponent(
            $this->context,
            $this->uiComponentFactory,
            $this->searchCriteriaBuilder,
            $this->productRepository,
            $this->configHelper
        );
    }

    public function testPrepare()
    {
        //ToDo: Improve this test
        $this->assertInstanceOf(Column::class, $this->customAttributeUiComponent);
    }

    public function testPrepareDataSource(): void
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => 1
                    ]
                ]
            ]
        ];

        $result = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => 1,
                        Constants::CUSTOM_ATTRIBUTE_CODE->value => 'Value: ' . Constants::CUSTOM_ATTRIBUTE_CODE->value
                    ]
                ]
            ]
        ];

        $this->assertEquals($result, $this->customAttributeUiComponent->prepareDataSource($dataSource));
        $this->assertEquals([], $this->customAttributeUiComponent->prepareDataSource([]));
    }
}
