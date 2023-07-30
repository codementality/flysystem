<?php

namespace Drupal\Tests\flysystem\Unit\EventSubscriber;

use Drupal\flysystem\Event\EnsureEvent;
use Drupal\flysystem\Event\FlysystemEvents;
use Drupal\flysystem\EventSubscriber\EnsureSubscriber;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\flysystem\EventSubscriber\EnsureSubscriber
 * @covers \Drupal\flysystem\EventSubscriber\EnsureSubscriber
 *
 * @group flysystem
 */
class EnsureSubscriberTest extends UnitTestCase {

  /**
   * Tests that the event subscriber logs ensure() calls.
   */
  public function testLoggingHappens() {
    $logger = $this->prophesize(LoggerInterface::class);
    $dispatcher = $this->createMock(EventDispatcherInterface::class);
    $logger->log('severity', 'message', ['context'])->shouldBeCalled();

    $event = new EnsureEvent('scheme', 'severity', 'message', ['context']);

    $subscriber = new EnsureSubscriber($logger->reveal());

    $subscriber->onEnsure($event, FlysystemEvents::ENSURE, $dispatcher);
  }

  /**
   * Tests that the ENSURE event is registered.
   */
  public function testSubscribedEvents() {
    $result = EnsureSubscriber::getSubscribedEvents();

    $this->assertTrue(isset($result[FlysystemEvents::ENSURE]));
  }

}
