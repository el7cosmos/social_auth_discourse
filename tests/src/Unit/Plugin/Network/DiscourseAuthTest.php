<?php

namespace Drupal\Tests\social_auth_discourse\Unit\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_discourse\Plugin\Network\DiscourseAuth;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\social_auth_discourse\Plugin\Network\DiscourseAuth
 */
class DiscourseAuthTest extends TestCase {

  /**
   * @covers ::initSdk
   */
  public function testGetSdk(): void {
    $this->expectException(SocialApiException::class);

    $auth = new DiscourseAuth(
      [],
      'social_auth_discourse',
      [],
      $this->createMock(LoggerChannelFactoryInterface::class),
      new Settings([]),
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(ConfigFactoryInterface::class)
    );
    $auth->getSdk();
  }

}
