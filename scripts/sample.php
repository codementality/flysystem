<?php

require __DIR__ . '/../vendor/autoload.php';

$plugin = \Drupal::service('plugin.manager.flysystem_adapter');

$fallback = $plugin->getFallbackId();
