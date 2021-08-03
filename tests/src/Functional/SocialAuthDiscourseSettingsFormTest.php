<?php

namespace Drupal\Tests\social_auth_discourse\Functional;

use Drupal\Tests\social_auth\Functional\SocialAuthTestBase;

/**
 * Test Social Auth Discourse settings form.
 *
 * @group social_auth
 *
 * @ingroup social_auth_discourse
 */
class SocialAuthDiscourseSettingsFormTest extends SocialAuthTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['social_auth_discourse'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->module = 'social_auth_discourse';
    $this->provider = 'discourse';
    $this->moduleType = 'social-auth';

    parent::setUp();
  }

  /**
   * Test if implementer is shown in the integration list.
   */
  public function testIsAvailableInIntegrationList(): void {
    $this->fields = ['url', 'secret', 'disabled_groups', 'user_login_form_link'];

    $this->checkIsAvailableInIntegrationList();
  }

  /**
   * Test if permissions are set correctly for settings page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testPermissionForSettingsPage(): void {
    $this->checkPermissionForSettingsPage();
  }

  /**
   * Test settings form submission.
   *
   * @throws \Exception
   */
  public function testSettingsFormSubmission(): void {
    $this->edit = [
      'url' => 'http://example.com',
      'secret' => $this->randomString(10),
      'disabled_groups' => implode(',', [
        $this->randomString(),
        $this->randomString(),
      ]),
      'user_login_form_link' => (bool) random_int(0, 1),
    ];

    $this->checkSettingsFormSubmission();
  }

}
