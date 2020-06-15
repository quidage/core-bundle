<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\HttpCache;

use Contao\CoreBundle\EventListener\HttpCache\StripQueryParametersSubscriber;
use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StripQueryParametersSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $subscriber = new StripQueryParametersSubscriber();

        $this->assertSame([Events::PRE_HANDLE => 'preHandle'], $subscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider queryParametersProvider
     */
    public function testQueryParametersAreStrippedCorrectly(array $parameters, array $expectedParameters, array $whitelist = [], array $disabledFromBlacklist = []): void
    {
        $request = Request::create('/', 'GET', $parameters);
        $event = new CacheEvent($this->createMock(CacheInvalidation::class), $request);

        $subscriber = new StripQueryParametersSubscriber($whitelist);
        $subscriber->disableFromBlacklist($disabledFromBlacklist);
        $subscriber->preHandle($event);

        $this->assertSame($expectedParameters, $request->query->all());
    }

    public function queryParametersProvider(): \Generator
    {
        yield [
            ['page' => 42, 'query' => 'foobar'],
            ['page' => 42, 'query' => 'foobar'],
        ];

        yield [
            ['page' => 42, 'query' => 'foobar', 'gclid' => 'EAIaIQobChMIgrbRrZLH6AIVl6F7Ch2NMQCxEAEYASAAEgLjlPD_BwE'],
            ['page' => 42, 'query' => 'foobar'],
        ];

        yield [
            ['page' => 42, 'query' => 'foobar', 'utm_source' => 'twitter'],
            ['page' => 42, 'query' => 'foobar'],
        ];

        yield [
            ['page' => 42, 'query' => 'foobar', 'utm_source' => 'twitter'],
            ['page' => 42],
            ['page'],
        ];

        yield [
            ['page' => 42, 'gclid' => 'EAIaIQobChMIgrbRrZLH6AIVl6F7Ch2NMQCxEAEYASAAEgLjlPD_BwE', 'utm_source' => 'twitter'],
            ['page' => 42, 'utm_source' => 'twitter'],
            [],
            ['utm_[a-z]+'],
        ];

        yield [
            ['page' => 42, 'utm_foo' => 'foo', 'utm_bar' => 'bar'],
            ['page' => 42, 'utm_foo' => 'foo'],
            [],
            ['utm_fo+'],
        ];
    }
}
