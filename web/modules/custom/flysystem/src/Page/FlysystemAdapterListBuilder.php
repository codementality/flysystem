<?php

namespace Drupal\flysystem\Page;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Flysystem Adapter config entities.
 */
class FlysystemAdapterListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Flysystem Adapter');
    $header['id'] = $this->t('Machine name');
    $header['adapter_type'] = $this->t('Adapter Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    // $row['adapter_type'] = $entity->getAdapterPluginType();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
