<?php

namespace Drupal\site\Entity\Bundle;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * A bundle class for site entities.
 */
class PhpSiteBundle extends WebAppSiteBundle {

  /**
   * @inheritdoc
   */
  public function siteHistoryTableHeaders()
  {
    $headers = parent::siteHistoryTableHeaders();
    $headers[] = 'PHP Version';
    return $headers;
  }

  /**
   * @inheritdoc
   */
  public function siteHistoryTableRow()
  {
    $row = parent::siteHistoryTableRow();
    if ($this->php_version->value) {
      $row[] = [
        'data' => $this->php_version->value ?? '',
      ];
    }
    else {
      $row[] = '';
    }
    return $row;
  }
}
