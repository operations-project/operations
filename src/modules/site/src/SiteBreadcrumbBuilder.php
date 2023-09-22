<?php

namespace Drupal\site;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\site\Entity\SiteEntity;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides a breadcrumb builder for articles.
 */
class SiteBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $project = $route_match->getParameter('drupal_project');
    $site = $route_match->getParameter('site');
    return $project instanceof DrupalProjectInterface || $site instanceof SiteEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $site = $route_match->getParameter('site');
    $project = $route_match->getParameter('drupal_project');

    // If "Projects" view exists, set as parent breadcrumb.
    try {
      $projects_route = 'view.drupal_projects.page_1';
      \Drupal::service('router.route_provider')
        ->getRouteByName($projects_route);
      ;
    }
    catch (\Exception $e) {
      $projects_route = 'entity.drupal_project.collection';
    }

    // If "Sites" view exists, set as parent breadcrumb.
    try {
      $sites_route = 'view.sites.page_1';
      \Drupal::service('router.route_provider')
        ->getRouteByName($projects_route);
      ;
    }
    catch (RouteNotFoundException $e) {
      $sites_route = 'entity.site.collection';
    }

    // If on project page...
    if ($project) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Projects'), $projects_route));

      if (\Drupal::routeMatch()->getRouteName() == 'entity.drupal_project.add_site') {
        $breadcrumb->addLink($project->toLink());
      }

      return $breadcrumb;
    }

    // If on a site with a drupal project, use Projects > Project Name
    if (!empty($site->drupal_project->entity)) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Projects'), $projects_route));
      $breadcrumb->addLink($site->drupal_project->entity->toLink());
      $breadcrumb->addLink($site->drupal_project->entity->toLink(t('Environments')));
    }
    // All other sites use Sites
    else {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Sites'), $sites_route));
    }

    return $breadcrumb;
  }
}
