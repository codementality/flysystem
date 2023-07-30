<?php

namespace Drupal\Tests\flysystem\Unit\Event;

use Drupal\flysystem\Event\EnsureEvent;
use Drupal\Tests\UnitTestCase;

/**
 * Tests EnsureEvent.
 *
 * @coversDefaultClass \Drupal\flysystem\Event\EnsureEvent
 * @covers \Drupal\flysystem\Event\EnsureEvent
 * @group flysystem
 */
class EnsureEventTest extends UnitTestCase {

  /**
   * Tests the basic setters/getters of EnsureEvent.
   */
  public function test() {
    $event = new EnsureEvent('scheme', 10, 'message', ['1234']);

    $this->assertSame('scheme', $event->getScheme());
    $this->assertSame(10, $event->getSeverity());
    $this->assertSame('message', $event->getMessage());
    $this->assertSame(['1234'], $event->getContext());
  }

}
