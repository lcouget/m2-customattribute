<?php

declare(strict_types=1);

namespace Lcouget\CustomAttribute\Console;

use Magento\Catalog\Model\ResourceModel\Attribute;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CustomAttribute extends Command
{
    const CUSTOM_ATTRIBUTE_CODE = 'lcouget_custom_attribute';
    const XML_PATH_CUSTOMATTRIBUTE_ENABLE = 'lcouget_customattribute/general_settings/enable';
    const ATTR_PRODUCT_SKU = 'sku';
    const ATTR_VALUE = 'value';
    const ATTR_ENABLE = 'enable';
    const ATTR_DISABLE = 'disable';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var Attribute
     */
    protected $attributeResource;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var State
     */
    protected $state;


    /***
     * @param ScopeConfigInterface       $scopeConfig
     * @param WriterInterface            $configWriter
     * @param TypeListInterface          $cacheTypeList
     * @param Pool                       $cacheFrontendPool
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory   $productCollectionFactory
     * @param State                      $state
 */
    public function __construct(
        ScopeConfigInterface        $scopeConfig,
        WriterInterface             $configWriter,
        TypeListInterface           $cacheTypeList,
        Pool                        $cacheFrontendPool,
        ProductRepositoryInterface  $productRepository,
        ProductCollectionFactory    $productCollectionFactory,
        State                       $state
    ) {
        $this->scopeConfig              = $scopeConfig;
        $this->configWriter             = $configWriter;
        $this->cacheTypeList            = $cacheTypeList;
        $this->cacheFrontendPool        = $cacheFrontendPool;
        $this->productRepository        = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->state                    = $state;

        parent::__construct('custom-attribute:manage');
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('custom-attribute:manage');
        $this->setDescription('Custom Attribute Command Example');

        //Arguments
        $this->addArgument(
            self::ATTR_VALUE,
            InputOption::VALUE_OPTIONAL,
            'New custom attribute value'
        );

        //Options
        $this->addOption(
            self::ATTR_ENABLE,
            '-e',
            InputOption::VALUE_NONE,
            'Enable Custom Attribute'
        );
        $this->addOption(
            self::ATTR_DISABLE,
            '-d',
            InputOption::VALUE_NONE,
            'Disable Custom Attribute'
        );
        $this->addOption(
            self::ATTR_PRODUCT_SKU,
            '-s',
            InputOption::VALUE_OPTIONAL,
            'Change custom attribute for selected Product SKU'
        );

        parent::configure();
    }
    /**
     * Executes the command and writes a message to the output.
     *
     * @param InputInterface $input The input interface.
     * @param OutputInterface $output The output interface.
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Check if options or arguments are set
        if (!$this->optionsAreSet($input->getOptions()) && empty($input->getArgument(self::ATTR_VALUE))) {
            $output->writeln('<error>No options or arguments provided. Use --help for more information.</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        //Option to Enable module
        if ($input->getOption(self::ATTR_ENABLE)) {
            if ($this->isCustomAttributeEnabled()) {
                $output->writeln('<info>Module is already enabled.</info>');
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }

            $output->writeln('<info>Enabling module!</info>');
            $this->toogleEnable(true);
            $this->clearCache('config');
            $output->writeln('<info>Module is now enabled.</info>');

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        //Option to Disable module
        if ($input->getOption(self::ATTR_DISABLE)) {
            if (!$this->isCustomAttributeEnabled()) {
                $output->writeln('<info>Module is already disabled.</info>');
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }

            $output->writeln('<info>Disabling module!</info>');
            $this->toogleEnable(false);
            $this->clearCache('config');
            $output->writeln('<info>Module is now disabled.</info>');

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        //Option to Update Product custom attribute by SKU with passed value
        if ($input->getOption(self::ATTR_PRODUCT_SKU)) {
            $output->writeln(
                '<info>Provided product SKU is `' .
                $input->getOption(self::ATTR_PRODUCT_SKU) .
                '`</info>'
            );

            $output->writeln('<info>Setting value...</info>');

            try {
                $this->updateProductBySku(
                    $input->getOption(self::ATTR_PRODUCT_SKU),
                    $input->getArgument(self::ATTR_VALUE)[0]
                );

                $output->writeln(
                    '<info>Custom attribute updated successfully for product with SKU `' .
                    $input->getOption(self::ATTR_PRODUCT_SKU) .
                    '`.</info>'
                );

            } catch (LocalizedException|CouldNotSaveException|NoSuchEntityException $e) {
                $output->writeln(
                    '<error>' . $e->getMessage() . '</error>'
                );
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        //Default option: Update all Products custom attribute with passed value
        if ($input->getArgument(self::ATTR_VALUE)) {
            $output->writeln('<info>Provided value is `' . $input->getArgument(self::ATTR_VALUE)[0] . '`</info>');
            $output->writeln('<info>Setting value...</info>');

            try {
                $this->updateAllProducts($input->getArgument(self::ATTR_VALUE)[0]);
                $output->writeln('<info>Custom attribute updated successfully for all products.</info>');
            } catch (LocalizedException|NoSuchEntityException|CouldNotSaveException $e) {
                $output->writeln(
                    '<error>' . $e->getMessage() . '</error>'
                );
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /***
     * @return bool
     */
    public function isCustomAttributeEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMATTRIBUTE_ENABLE
        );
    }

    /***
     * @param false $enable
     * @return void
     */
    private function toogleEnable(bool $enable = false): void
    {
        $value = $enable ? 1 : 0;
        $this->configWriter->save(
            self::XML_PATH_CUSTOMATTRIBUTE_ENABLE,
            $value
        );
    }

    /***
     * @param $value
     * @return void
     */
    private function updateAllProducts($value): void
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
           throw new LocalizedException($e->getMessage());
        }

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect(self::CUSTOM_ATTRIBUTE_CODE);

        foreach ($productCollection as $product) {
            try {
                $product->setData(self::CUSTOM_ATTRIBUTE_CODE, $value);
                $this->productRepository->save($product);

            } catch (NoSuchEntityException $e) {
                throw new NoSuchEntityException($e->getMessage());
            } catch (CouldNotSaveException $e) {
               throw new CouldNotSaveException($e->getMessage());
            }
        }

    }

    /**
     * @param $sku
     * @param $value
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function updateProductBySku($sku, $value): void
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new LocalizedException($e->getMessage());
        }

        try {
            $product = $this->productRepository->get($sku);
            $product->setData(self::CUSTOM_ATTRIBUTE_CODE, $value);
            $this->productRepository->save($product);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException($e->getMessage());
        } catch (CouldNotSaveException $e) {
            throw new CouldNotSaveException($e->getMessage());
        }
    }

    /***
     * @param $cacheType
     * @return void
     */
    private function clearCache($cacheType): void
    {
        $this->cacheTypeList->cleanType($cacheType);
        $this->cacheFrontendPool->get($cacheType)->getBackend()->clean();
    }

    /***
     * @param $options
     * @return bool
     */
    private function optionsAreSet($options): bool
    {
        $inputOptions = [
            self::ATTR_ENABLE,
            self::ATTR_DISABLE,
            self::ATTR_PRODUCT_SKU
        ];

        foreach ($options as $option) {
            if (in_array($option, $inputOptions) && !empty($option)) {
                return true;
            }
        }
        return false;
    }
}
