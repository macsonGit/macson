<?php

namespace Custom\SermepaBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;


class JMSPaymentSermepaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->process($configuration->getConfigTree(), $configs);

        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('services.xml');

        $container->setParameter('payment.sermepa.username', $config['username']);
        $container->setParameter('payment.sermepa.password', $config['password']);
        $container->setParameter('payment.sermepa.terminal', $config['terminal']);
        $container->setParameter('payment.sermepa.express_checkout.return_url', $config['return_url']);
        $container->setParameter('payment.sermepa.express_checkout.cancel_url', $config['cancel_url']);
        $container->setParameter('payment.sermepa.debug', $config['debug']);
    }
}