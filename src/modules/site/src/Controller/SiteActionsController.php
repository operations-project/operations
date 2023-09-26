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
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function refresh(SiteEntity $site) {
    // Reset Reasons.
    $site->get('reason')->setValue([]);

    // Get remote data...
    $site->getRemote();
    $violations = $site->validate();
    // Save the site if there are no violations.
    // @TODO: getRemote() triggers a EntityChangedConstraintValidator to fail.
    // This code ignores it.
    if ($violations->count() == 0 || ($violations->count() == 1 && $violations->get(0)->getMessageTemplate() == "The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.")) {
      $site->setRevisionLogMessage(t('Site updated from :url by :user on :date.', [
        ':url' => \Drupal::request()->getUri(),
        ':user' => \Drupal::currentUser()->getDisplayName(),
        ':date' => \Drupal::service('date.formatter')->format(time()),
      ]));
      $site->save();
      \Drupal::messenger()->addStatus(t('Site data has been updated.'));
    }
    else {

      \Drupal::messenger()->addError(t('The site could not validate:'));
      foreach ($violations as $violation) {
        \Drupal::messenger()->addError($violation->getMessage());
      }
    }

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
