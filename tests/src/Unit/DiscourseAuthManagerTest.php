<?php

namespace Drupal\Tests\social_auth_discourse\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\social_auth_discourse\DiscourseAuthManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\social_auth_discourse\DiscourseAuthManager
 * @covers ::__construct
 *
 * @group social_auth
 * @ingroup social_auth_discourse
 */
class DiscourseAuthManagerTest extends UnitTestCase {

  private const URL = 'http://example.com';

  private DiscourseAuthManager $authManager;

  private string $secret;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $loggerChannelFactory;

  /**
   * @covers ::getState
   */
  public function testGetState(): void {
    self::assertEquals($this->authManager->getState(), $this->authManager->getState());
  }

  /**
   * @covers ::getAuthorizationUrl
   */
  public function testGetAuthorizationUrl(): void {
    $url = parse_url($this->authManager->getAuthorizationUrl());
    self::assertIsArray($url);
    self::assertArrayHasKey('path', $url);
    self::assertArrayHasKey('query', $url);

    self::assertEquals('/session/sso_provider', $url['path']);
    self::assertNotEmpty($url['query']);

    parse_str($url['query'], $result);
    self::assertArrayHasKey('sso', $result);
    self::assertArrayHasKey('sig', $result);

    $payload = [
      'nonce' => $this->authManager->getState(),
      'return_sso_url' => self::URL,
    ];
    $query = http_build_query($payload);
    $base64_payload = base64_encode($query);
    $url_encoded_payload = str_replace(
      ['+', '/', '='],
      ['-', '_', ''],
      $base64_payload
    );

    self::assertEquals($url_encoded_payload, $result['sso']);
    self::assertEquals(hash_hmac('sha256', $result['sso'], $this->secret), $result['sig']);
  }

  /**
   * @covers ::getUserInfo
   */
  public function testGetUserInfoWithoutRequestArgument(): void {
    self::assertFalse($this->authManager->getUserInfo());
  }

  /**
   * @covers ::getUserInfo
   */
  public function testGetUserInfoWithInvalidSsoParameter(): void {
    $loggerChannel = $this->createMock(LoggerChannelInterface::class);
    $loggerChannel->expects(self::once())->method('error');

    $this->loggerChannelFactory->method('get')->willReturn($loggerChannel);

    $request = new Request();
    self::assertFalse($this->authManager->getUserInfo($request));
  }

  /**
   * @covers ::getUserInfo
   */
  public function testGetUserInfo(): void {
    $query = [
      'a' => 1,
      'b' => 2,
    ];
    $sso = base64_encode(http_build_query($query));
    $request = new Request([
      'sso' => urlencode($sso),
    ]);
    self::assertEquals($query, $this->authManager->getUserInfo($request));
  }

  /**
   * @covers ::requestEndPoint
   */
  public function testRequestEndPoint(): void {
    self::assertNull($this->authManager->requestEndPoint('GET', ''));
  }

  protected function setUp(): void {
    parent::setUp();

    $this->secret = $this->getRandomGenerator()->string();
    $this->loggerChannelFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $configFactory = $this->getConfigFactoryStub([
      'social_auth_discourse.settings' => [
        'url' => self::URL,
        'secret' => $this->secret,
      ],
    ]);
    assert($configFactory instanceof ConfigFactoryInterface);

    $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $urlGenerator->method('generateFromRoute')->willReturn(self::URL);

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')
      ->willReturn($this->createMock(Request::class));

    $unroutedUrlAssembler = new UnroutedUrlAssembler(
      $requestStack,
      $this->createMock(OutboundPathProcessorInterface::class)
    );

    $this->authManager = new DiscourseAuthManager(
      $configFactory,
      $this->loggerChannelFactory,
      $urlGenerator,
      $unroutedUrlAssembler,
    );
  }

}
