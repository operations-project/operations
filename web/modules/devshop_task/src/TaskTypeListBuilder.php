<?php

namespace Drupal\devshop_task;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;use Drupal\devshop_task\Entity\TaskType;

/**
 * Defines a class to build a listing of task type entities.
 *
 * @see \Drupal\devshop_task\Entity\TaskType
 */
class TaskTypeListBuilder extends ConfigEntityListBuilder {

  function load(){

    $entity_ids = $this->getEntityIds();
    $entities = $this->storage->loadMultipleOverrideFree($entity_ids);

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, [$this->entityType->getClass(), 'sort']);

    $type = \Drupal::service('plugin.manager.devshop_task_type');
    $plugin_definitions = $type->getDefinitions();


    $plugin_entities = [];
    foreach ($plugin_definitions as $plugin_definition) {
      $values = $plugin_definition;
      $task_type_entity = new TaskType($values, 'task_type');
      $plugin_entities[$plugin_definition['id']] = $task_type_entity;
    }
    return $entities + $plugin_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Type');
    $header['command'] = $this->t('Command');
    $header['description'] = $this->t('Description');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    dsm($entity);
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
//    $row['command'] = $entity->command();
//    $row['command'] = $entity->description();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No task types available. <a href=":link">Enable a Task Type module</a> to continue.',
      [':link' => Url::fromRoute('system.modules_list')->toString()]
    );

    return $build;
  }

}
