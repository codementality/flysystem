<?php

namespace Drupal\Tests\flysystem\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\flysystem\SerializationStopperTrait;
use LogicException;

/**
 * @coversDefaultClass \Drupal\flysystem\SerializationStopperTrait
 * @group flysystem
 */
class SerializationStopperTraitTest extends UnitTestCase {

  /**
   * @covers ::__sleep
   */
  public function test() {
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('can not be serialized.');
    $trait = $this->getMockForTrait(SerializationStopperTrait::class);
    serialize($trait);
  }

}
