<?php

namespace Lcouget\CustomAttribute\Test\Unit\Block\Product\View;

use Lcouget\CustomAttribute\Block\Product\View\CustomAttribute as CustomAttributeBlock;
use Lcouget\CustomAttribute\Helper\Config as ConfigHelper;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomAttributeTest extends TestCase
{

    /**
     * @var Context|MockObject
     */
    private $context;


    /**
     * @var MockObject|Product
     */
    private $product;

    /**
     * @var MockObject|AbstractAttribute
     */
    private $abstractAttribute;

    /**
     * @var MockObject|AbstractFrontend
     */
    private $abstractFrontend;

    /**
     * @var MockObject|Attribute
     */
    private $attribute;
    /**
     * @var MockObject|AbstractAttribute
     */
    private $productAttribute;

    /**
     * @var MockObject|ProductAttributeRepositoryInterface
     */
    private $productAttributeRepositoryInterface;

    /**
     * @var MockObject|Registry
     */
    private $coreRegistry;

    /**
     * @var MockObject|ConfigHelper
     */
    private $config;

    /**
     * @var MockObject|CustomAttributeBlock
     */
    private $customAttributeBlock;

    protected function setUp(): void
    {
        $this->context = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->product = $this
            ->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->product
            ->expects($this->any())
            ->method('getAttributes')
            ->willReturn($this->productAttribute);
        $this->product
            ->expects($this->any())
            ->method('hasData')
            ->willReturn(true);

        $this->abstractAttribute = $this
            ->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->abstractAttribute
            ->expects($this->any())
            ->method('getFrontend')
            ->willReturn($this->abstractFrontend);

        $this->abstractFrontend = $this
            ->getMockBuilder(AbstractFrontend::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->abstractFrontend
            ->expects($this->any())
            ->method('getValue')
            ->willReturn($this->product);

        $this->attribute = $this
            ->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attribute
            ->expects($this->any())
            ->method('getStoreLabel')
            ->willReturn('custom-attribute');
        $this->attribute
            ->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('custom-attribute');
        $this->attribute
            ->expects($this->any())
            ->method('getFrontend')
            ->willReturn($this->abstractAttribute);

        $this->productAttributeRepositoryInterface = $this
            ->getMockBuilder(ProductAttributeRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productAttributeRepositoryInterface
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->attribute);

        $this->coreRegistry = $this
            ->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->coreRegistry
            ->expects($this->any())
            ->method('registry')
            ->willReturn($this->product);

        $this->config = $this
            ->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config
            ->expects($this->any())
            ->method('isCustomAttributeEnabled')
            ->willReturn(true);

        $this->customAttributeBlock = new CustomAttributeBlock(
            $this->context,
            $this->productAttributeRepositoryInterface,
            $this->config,
            $this->coreRegistry
        );
    }

    public function testGetProduct()
    {
        $this->assertEquals($this->product, $this->customAttributeBlock->getProduct());
    }

    public function testGetCustomAttribute()
    {
        $data = $this->customAttributeBlock->getCustomAttribute();

        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('value', $data);
        $this->assertArrayHasKey('code', $data);
    }

    public function testIsCustomAttributeEnabled()
    {
        $this->assertTrue($this->customAttributeBlock->isCustomAttributeEnabled());
    }
}
