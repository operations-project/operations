<?php

namespace Drupal\site\Entity\Bundle;

/**
 * A bundle class for site entities.
 */
class DefaultSiteBundle extends SiteBundle {

  public function getRemote()
  {
    parent::getRemote();
    if (empty($this->host_provider->value) && $this->headers->get('server')) {
      $this->set('host_provider', $this->headers->get('server'));
    }

    $this->set('site_title', $this->getPageTitle());

    $data = $this->data->getValue();
    $http_code = $data[0]['site_uri']['worst_code'] ?? 0;
    $this->set('http_status', $http_code);

    return $this;
  }

  /**
   * Return page title from the site_uri sites content data.
   * @return string
   */
  public function getPageTitle() {
      $data = $this->data->getValue();
      $site_content = $data[0]['site_uri']['sites'][$this->site_uri->value]['content'] ?? '';

    if (empty($site_content)) {
      return '';
    }

    $res = preg_match("/<title[^>]*>(.*)<\/title>/siU", $site_content, $title_matches);

    if (empty($res)) {
      return '';
    }

    return trim(preg_replace('/\s+/', ' ', $title_matches[1]));
  }
}
