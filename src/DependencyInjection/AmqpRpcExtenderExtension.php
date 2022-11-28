<?php

namespace Serrvius\AmqpRpcExtender\DependencyInjection;

use Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcCommandExecutor;
use Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcQueryExecutor;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcCommandInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcQueryInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcRequestInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AmqpRpcExtenderExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(AmqpRpcRequestInterface::class)
            ->addTag('messenger.amqp.rpc.request');

        $container->registerForAutoconfiguration(AmqpRpcQueryInterface::class)
            ->addTag('messenger.amqp.rpc.query.executor');

        $container->registerForAutoconfiguration(AmqpRpcCommandInterface::class)
            ->addTag('messenger.amqp.rpc.command.executor');

        $this->registerAnnotationExecutors($container);

    }

    protected function registerAnnotationExecutors(ContainerBuilder $container)
    {
        //Register query executors tags
        $container->registerAttributeForAutoconfiguration(AsAmqpRpcQueryExecutor::class,
            static function (ChildDefinition $definition, AsAmqpRpcQueryExecutor $attribute): void {
                $tagAttributes = get_object_vars($attribute);
                $definition->addTag('messenger.amqp.rpc.query.executor', $tagAttributes);
            });

        //Register command executors tags
        $container->registerAttributeForAutoconfiguration(AsAmqpRpcCommandExecutor::class,
            static function (ChildDefinition $definition, AsAmqpRpcCommandExecutor $attribute): void {
                $tagAttributes = get_object_vars($attribute);
                $definition->addTag('messenger.amqp.rpc.command.executor', $tagAttributes);
            });
    }


}