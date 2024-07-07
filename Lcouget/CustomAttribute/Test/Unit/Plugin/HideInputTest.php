<?php

namespace Lcouget\CustomAttribute\Test\Unit\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\Form\ProductDataProvider;
use Lcouget\CustomAttribute\Plugin\HideInput;
use Lcouget\CustomAttribute\Helper\Constants;
use Lcouget\CustomAttribute\Helper\Config as ConfigHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HideInputTest extends TestCase
{

    /**
     * @var ProductDataProvider
     */
    private $productDataProvider;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var HideInput
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->productDataProvider = $this
            ->getMockBuilder(ProductDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelper = $this
            ->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new HideInput(
            $this->configHelper
        );
    }

    public function testAfterGetMetaEmpty()
    {
        $this->productDataProvider
            ->expects($this->any())
            ->method('getMeta')
            ->willReturn([]);

        $this->plugin->afterGetMeta($this->productDataProvider, []);
    }

    public function testAfterGetMetaHideColumn()
    {
        $metaData = [
           'product-details' => [
                'children' => [
                    'container_' . Constants::CUSTOM_ATTRIBUTE_CODE => [
                        'children' => [
                            Constants::CUSTOM_ATTRIBUTE_CODE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'visible' => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = [
           'product-details' => [
               'children' => [
                   'container_' . Constants::CUSTOM_ATTRIBUTE_CODE => [
                       'children' => [
                           Constants::CUSTOM_ATTRIBUTE_CODE => [
                               'arguments' => [
                                   'data' => [
                                       'config' => [
                                           'visible' => false
                                       ]
                                   ]
                               ]
                           ]
                       ]
                   ]
               ]
           ]
        ];

        $this->configHelper
            ->expects($this->any())
            ->method('isCustomAttributeEnabled')
            ->willReturn(false);

        $this->productDataProvider
            ->expects($this->any())
            ->method('getMeta')
            ->willReturn($metaData);

        $this->assertEquals($result, $this->plugin->afterGetMeta($this->productDataProvider, $metaData));
    }

    public function testAfterGetMetaShowColumn()
    {
        $metaData = [
            'product-details' => [
                'children' => [
                    'container_' . Constants::CUSTOM_ATTRIBUTE_CODE => [
                        'children' => [
                            Constants::CUSTOM_ATTRIBUTE_CODE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'visible' => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $metaData;

        $this->productDataProvider
            ->expects($this->any())
            ->method('getMeta')
            ->willReturn($metaData);

        $this->configHelper
            ->expects($this->any())
            ->method('isCustomAttributeEnabled')
            ->willReturn(true);

        $this->assertEquals($result, $this->plugin->afterGetMeta($this->productDataProvider, $metaData));
    }
}
