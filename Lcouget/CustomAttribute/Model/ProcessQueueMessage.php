<?php

namespace Lcouget\CustomAttribute\Model;

use Lcouget\CustomAttribute\Helper\Constants;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Psr\Log\LoggerInterface as Logger;

/**
 * ProcessQueueMsg Model
 */
class ProcessQueueMessage
{
    /**
     * @var State
     */
    private $state;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    private $productAction;

    /**
     * @var Logger
     */
    private $logger;

    private $jsonHelper;

    /**
     * @param State $state
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductAction $productAction
     * @param JsonHelper $jsonHelper
     * @param Logger $logger
     */
    public function __construct(
        State $state,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollectionFactory,
        ProductAction $productAction,
        JsonHelper $jsonHelper,
        Logger $logger
    ) {
        $this->state = $state;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productAction = $productAction;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
    }

    /**
     * process
     *
     * @param $message
     * @return array
     */
    public function process($message): array
    {
        $data = $this->jsonHelper->jsonDecode($this->jsonHelper->jsonDecode($message));

        try {
            $productCollection = $this->productCollectionFactory->create();
            $productIds = $productCollection->getAllIds();

            $this->productAction->updateAttributes(
                $productIds,
                [Constants::CUSTOM_ATTRIBUTE_CODE => $data['custom_attribute_value']],
                0
            );

            $result = ['msg' => 'success'];

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $result = ['msg' => 'error', 'error' => $e->getMessage()];
        }

        return $result;
    }
}
