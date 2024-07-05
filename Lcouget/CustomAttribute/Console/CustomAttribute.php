<?php
namespace Lcouget\CustomAttribute\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Catalog\Model\ResourceModel\Attribute;
use Magento\Framework\Console\Cli;

class CustomAttribute extends Command
{
    private const string CUSTOM_ATTRIBUTE_CODE = 'lcouget_custom_attribute';
    private const string XML_PATH_CUSTOMATTRIBUTE_ENABLE = 'lcouget_customattribute/general_settings/enable';
    private const string ATTR_PRODUCT_SKU = 'sku';
    private const string ATTR_VALUE = 'value';
    private const string ATTR_ENABLE = 'enable';
    private const string ATTR_DISABLE = 'disable';

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

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

    /***
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * @param string|null $name
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        Attribute $attributeResource
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->attributeResource = $attributeResource;
        $this->cacheFrontendPool = $cacheFrontendPool;

        parent::__construct();
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('customattribute:manage');
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
            '-sku',
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
        //$output->setDecorated(true);

        try {
            if (!$this->optionsAreSet($input->getOptions()) && empty($input->getArgument(self::ATTR_VALUE))) {
                $output->writeln('<error>No options or arguments provided. Use --help for more information.</error>');
                return Cli::RETURN_FAILURE;
            }

            if ($input->getOption(self::ATTR_ENABLE)) {
                if ($this->isEnabled()) {
                    $output->writeln('<info>Module is already enabled.</info>');
                    return Cli::RETURN_FAILURE;
                }

                $exitCode = 0;
                $output->writeln('<info>Enabling module!</info>');
                $this->toogleEnable(true);
                $this->clearCache('config');
                $output->writeln('<info>Module is now enabled.</info>');

                return Cli::RETURN_SUCCESS;
            }

            if ($input->getOption(self::ATTR_DISABLE)) {
                if (!$this->isEnabled()) {
                    $output->writeln('<info>Module is already disabled.</info>');
                    return Cli::RETURN_FAILURE;
                }

                $exitCode = 0;
                $output->writeln('<info>Disabling module!</info>');
                $this->toogleEnable(false);
                $this->clearCache('config');
                $output->writeln('<info>Module is now disabled.</info>');

                return Cli::RETURN_SUCCESS;
            }

            if ($input->getArgument(self::ATTR_VALUE)) {
                $output->writeln('<info>Provided value is `' . $input->getArgument(self::ATTR_VALUE)[0] . '`</info>');
                $output->writeln('<info>Setting value...</info>');
                $exitCode = 0;
                $this->updateAllProducts($input->getArgument(self::ATTR_VALUE)[0]);

                return Cli::RETURN_SUCCESS;
            }

            if ($input->getOption(self::ATTR_PRODUCT_SKU)) {
                $output->writeln(
                    '<info>Provided product SKU is `' .
                    $input->getOption(self::ATTR_PRODUCT_SKU) .
                    '`</info>'
                );

                $output->writeln('<info>Setting value...</info>');

                //ToDo...
                return Cli::RETURN_SUCCESS;
            }
        } catch (\Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /***
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMATTRIBUTE_ENABLE
        );
    }

    /***
     * @param $enable
     * @return void
     */
    private function toogleEnable($enable = false): void
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
        $this->attributeResource->beginTransaction();

        try {
            $products = $this->attributeResource->getProducts();
            foreach ($products as $product) {
                $product->setData(self::CUSTOM_ATTRIBUTE_CODE, $value);
                $this->attributeResource->save($product);
            }
            $this->attributeResource->commit();
            $this->clearCache('catalog_product');
        } catch (\Exception $e) {
            $this->attributeResource->rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    /***
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
