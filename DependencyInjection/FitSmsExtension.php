<?php

namespace Fza\FitSmsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

class FitSmsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config = $this->processConfiguration($configuration, $configs);

        $fitSMSGateway = $container->findDefinition('fitsms.gateway');
        $options = $fitSMSGateway->getArgument(0);
        $validOptions = array(
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
        );
        foreach($validOptions as $key) {
            $options[$key] = $config[$key];
        }
        $fitSMSGateway->replaceArgument(0, $options);

        $this->addClassesToCompile( array(
            'fza\\FitSmsBundle\\SMS',
            'fza\\FitSmsBundle\\FitSMS\\Gateway',
            'fza\\FitSmsBundle\\FitSMS\\Response',
            'fza\\FitSmsBundle\\Helper\\NumberHelper',
        ) );
    }
}
