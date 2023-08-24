<?php

namespace Drupal\site\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\site\Entity\SiteEntity;
use Drupal\site\SiteSelf;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Site routes.
 */
class SiteActionsController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The controller constructor.
   *
   * @param \Drupal\site\SiteSelf $self
   *   The site.self service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(SiteSelf $self, ConfigFactoryInterface $config_factory) {
    $this->self = $self;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site.remote'),
      $container->get('config.factory')
    );
  }

  /**
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function refresh(SiteEntity $site) {
    $site->getRemote()->save();
    return $this->redirect('entity.site.canonical', [
      'site' => $site->id(),
    ]);


//    $this->site->getEntity()
//      ->setRevisionLogMessage(t('Report saved by @user via Save Report form.', [
//        '@user' => \Drupal::currentUser()->getAccount()->getDisplayName(),
//      ]))
//      ->save();
//
//    return $this->redirect('site.history');
  }

}
