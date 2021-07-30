<?php

namespace Drupal\social_auth_discourse\Plugin\Network;

use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;

/**
 * Defines a Network Plugin for Social Auth Discourse.
 *
 * @Network (
 *   id = "social_auth_discourse",
 *   socialNetwork = "Discourse",
 *   social_network = "Discourse",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "config_id": "social_auth_discourse.settings"
 *     }
 *   }
 * )
 */
class DiscourseAuth extends NetworkBase {

  /**
   * {@inheritDoc}
   */
  protected function initSdk() {
    throw new SocialApiException();
  }

}
