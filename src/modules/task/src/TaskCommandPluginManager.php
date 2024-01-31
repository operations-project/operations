<?php

namespace Drupal\task;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * TaskCommand plugin manager.
 */
class TaskCommandPluginManager extends DefaultPluginManager {

  /**
   * Constructs TaskCommandPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/TaskCommand',
      $namespaces,
      $module_handler,
      'Drupal\task\TaskCommandInterface',
      'Drupal\task\Annotation\TaskCommand'
    );
    $this->alterInfo('task_command_info');
    $this->setCacheBackend($cache_backend, 'task_command_plugins');
  }

}
