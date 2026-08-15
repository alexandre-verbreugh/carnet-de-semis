<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expose route_exists() aux templates.
 *
 * La navigation est ecrite en entier des le depart, mais les ecrans arrivent lot
 * par lot : sans ce test, path() leverait une exception sur les routes pas encore
 * creees. Les entrees apparaissent donc au fur et a mesure, sans template a retoucher.
 */
class RoutingExtension extends AbstractExtension
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('route_exists', $this->routeExists(...)),
        ];
    }

    public function routeExists(string $name): bool
    {
        return null !== $this->router->getRouteCollection()->get($name);
    }
}
