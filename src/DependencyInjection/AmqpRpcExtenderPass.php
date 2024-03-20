<?php

namespace Serrvius\AmqpRpcExtender\DependencyInjection;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcCommandInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcQueryInterface;
use Serrvius\AmqpRpcExtender\Serializer\AmqpRpcMessageSerializer;
use Serrvius\AmqpRpcExtender\Serializer\AmqpRpcSerializer;
use Serrvius\AmqpRpcExtender\Transport\AmqpRpcTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AmqpRpcExtenderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $amqpRpcFactoryDefinition = new Definition(AmqpRpcTransportFactory::class);
        $amqpRpcFactoryDefinition->addTag('messenger.transport_factory');
        $amqpRpcFactoryDefinition->setArgument(0, new Reference('messenger.transport.rpc.symfony_serializer'));

        $container->setDefinition('messenger.transport.amqp.rpc.factory', $amqpRpcFactoryDefinition);

        if ($container->hasDefinition('messenger.transport.amqp.factory')) {
            $amqpDefinition = $container->getDefinition('messenger.transport.amqp.factory');
            $amqpDefinition->setFactory(new Reference('messenger.transport.amqp.rpc.factory'));

            $container->removeDefinition('messenger.transport.amqp.factory');
            $container->setDefinition('messenger.transport.amqp.factory', $amqpDefinition);
        }

        $defaultMessengerSerializer = new Definition(AmqpRpcSerializer::class);
        $defaultMessengerSerializer->setArgument(0, null);
        $defaultMessengerSerializer->setArgument(1, 'json');
        # Preventing came_case_to_snake_case converting
        $defaultMessengerSerializer->setArgument(2, ['name_converter' => null]);
        $container->setDefinition('messenger.transport.rpc.default.symfony_serializer', $defaultMessengerSerializer);

        $serializerDefinition = new Definition(AmqpRpcMessageSerializer::class);
        $serializerDefinition->setArgument(0, new Reference('messenger.transport.rpc.default.symfony_serializer'));

        //        $errorDetailsStampListener = new Definition(AmqpAddErrorDetailsStampListener::class);
        //        $errorDetailsStampListener->addTag('kernel.event_subscriber');
        //        $container->set('messenger.failure.add_error_details_stamp_listener', $errorDetailsStampListener);

        $queryExecutors = $this->registerExecutorServiceLocator(
            'messenger.amqp.rpc.query.executor',
            AmqpRpcQueryInterface::class,
            'executorName', $container
        );

        $commandExecutors = $this->registerExecutorServiceLocator(
            'messenger.amqp.rpc.command.executor',
            AmqpRpcCommandInterface::class,
            'executorName',
            $container
        );

        $serializerDefinition->setArgument(1,
            ServiceLocatorTagPass::register($container, $queryExecutors)
        );
        $serializerDefinition->setArgument(2,
            ServiceLocatorTagPass::register($container, $commandExecutors)
        );

        $container->setDefinition('messenger.transport.rpc.symfony_serializer', $serializerDefinition);
    }

    protected function registerExecutorServiceLocator(
        string $executorTag,
        string $instanceOf,
        string $indexMethod,
        ContainerBuilder $container
    ): array {
        $executorServices = [];
        foreach ($container->findTaggedServiceIds($executorTag) as $executorClass => $attributes) {
            $exRef = $container->getReflectionClass($executorClass);
            if ($exRef->isSubclassOf($instanceOf) && $exRef->hasMethod($indexMethod)) {
                $method = $exRef->getMethod($indexMethod);
                $name = $method->invoke($exRef);
                if (!$container->hasDefinition($executorClass)) {
                    $executorDefinition = new Definition($executorClass);
                    $container->setDefinition($executorClass, $executorDefinition);
                }
                $executorServices[$name] = new Reference($executorClass);
            } else {
                //Extract name from Annotation param
                $name = current(array_map(function($params) {
                    return $params['name'] ?? null;
                }, $attributes));
                if ($name) {
                    $executorServices[$name] = new Reference($executorClass);
                } else {
                    $executorServices[] = new Reference($executorClass);
                }
            }
        }

        return $executorServices;
    }
}