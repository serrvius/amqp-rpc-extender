<?php

namespace Serrvius\AmqpRpcExtender\DependencyInjection;

use Serrvius\AmqpRpcExtender\Transport\AmqpRpcTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactory;

class AmqpRpcExtenderTransportPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        $definition = new Definition(AmqpRpcTransportFactory::class);
        $definition->addTag('messenger.transport_factory');
        $container->setDefinition('messenger.transport.amqp.rpc.factory', $definition);


        if($container->hasDefinition('messenger.transport.amqp.factory')){
            $amqpDefinition  = $container->getDefinition('messenger.transport.amqp.factory');
            $amqpDefinition->setFactory(new Reference('messenger.transport.amqp.rpc.factory'));

            $container->removeDefinition('messenger.transport.amqp.factory');
            $container->setDefinition('messenger.transport.amqp.factory',$amqpDefinition);

        }

    }

}