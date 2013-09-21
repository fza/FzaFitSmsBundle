<?php

namespace Fza\FitSmsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FzaFitSmsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config        = $this->processConfiguration($configuration, $configs);

        $fitSmsGateway = $container->findDefinition('fitsms.gateway');
        $options       = $fitSmsGateway->getArgument(0);
        foreach (array(
            'debug_test',
            'gateway_uri',
            'max_sms_part_count',
            'default_intl_prefix',
            'tracking',
            'username',
            'password',
            'numlock',
            'iplock',
            'from',
        ) as $key) {
            $options[$key] = $config[$key];
        }
        $fitSmsGateway->replaceArgument(0, $options);
    }
}
