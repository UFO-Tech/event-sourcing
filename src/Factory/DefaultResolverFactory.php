<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Factory;

use Ufo\EventSourcing\Contracts\MainResolverFactoryInterface;
use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Resolver\ArrayResolver;
use Ufo\EventSourcing\Resolver\CollectionResolver;
use Ufo\EventSourcing\Resolver\MainResolver;
use Ufo\EventSourcing\Resolver\ObjectResolver;
use Ufo\EventSourcing\Resolver\ScalarResolver;
use Ufo\EventSourcing\Utils\ValueNormalizer;

class DefaultResolverFactory implements MainResolverFactoryInterface
{
    protected const array RESOLVER_CLASSES = [
        ObjectResolver::class,
        CollectionResolver::class,
        ArrayResolver::class,
        ScalarResolver::class,
    ];

    public function create(): MainResolverInterface
    {
        $valueNormalizer = new ValueNormalizer();
        $mainResolver = new MainResolver();

        foreach (self::RESOLVER_CLASSES as $resolverClass) {
            $mainResolver->addResolver(new $resolverClass($mainResolver, $valueNormalizer));
        }

        return $mainResolver;
    }
}