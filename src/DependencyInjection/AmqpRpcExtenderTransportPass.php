<?php

namespace Serrvius\AmqpRpcExtender\DependencyInjection;

use Serrvius\AmqpRpcExtender\Transport\AmqpRpcTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AmqpRpcExtenderTransportPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $definition = new Definition(AmqpRpcTransportFactory::class);
        $definition->addTag('messenger.transport_factory');
        $container->setDefinition('messenger.transport.amqp.rpc.extender.factory', $definition);
    }

}