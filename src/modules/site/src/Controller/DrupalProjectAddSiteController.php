<?php

namespace Drupal\site\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\site\DrupalProjectInterface;
use Drupal\site\Entity\SiteEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Site routes.
 */
class DrupalProjectAddSiteController extends ControllerBase
{

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder)
  {
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity.form_builder')
    );
  }

  /**
   * Show the create site form with Drupal Project pre-populated.
   */
  public function form()
  {
    $site = SiteEntity::create([
      'site_type' => 'drupal',
    ]);

    // Set project field.
    $project = \Drupal::routeMatch()->getParameter('project');
    $site->set('project', $project->id());
    $form = $this->entityFormBuilder()->getForm($site, 'add');
    $form['project'] = [
      '#type' => 'value',
      '#value' => $project->id(),
    ];

    return $form;
  }


  /**
   * @return string
   */
  public function formPageTitle()
  {
    $project = \Drupal::routeMatch()->getParameter('project');
    if ($project) {
      return t('Add site');
    }
  }
}
