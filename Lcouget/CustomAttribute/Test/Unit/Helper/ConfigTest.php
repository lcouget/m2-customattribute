<?php

namespace Lcouget\CustomAttribute\Test\Unit\Helper;

use Lcouget\CustomAttribute\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    protected function setUp(): void
    {
        $this->scopeConfig = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelper = new ConfigHelper($this->scopeConfig);
    }

    public function testIsCustomAttributeDisabled()
    {
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(false);

        $this->assertFalse($this->configHelper->isCustomAttributeEnabled());
    }

    public function testIsCustomAttributeEnabled()
    {
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(true);

        $this->assertTrue($this->configHelper->isCustomAttributeEnabled());
    }
}
