<?php

namespace Serrvius\AmqpRpcExtender\Transport;


use Serrvius\AmqpRpcExtender\Serializer\AmqpRpcMessageSerializer;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcCommandStamp;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryResultStamp;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceiver;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpRpcTransport extends AmqpTransport
{

    protected Connection $connection;
    protected SerializerInterface $serializer;
    protected AmqpFactory $amqpFactory;
    protected AmqpReceiver $amqpReceiver;
    protected ?SerializerInterface $amqpSerializer;

    public function __construct(
        Connection               $connection,
        SerializerInterface      $serializer = null,
        AmqpRpcMessageSerializer $amqpSerializer = null
    )
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
        $this->amqpSerializer = $amqpSerializer;

        $this->amqpReceiver = new AmqpRcpReceiver($connection, $serializer, $amqpSerializer);
        $this->amqpReceiver->addTransport($this);

        $this->amqpFactory = new AmqpFactory();

        parent::__construct($connection, $serializer);
    }


    public function get(): iterable
    {
        return $this->amqpReceiver->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getFromQueues(array $queueNames): iterable
    {
        return $this->amqpReceiver->getFromQueues($queueNames);
    }

    /**
     * {@inheritdoc}
     */
    public function ack(Envelope $envelope): void
    {
        $this->amqpReceiver->ack($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Envelope $envelope): void
    {
        parent::reject($envelope);
    }


    public function send(Envelope $envelope): Envelope
    {

        //Command logic
        if ($amqpRpcCommandStamp = $envelope->last(AmqpRpcCommandStamp::class)) {

            $amqpAttributes = [
                'headers' => [
                    'executor' => $amqpRpcCommandStamp->getExecutorName()
                ],
            ];
            if ($amqpRpcCommandStamp->getPriority() > 0) {
                $amqpAttributes['priority'] = $amqpRpcCommandStamp->getPriority();
            }
            $envelope = $envelope->with(new AmqpStamp($amqpRpcCommandStamp->getRoutingKey(), AMQP_NOPARAM, $amqpAttributes));

            //Query logic
        } elseif ($amqpRpcQueryStamp = $envelope->last(AmqpRpcQueryStamp::class)) {
            $queueName = $amqpRpcQueryStamp->getReplayToQueue();
            $correlationId = $amqpRpcQueryStamp->getCorrelationId();


            $amqpAttributes = [
                'headers' => [
                    'executor' => $amqpRpcQueryStamp->getExecutorName()
                ],
                'correlation_id' => $amqpRpcQueryStamp->getCorrelationId(),
                'reply_to' => $amqpRpcQueryStamp->getReplayToQueue()
            ];

            if ($amqpRpcQueryStamp->getPriority() > 0) {
                $amqpAttributes['priority'] = $amqpRpcQueryStamp->getPriority();
            }

            $rpcEnvelope = $envelope->with(new AmqpStamp(
                $amqpRpcQueryStamp->getRoutingKey(),
                AMQP_NOPARAM,
                $amqpAttributes
            ));

            $responseQueue = $this->createResponseQueue($amqpRpcQueryStamp);

            parent::send($rpcEnvelope);


            $startTime = time();

            do {
                /** @var \AMQPEnvelope $amqpEnvelope */
                $amqpEnvelope = $responseQueue->get();
                usleep(250);
            } while ((!$amqpEnvelope || $correlationId != $amqpEnvelope->getCorrelationId()) && time() - $startTime < $amqpRpcQueryStamp->waitingResponseTTL);


            try {
                if (!$amqpEnvelope) {
                    throw new TransportException('Amqp RPC Query - answer TTL expired!');
                }

                $body = $amqpEnvelope->getBody();
                $headers = $amqpEnvelope->getHeaders();

                $respEnvelope = $this->serializer->decode([
                    'body' => false === $body ? '' : $body,
                    // workaround https://github.com/pdezwart/php-amqp/issues/351
                    'headers' => $headers,
                ]);
                /** @var AmqpRpcQueryResultStamp $amqpRpcQueryResponseStamp */
                if (($amqpRpcQueryResponseStamp = $respEnvelope->last(AmqpRpcQueryResultStamp::class))) {
                    $envelope = $envelope->with($amqpRpcQueryResponseStamp);
                    $envelope = $envelope->with(new HandledStamp($amqpRpcQueryResponseStamp->getResults(),
                        'amqp_rpc_query_handler'));
                }

                /** @var AmqpReceivedStamp $amqpReceivedStamp */
                $amqpReceivedStamp = $respEnvelope->last(AmqpReceivedStamp::class);


            } catch (MessageDecodingFailedException $exception) {
                // invalid message of some type
                $this->rejectAmqpEnvelope($amqpEnvelope, $queueName);

                throw $exception;
            }

            //TODO: Need to check the response queue name
            return $envelope->with(new AmqpReceivedStamp($amqpEnvelope, $amqpReceivedStamp ? $amqpReceivedStamp->getQueueName() : $amqpRpcQueryStamp->getRoutingKey()));


        }
        return parent::send($envelope);
    }

    private function rejectAmqpEnvelope(\AMQPEnvelope $amqpEnvelope, string $queueName): void
    {
        try {
            $this->connection->nack($amqpEnvelope, $queueName, \AMQP_NOPARAM);
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    protected function createResponseQueue(AmqpRpcQueryStamp $amqpRpcQueryStamp)
    {
        $responseQueue = $this->amqpFactory->createQueue($this->connection->channel());
        $responseQueue->setName($amqpRpcQueryStamp->getReplayToQueue());
        $responseQueue->setFlags(AMQP_EXCLUSIVE);
        $responseQueue->declareQueue();

        $responseQueue->bind($this->connection->exchange()->getName(), $amqpRpcQueryStamp->getReplayToQueue());


        return $responseQueue;
    }

    /**
     * {@inheritdoc}
     */
    public function setup(): void
    {
        parent::setup();
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCount(): int
    {
        return parent::getMessageCount();
    }


}