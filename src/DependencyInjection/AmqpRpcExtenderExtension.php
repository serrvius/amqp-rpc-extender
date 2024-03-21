<?php

namespace Serrvius\AmqpRpcExtender\DependencyInjection;

use Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcCommandExecutor;
use Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcQueryExecutor;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcCommandInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcQueryInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcRequestInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcTraceableInterface;
use Serrvius\AmqpRpcExtender\Middleware\AmqpRpcTraceMiddleware;
use Serrvius\AmqpRpcExtender\Traceable\AmqpRpcTraceableInfo;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

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

        $container->register('amqp.rpc.traceable.info.default', AmqpRpcTraceableInfo::class);
        $container->registerForAutoconfiguration(AmqpRpcTraceableInterface::class)
            ->addTag('amqp.rpc.traceable.instance');

        $container->setAlias(
            AmqpRpcTraceableInterface::class,
            'amqp.rpc.traceable.info.default'
        )->setPublic(true);

        $container->register('messenger.amqp.rpc.middleware.trace', AmqpRpcTraceMiddleware::class)
            ->setArgument(0, new Reference('amqp.rpc.traceable.info.default'));
    }

    protected function registerAnnotationExecutors(ContainerBuilder $container)
    {
        //Register query executors tags
        $container->registerAttributeForAutoconfiguration(AsAmqpRpcQueryExecutor::class,
            static function(ChildDefinition $definition, AsAmqpRpcQueryExecutor $attribute): void {
                $tagAttributes = get_object_vars($attribute);
                $definition->addTag('messenger.amqp.rpc.query.executor', $tagAttributes);
            }
        );

        //Register command executors tags
        $container->registerAttributeForAutoconfiguration(AsAmqpRpcCommandExecutor::class,
            static function(ChildDefinition $definition, AsAmqpRpcCommandExecutor $attribute): void {
                $tagAttributes = get_object_vars($attribute);
                $definition->addTag('messenger.amqp.rpc.command.executor', $tagAttributes);
            }
        );
    }
}