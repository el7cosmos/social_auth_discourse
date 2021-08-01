<?php

namespace Drupal\Tests\social_auth_discourse\Functional;

use Drupal\Tests\social_auth\Functional\SocialAuthTestBase;

/**
 * Test that path to authentication route exists in Social Auth Login block.
 *
 * @group social_auth
 *
 * @ingroup social_auth_discourse
 */
class SocialAuthDiscourseLoginBlockTest extends SocialAuthTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'social_auth_discourse'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->provider = 'discourse';
  }

  /**
   * Test that the path is included in the login block.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testLinkExistsInBlock(): void {
    $this->checkLinkToProviderExists();
  }

}
