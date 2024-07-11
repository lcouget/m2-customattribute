<?php
namespace Lcouget\CustomAttribute\Model;

use Closure;
use Magento\AsynchronousOperations\Model\OperationProcessor;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\MessageQueue\MessageLockException;
use Magento\Framework\MessageQueue\ConnectionLostException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\MessageQueue\CallbackInvoker;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\MessageQueue\LockInterface;
use Magento\Framework\MessageQueue\MessageController;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Consumer used to process OperationInterface messages.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassUpdateCustomAttribute implements ConsumerInterface
{
    /**
     * @var CallbackInvoker
     */
    private CallbackInvoker $invoker;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;
    /**
     * @var ConsumerConfigurationInterface
     */
    private ConsumerConfigurationInterface $configuration;
    /**
     * @var MessageController
     */
    private MessageController $messageController;
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var OperationProcessor
     */
    private OperationProcessor $operationProcessor;

    /**
     * @var ProcessQueueMessage
     */
    private ProcessQueueMessage $processQueueMsg;

    /**
     * @var EnvelopeFactory
     */
    private $envelopeFactory;

    /**
     * Initialize dependencies.
     *
     * @param CallbackInvoker $invoker
     * @param ResourceConnection $resource
     * @param MessageController $messageController
     * @param ConsumerConfigurationInterface $configuration
     * @param ProcessQueueMessage $processQueueMsg
     * @param EnvelopeFactory $envelopeFactory
     * @param Logger $logger
     */
    public function __construct(
        CallbackInvoker                $invoker,
        ResourceConnection             $resource,
        MessageController              $messageController,
        ConsumerConfigurationInterface $configuration,
        ProcessQueueMessage            $processQueueMsg,
        EnvelopeFactory                 $envelopeFactory,
        Logger                         $logger
    ) {
        $this->invoker = $invoker;
        $this->resource = $resource;
        $this->messageController = $messageController;
        $this->configuration = $configuration;
        $this->processQueueMsg = $processQueueMsg;
        $this->envelopeFactory = $envelopeFactory;
        $this->logger = $logger;
    }

    /**
     * @param $maxNumberOfMessages
     * @return void
     */
    public function process($maxNumberOfMessages = null): void
    {
        $queue = $this->configuration->getQueue();
        if (!isset($maxNumberOfMessages)) {
            $queue->subscribe($this->getTransactionCallback($queue));
        } else {
            $this->invoker->invoke($queue, $maxNumberOfMessages, $this->getTransactionCallback($queue));
        }
    }
    /**
     * Get transaction callback. This handles the case of async.
     *
     * @param QueueInterface $queue
     * @return Closure
     */
    private function getTransactionCallback(QueueInterface $queue): Closure
    {
        return function (EnvelopeInterface $message) use ($queue) {
            /** @var LockInterface $lock */
            $lock = null;
            try {
                $lock = $this->messageController->lock($message, $this->configuration->getConsumerName());
                $responseBody = $message->getBody();
                $data = $this->processQueueMsg->process($responseBody);

                $responseMessage = $this->envelopeFactory->create(
                    [
                        'body' => $responseBody,
                        'properties' => $message->getProperties(),
                    ]
                );

                if (in_array($data['msg'], ['success'])) {
                    $queue->acknowledge($responseMessage); // send acknowledge to queue
                } else {
                    $queue->reject($responseMessage); // if get error in message process
                }

                //$queue->acknowledge($responseMessage); // send acknowledge to queue
            } catch (MessageLockException $exception) {
                $queue->acknowledge($message);
            } catch (ConnectionLostException $e) {
                $queue->acknowledge($message);
                if ($lock) {
                    $this->resource->getConnection()
                        ->delete($this->resource->getTableName('queue_lock'), ['id = ?' => $lock->getId()]);
                }
            } catch (NotFoundException $e) {
                $queue->acknowledge($message);
                $this->logger->warning($e->getMessage());
            } catch (\Exception $e) {
                $queue->reject($message, false, $e->getMessage());
                $queue->acknowledge($message);
                if ($lock) {
                    $this->resource->getConnection()
                        ->delete($this->resource->getTableName('queue_lock'), ['id = ?' => $lock->getId()]);
                }
            }
        };
    }
}
