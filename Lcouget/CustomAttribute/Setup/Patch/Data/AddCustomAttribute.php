<?php
namespace Lcouget\CustomAttribute\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\State;
use Magento\Catalog\Model\Config;
use Magento\Eav\Api\AttributeManagementInterface;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCustomAttribute implements DataPatchInterface
{
    const CUSTOM_ATTRIBUTE_CODE = 'lcouget_custom_attribute';
    const ATTRIBUTE_GROUP_GENERAL = 'Product Details';

    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    private $state;

    private $config;

    private $attributeManagement;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        State $state,
        Config $config,
        AttributeManagementInterface $attributeManagement
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->state = $state;
        $this->config = $config;
        $this->attributeManagement = $attributeManagement;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, self::CUSTOM_ATTRIBUTE_CODE,
            [
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Custom Attribute',
                'input' => 'text',
                'class' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'sort_order' => 100,
                'position' => 100,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_for_promo_rules' => false,
                'used_in_product_listing' => true,
                'unique' => false,
            ]
        );

        $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);

        foreach ($attributeSetIds as $attributeSetId) {
            if ($attributeSetId) {
                $groupId = $this->config->getAttributeGroupId($attributeSetId, self::ATTRIBUTE_GROUP_GENERAL);
                $this->attributeManagement->assign(
                    'catalog_product',
                    $attributeSetId,
                    $groupId,
                    self::CUSTOM_ATTRIBUTE_CODE,
                    999
                );
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
