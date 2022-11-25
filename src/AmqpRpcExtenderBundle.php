<?php

namespace Serrvius\AmqpRpcExtender;

use App\Common\DependencyInjection\AmqpRpcExtenderTransportPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AmqpRpcExtenderBundle extends Bundle
{

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AmqpRpcExtenderTransportPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION,1);
    }

}