<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\View\Resolver;

use Laminas\View\Resolver;
use PHPUnit\Framework\TestCase;

class AggregateResolverTest extends TestCase
{
    public function testAggregateIsEmptyByDefault()
    {
        $resolver = new Resolver\AggregateResolver();
        $this->assertEquals(0, count($resolver));
    }

    public function testCanAttachResolvers()
    {
        $resolver = new Resolver\AggregateResolver();
        $resolver->attach(new Resolver\TemplateMapResolver);
        $this->assertEquals(1, count($resolver));
        $resolver->attach(new Resolver\TemplateMapResolver);
        $this->assertEquals(2, count($resolver));
    }

    public function testReturnsNonFalseValueWhenAtLeastOneResolverSucceeds()
    {
        $resolver = new Resolver\AggregateResolver();
        $resolver->attach(new Resolver\TemplateMapResolver([
            'foo' => 'bar',
        ]));
        $resolver->attach(new Resolver\TemplateMapResolver([
            'bar' => 'baz',
        ]));
        $test = $resolver->resolve('bar');
        $this->assertEquals('baz', $test);
    }

    public function testLastSuccessfulResolverIsNullInitially()
    {
        $resolver = new Resolver\AggregateResolver();
        $this->assertNull($resolver->getLastSuccessfulResolver());
    }

    public function testCanAccessResolverThatLastSucceeded()
    {
        $resolver = new Resolver\AggregateResolver();
        $fooResolver = new Resolver\TemplateMapResolver([
            'foo' => 'bar',
        ]);
        $barResolver = new Resolver\TemplateMapResolver([
            'bar' => 'baz',
        ]);
        $bazResolver = new Resolver\TemplateMapResolver([
            'baz' => 'bat',
        ]);
        $resolver->attach($fooResolver)
                 ->attach($barResolver)
                 ->attach($bazResolver);

        $test = $resolver->resolve('bar');
        $this->assertEquals('baz', $test);
        $this->assertSame($barResolver, $resolver->getLastSuccessfulResolver());
    }

    public function testReturnsFalseWhenNoResolverSucceeds()
    {
        $resolver = new Resolver\AggregateResolver();
        $resolver->attach(new Resolver\TemplateMapResolver([
            'foo' => 'bar',
        ]));
        $this->assertFalse($resolver->resolve('bar'));
        $this->assertEquals(Resolver\AggregateResolver::FAILURE_NOT_FOUND, $resolver->getLastLookupFailure());
    }

    public function testLastSuccessfulResolverIsNullWhenNoResolverSucceeds()
    {
        $resolver    = new Resolver\AggregateResolver();
        $fooResolver = new Resolver\TemplateMapResolver([
            'foo' => 'bar',
        ]);
        $resolver->attach($fooResolver);
        $test = $resolver->resolve('foo');
        $this->assertSame($fooResolver, $resolver->getLastSuccessfulResolver());

        try {
            $test = $resolver->resolve('bar');
            $this->fail('Should not have resolved!');
        } catch (\Exception $e) {
            // exception is expected
        }
        $this->assertNull($resolver->getLastSuccessfulResolver());
    }

    public function testResolvesInOrderOfPriorityProvided()
    {
        $resolver = new Resolver\AggregateResolver();
        $fooResolver = new Resolver\TemplateMapResolver([
            'bar' => 'foo',
        ]);
        $barResolver = new Resolver\TemplateMapResolver([
            'bar' => 'bar',
        ]);
        $bazResolver = new Resolver\TemplateMapResolver([
            'bar' => 'baz',
        ]);
        $resolver->attach($fooResolver, -1)
                 ->attach($barResolver, 100)
                 ->attach($bazResolver);

        $test = $resolver->resolve('bar');
        $this->assertEquals('bar', $test);
    }

    public function testReturnsFalseWhenAttemptingToResolveWhenNoResolversAreAttached()
    {
        $resolver = new Resolver\AggregateResolver();
        $this->assertFalse($resolver->resolve('foo'));
        $this->assertEquals(Resolver\AggregateResolver::FAILURE_NO_RESOLVERS, $resolver->getLastLookupFailure());
    }
}
