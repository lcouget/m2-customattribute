<?php

declare(strict_types=1);

namespace Lcouget\CustomAttribute\Console;

use Lcouget\CustomAttribute\Helper\Constants;
use Lcouget\CustomAttribute\Helper\Config;
use Magento\Catalog\Model\ResourceModel\Attribute;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CustomAttribute extends Command
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var WriterInterface
     */
    protected WriterInterface $configWriter;

    /**
     * @var TypeListInterface
     */
    protected TypeListInterface $cacheTypeList;

    /**
     * @var Pool
     */
    protected Pool $cacheFrontendPool;

    /**
     * @var Attribute
     */
    protected Attribute $attributeResource;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var ProductCollectionFactory
     */
    protected ProductCollectionFactory $productCollectionFactory;

    /**
     * @var State
     */
    protected State $state;

    /**
     * @var PublisherInterface
     */
    protected PublisherInterface $publisher;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var ProductAction
     */
    private $productAction;


    /***
     * @param Config                     $config
     * @param WriterInterface            $configWriter
     * @param TypeListInterface          $cacheTypeList
     * @param Pool                       $cacheFrontendPool
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory   $productCollectionFactory
     * @param ProductAction              $productAction
     * @param PublisherInterface         $publisher
     * @param JsonHelper                 $jsonHelper
     * @param State                      $state
     */
    public function __construct(
        Config                      $config,
        WriterInterface             $configWriter,
        TypeListInterface           $cacheTypeList,
        Pool                        $cacheFrontendPool,
        ProductRepositoryInterface  $productRepository,
        ProductCollectionFactory    $productCollectionFactory,
        ProductAction               $productAction,
        PublisherInterface          $publisher,
        JsonHelper                  $jsonHelper,
        State                       $state
    ) {
        $this->config                   = $config;
        $this->configWriter             = $configWriter;
        $this->cacheTypeList            = $cacheTypeList;
        $this->cacheFrontendPool        = $cacheFrontendPool;
        $this->productRepository        = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productAction            = $productAction;
        $this->publisher                = $publisher;
        $this->jsonHelper               = $jsonHelper;
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
            Constants::ATTR_VALUE,
            InputOption::VALUE_OPTIONAL,
            'New custom attribute value (Must be alphanumeric with no whitespaces).'
        );

        //Options
        $this->addOption(
            Constants::ATTR_ENABLE,
            '-e',
            InputOption::VALUE_NONE,
            'Enable Custom Attribute for all products'
        );
        $this->addOption(
            Constants::ATTR_DISABLE,
            '-d',
            InputOption::VALUE_NONE,
            'Disable Custom Attribute for all products'
        );
        $this->addOption(
            Constants::ATTR_PRODUCT_SKU,
            '-s',
            InputOption::VALUE_OPTIONAL,
            'Change custom attribute for selected Product SKU'
        );
        $this->addOption(
            Constants::ATTR_ASYNC,
            '-a',
            InputOption::VALUE_NONE,
            'Change custom attribute for all products asynchronously'
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
        if (!$this->optionsAreSet($input->getOptions()) && empty($input->getArgument(Constants::ATTR_VALUE))) {
            $output->writeln(
                '<error>No options or arguments provided. Use --help for more information.</error>'
            );
            return Cli::RETURN_FAILURE;
        }

        //Option to Enable module
        if ($input->getOption(Constants::ATTR_ENABLE)) {
            if ($this->config->isCustomAttributeEnabled()) {
                $output->writeln('<info>Module is already enabled.</info>');
                return Cli::RETURN_FAILURE;
            }

            $output->writeln('<info>Enabling module...</info>');
            $this->toogleEnable(true);
            $this->clearCache('config');
            $output->writeln('<info>Module is now enabled.</info>');

            return Cli::RETURN_SUCCESS;
        }

        //Option to Disable module
        if ($input->getOption(Constants::ATTR_DISABLE)) {
            if (!$this->config->isCustomAttributeEnabled()) {
                $output->writeln('<info>Module is already disabled.</info>');
                return Cli::RETURN_FAILURE;
            }

            $output->writeln('<info>Disabling module...</info>');
            $this->toogleEnable(false);
            $this->clearCache('config');
            $output->writeln('<info>Module is now disabled.</info>');

            return Cli::RETURN_SUCCESS;
        }

        //Option to Update Product custom attribute by SKU with passed value
        if ($input->getOption(Constants::ATTR_PRODUCT_SKU)) {
            if (!$this->config->isCustomAttributeEnabled()) {
                $output->writeln('<info>Module is disabled. Cannot update custom attribute.</info>');
                return Cli::RETURN_FAILURE;
            }

            if (!$this->checkParameterRules($input->getArgument(Constants::ATTR_VALUE)[0])) {
                $output->writeln(
                    '<error>Provided value is not valid. Use --help for more information.</error>'
                );
                return Cli::RETURN_FAILURE;
            }

            $output->writeln(
                '<info>Provided product SKU is `' .
                $input->getOption(Constants::ATTR_PRODUCT_SKU) .
                '`</info>'
            );

            $output->writeln('<info>Setting value...</info>');

            try {
                $this->updateProductBySku(
                    $input->getOption(Constants::ATTR_PRODUCT_SKU),
                    $input->getArgument(Constants::ATTR_VALUE)[0]
                );

                $output->writeln(
                    '<info>Custom attribute updated successfully for product with SKU `' .
                    $input->getOption(Constants::ATTR_PRODUCT_SKU) .
                    '`.</info>'
                );

            } catch (LocalizedException|CouldNotSaveException|NoSuchEntityException $e) {
                $output->writeln(
                    '<error>' . $e->getMessage() . '</error>'
                );
                return Cli::RETURN_FAILURE;
            }

            return Cli::RETURN_SUCCESS;
        }

        //Option to Update all Products custom attribute with passed value asynchronously
        if ($input->getOption(Constants::ATTR_ASYNC)) {
            if (!$this->config->isCustomAttributeEnabled()) {
                $output->writeln('<info>Module is disabled. Cannot update custom attribute.</info>');
                return Cli::RETURN_FAILURE;
            }

            if (!$this->checkParameterRules($input->getArgument(Constants::ATTR_VALUE)[0])) {
                $output->writeln(
                    '<error>Provided value is not valid. Use --help for more information.</error>'
                );
                return Cli::RETURN_FAILURE;
            }

            $this->asyncUpdateAllProducts($input->getArgument(Constants::ATTR_VALUE)[0]);
            return Cli::RETURN_SUCCESS;
        }

        //Default option: Update all Products custom attribute with passed value
        if (!$this->config->isCustomAttributeEnabled()) {
            $output->writeln('<info>Module is disabled. Cannot update custom attribute.</info>');
            return Cli::RETURN_FAILURE;
        }

        if (!$this->checkParameterRules($input->getArgument(Constants::ATTR_VALUE)[0])) {
            $output->writeln(
                '<error>Provided value is not valid. Use --help for more information.</error>'
            );
            return Cli::RETURN_FAILURE;
        }

        $output->writeln(
            '<info>Provided value is `' .
            $input->getArgument(Constants::ATTR_VALUE)[0] .
            '`</info>'
        );

        $output->writeln('<info>Setting value...</info>');

        try {
            $this->updateAllProducts($input->getArgument(Constants::ATTR_VALUE)[0]);
            $output->writeln('<info>Custom attribute updated successfully for all products.</info>');
        } catch (LocalizedException|NoSuchEntityException|CouldNotSaveException $e) {
            $output->writeln(
                '<error>' . $e->getMessage() . '</error>'
            );
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /***
     * Enable/disable custom attribute
     *
     * @param bool $enable
     * @return void
     */
    private function toogleEnable(bool $enable = false): void
    {
        $value = $enable ? 1 : 0;
        $this->configWriter->save(
            Constants::XML_PATH_CUSTOMATTRIBUTE_ENABLE,
            $value
        );
    }

    /**
     * Update all products (Optimized version)
     *
     * @param string $value
     * @return void
     * @throws LocalizedException
     */
    private function updateAllProducts(string $value): void
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new LocalizedException(
                __('An error has occurred while updating the custom attribute. Please try again.')
            );
        }

        try {
            $productCollection = $this->productCollectionFactory->create();
            $productIds = $productCollection->getAllIds();

            $this->productAction->updateAttributes(
                $productIds,
                [Constants::CUSTOM_ATTRIBUTE_CODE => $value],
                0
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('An error has occurred while updating the custom attribute. Please try again.')
            );
        }
    }

    /**
     * @param string $value
     * @return void
     */
    private function asyncUpdateAllProducts(string $value): void
    {
        $publishData = [ 'custom_attribute_value' => $value ];
        $this->publisher->publish(Constants::QUEUE_TOPIC, $this->jsonHelper->jsonEncode($publishData));
    }

    /**
     * Updates product by selected SKU
     *
     * @param string $sku
     * @param string $value
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function updateProductBySku(string $sku, string $value): void
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new LocalizedException(
                __('An error has occurred while updating the custom attribute. Please try again.')
            );
        }

        try {
            $product = $this->productRepository->get($sku);
            $product->setData(Constants::CUSTOM_ATTRIBUTE_CODE, $value);
            $this->productRepository->save($product);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('The product was not found. Please try again.')
            );
        } catch (CouldNotSaveException $e) {
            throw new CouldNotSaveException(
                __('The custom attribute was unable to be saved. Please try again.')
            );
        }
    }

    /***
     * Used to clear cache
     *
     * @param string $cacheType
     * @return void
     */
    private function clearCache(string $cacheType): void
    {
        $this->cacheTypeList->cleanType($cacheType);
        $this->cacheFrontendPool->get($cacheType)->getBackend()->clean();
    }

    /***
     * Check if selected options are set
     *
     * @param array $options
     * @return bool
     */
    private function optionsAreSet(array $options): bool
    {
        $inputOptions = [
            Constants::ATTR_ENABLE,
            Constants::ATTR_DISABLE,
            Constants::ATTR_PRODUCT_SKU
        ];

        foreach ($options as $option) {
            if (in_array($option, $inputOptions) && !empty($option)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set of basic validation rules
     *
     * @param string $parameter
     * @return bool
     */
    private function checkParameterRules(string $parameter): bool
    {
        //check alphanumeric
        if (!preg_match('/^[a-zA-Z0-9]+$/', $parameter) || !ctype_alnum($parameter)) {
            return false;
        }

        //no whitespaces
        if (preg_match('/\s/', $parameter)) {
            return false;
        }
        return true;
    }

}
