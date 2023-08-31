<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests\fixtures;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfonycasts\DynamicForms\Tests\fixtures\Enum\DynamicTestMeal;
use Twig\Environment;

class DynamicFormsTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function form(Environment $twig, FormFactoryInterface $formFactory, Request $request): Response
    {
        $form = $formFactory->create(TestDynamicForm::class, [
            'meal' => DynamicTestMeal::Breakfast,
        ]);
        $form->handleRequest($request);

        return new Response($twig->render('form.html.twig', [
            'form' => $form->createView(),
            'isFormValid' => $form->isSubmitted() && $form->isValid(),
        ]));
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'foo000',
            'http_method_override' => false,
            'test' => true,
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
        ]);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('form', '/form')->controller('kernel::form');
    }
}
