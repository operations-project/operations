<?php

namespace Drupal\site\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element\Form;
use Drupal\site\Form\SiteActionForm;
use Drupal\site\SiteEntityInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Site routes.
 */
class SiteActionController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function form(SiteEntityInterface $site, string $plugin_id) {

    try {
      $plugin = \Drupal::service('plugin.manager.site_action')->createInstance($plugin_id, [
        'site' => $site,
      ]);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }

    /** @var FormBuilderInterface $form_builder */
    $form_builder = $this->formBuilder();
    $form_state = new FormState();
    $form_state->set('plugin', $plugin);
    $build = $form_builder->buildForm(SiteActionForm::class, $form_state);
    return $build;

  }

  /**
   * @return string
   */
  public function formPageTitle(SiteEntityInterface $site, string $plugin_id) {
    try {
      $plugin = \Drupal::service('plugin.manager.site_action')->createInstance($plugin_id, [
        'site' => $site,
      ]);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }

    $plugin->setSite($site);
    return $plugin->formPageTitle();
  }

}
