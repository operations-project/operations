<?php

namespace Drupal\site\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\migrate\Exception\EntityValidationException;
use Drupal\site\Entity\DrupalProject;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteSelf;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Site routes.
 */
class SiteAboutController extends ControllerBase {

  /**
   * The site.self service.
   *
   * @var \Drupal\site\SiteSelf
   */
  protected $site;

  /**
   * The controller constructor.
   *
   * @param \Drupal\site\SiteSelf $site
   *   The site.self service.
   */
  public function __construct(SiteSelf $site) {
    $this->site = $site;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site.self')
    );
  }


  /**
   * Builds the response.
   */
  public function build() {

    // The SiteEntity (Environment)
    $drupal_project_entity = DrupalProject::loadSelf();
    $site_entity = SiteEntity::loadSelf();

    if (empty($drupal_project_entity)) {
      $build['welcome'] = [
        '#type' => 'fieldset',
        '#title' => t('Welcome to the Site Module!'),
      ];
      $build['welcome']['intro'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => t('There is no information stored about this site. Press the button to continue.'),
      ];
      $build['welcome']['create'] = Link::createFromRoute(t('Setup Site Module'), 'site.save', [], [
        'attributes' => [
          'class' => ['button button--action button--success']
        ]
      ])->toRenderable();
    }
    else {

      // Drupal Site entity information: all environments have this same entity.
      $build['info'] = [
        '#type' => 'fieldgroup',
        '#title' => t('Site Information'),
      ];
      $build['info']['site'] = $drupal_project_entity->view('teaser');
    }

    if (!empty($drupal_project_entity) && empty($site_entity)) {
      $build['no_environment'] = [
        '#type' => 'fieldset',
      ];
      $build['no_environment']['help'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => t('There is no site record stored for this environment (@link). Press the button below to create one.', [
          '@link' => Link::fromTextAndUrl(SiteEntity::getHostname(), Url::fromUri(SiteEntity::getUri()))->toString(),
        ]),
      ];
      if (\Drupal::moduleHandler()->moduleExists('site_manager')) {
        $build['no_environment']['create'] = Link::createFromRoute(t('Create Site Manager Record'), 'site.save', [
          'type' => 'site_manager',
        ], [
          'attributes' => [
            'class' => ['button button--action button--success']
          ]
        ])->toRenderable();
      }
      else {
        $build['no_environment']['create'] = Link::createFromRoute(t('Create Site Record'), 'site.save', [], [
          'attributes' => [
            'class' => ['button button--action button--success']
          ]
        ])->toRenderable();
      }
    }
    elseif (!empty($site_entity)) {

      // State
      $build['state_widget'] = $site_entity->state->view([
        'label' => 'hidden',
        'type' => 'site_state',
        'settings' => [
          'show_reason' => true,
          'reason_open' => true,
          'collapsible' => false,
        ]
      ]);
      $build['state_widget']['#weight'] = -100;

      // Drupal environment information.
      $build['environment'] = [
        '#type' => 'fieldgroup',
        '#title' => t('Current Environment'),
      ];
      $build['environment']['current'] = $site_entity->view('about');
    }
    return $build;
  }

  /**
   * Edit site info page.
   */
  public function edit() {
    // $site = SiteEntity::loadSelf();
    $project = DrupalProject::loadSelf();
    $form = \Drupal::service('entity.form_builder')->getForm($project, 'edit');

    return $form;
  }

  /**
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function saveReport() {
    $entity = $this->site->getEntity();

    // If creating a new entity, and ?type= was specified, change bundle.
    $type_request = \Drupal::request()->get('type') ?: 'drupal';
    if (empty($entity->id()) && $type_request) {
      $entity->set('site_type', $type_request);
    }

    $this->site->prepareEntity($entity);
    try {
      $entity
        ->setValidationRequired(true)
        ->setRevisionLogMessage(t('Report saved by @user via Save Report form.', [
          '@user' => \Drupal::currentUser()->getAccount()->getDisplayName(),
          ]));
      $violations = $entity->validate();
      if (count($violations)) {
        throw new EntityStorageException($violations);
      }

      $entity->save();

    }
    catch (EntityStorageException $e) {
      \Drupal::messenger()->addError(t('There was a problem when saving the site: @message', [
        '@message' => $e->getMessage(),
      ]));
    }
    if ($entity->sent) {
      \Drupal::messenger()->addStatus(t('Site data updated and sent.'));
    }
    else {
      \Drupal::messenger()->addStatus(t('Site data updated.'));
    }
    return $this->redirect('site.about');
  }

}
