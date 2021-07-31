<?php

namespace Drupal\Tests\social_auth_discourse\Unit\Controller;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\social_auth_discourse\Controller\DiscourseAuthController;
use Drupal\social_auth_discourse\DiscourseAuthManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\social_auth_discourse\Controller\DiscourseAuthController
 * @covers ::__construct()
 * @covers ::create
 * @covers ::<!public>
 *
 * @group social_auth
 * @ingroup social_auth_discourse
 */
class DiscourseAuthControllerTest extends UnitTestCase {

  /**
   * @var \Drupal\social_auth_discourse\Controller\DiscourseAuthController
   */
  private DiscourseAuthController $controller;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $currentUser;

  /**
   * @var \Drupal\social_auth\SocialAuthDataHandler|\PHPUnit\Framework\MockObject\MockObject
   */
  private $dataHandler;

  /**
   * @var \Drupal\social_auth_discourse\DiscourseAuthManager|\PHPUnit\Framework\MockObject\MockObject
   */
  private $discourseAuthManager;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $messenger;

  /**
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $renderer;

  private string $secret;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $urlGenerator;

  /**
   * @var \Drupal\social_auth\User\UserAuthenticator|\PHPUnit\Framework\MockObject\MockObject
   */
  private $userAuthenticator;

  /**
   * @covers ::redirectToProvider
   */
  public function testRedirectToProviderAuthenticatedUser(): void {
    $this->currentUser->method('isAuthenticated')->willReturn(TRUE);
    $this->urlGenerator->method('generateFromRoute')->willReturn('http://example.com');

    $response = $this->controller->redirectToProvider(new Request());
    self::assertEquals('http://example.com', $response->getTargetUrl());
  }

  /**
   * @covers ::redirectToProvider
   * @testWith [false]
   *           [true]
   */
  public function testRedirectToProvider(bool $destination): void {
    $this->discourseAuthManager->method('getAuthorizationUrl')->willReturn('http://example.com');
    $this->renderer
      ->method('executeInRenderContext')
      ->willReturnCallback(function (RenderContext $context, callable $callable) {
        $context->push(NULL);
        return $callable();
      });
    $this->userAuthenticator->expects($destination ? self::once() : self::never())->method('setDestination');

    $response = $this->controller->redirectToProvider(new Request([
      'destination' => $destination,
    ]));
    self::assertEquals('http://example.com', $response->getTargetUrl());
  }

  /**
   * @covers ::callback
   */
  public function testCallbackAuthenticatedUser(): void {
    $this->currentUser->method('isAuthenticated')->willReturn(TRUE);
    $this->urlGenerator->method('generateFromRoute')->willReturn('http://example.com');

    $response = $this->controller->callback(new Request());
    self::assertEquals('http://example.com', $response->getTargetUrl());
  }

  /**
   * @covers ::callback
   */
  public function testCallbackInvalidQueryParameter(): void {
    $this->expectException(NotFoundHttpException::class);
    $this->controller->callback(new Request());
  }

  /**
   * @covers ::callback
   */
  public function testCallbackInvalidSignature(): void {
    $this->expectException(AccessDeniedHttpException::class);

    $this->controller->callback(new Request([
      'sso' => $this->getRandomGenerator()->string(),
      'sig' => $this->getRandomGenerator()->string(),
    ]));
  }

  /**
   * @covers ::callback
   */
  public function testCallbackInvalidNonce(): void {
    $this->expectException(AccessDeniedHttpException::class);

    $sso = $this->getRandomGenerator()->string();
    $this->controller->callback(new Request([
      'sso' => $sso,
      'sig' => hash_hmac('sha256', urldecode($sso), $this->secret),
    ]));
  }

  /**
   * @covers ::callback
   */
  public function testCallbackDisabledGroup(): void {
    $nonce = $this->getRandomGenerator()->string();
    $this->dataHandler->method('get')->willReturn($nonce);
    $this->discourseAuthManager->method('getUserInfo')->willReturn([
      'nonce' => $nonce,
      'groups' => 'group',
    ]);
    $this->messenger->expects(self::once())->method('addError');
    $this->urlGenerator->method('generateFromRoute')->willReturn('http://example.com');

    $sso = $this->getRandomGenerator()->string();
    $response = $this->controller->callback(new Request([
      'sso' => $sso,
      'sig' => hash_hmac('sha256', urldecode($sso), $this->secret),
    ]));
    self::assertEquals('http://example.com', $response->getTargetUrl());
  }

  /**
   * @covers ::callback
   */
  public function testCallback(): void {
    $nonce = $this->getRandomGenerator()->string();
    $username = $this->getRandomGenerator()->name();
    $email = $this->getRandomGenerator()->name();
    $externalId = mt_rand();
    $avatarUrl = $this->getRandomGenerator()->string();

    $this->userAuthenticator->expects(self::once())->method('authenticateUser')->with(
      self::equalTo($username),
      self::equalTo($email),
      self::equalTo($externalId),
      '',
      self::equalTo($avatarUrl),
      ['groups' => 'groups']
    );
    $this->dataHandler->method('get')->willReturn($nonce);
    $this->discourseAuthManager->method('getUserInfo')->willReturn([
      'username' => $username,
      'email' => $email,
      'external_id' => $externalId,
      'avatar_url' => $avatarUrl,
      'nonce' => $nonce,
      'groups' => 'groups',
    ]);
    $this->urlGenerator->method('generateFromRoute')->willReturn('http://example.com');

    $sso = $this->getRandomGenerator()->string();
    $response = $this->controller->callback(new Request([
      'sso' => $sso,
      'sig' => hash_hmac('sha256', urldecode($sso), $this->secret),
    ]));
    self::assertEquals('http://example.com', $response->getTargetUrl());
  }

  protected function setUp(): void {
    parent::setUp();

    $this->secret = $this->getRandomGenerator()->string();

    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->dataHandler = $this->createMock(SocialAuthDataHandler::class);
    $this->discourseAuthManager = $this->createMock(DiscourseAuthManager::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $this->userAuthenticator = $this->createMock(UserAuthenticator::class);

    $container = new ContainerBuilder();
    $container->set('config.factory', $this->getConfigFactoryStub([
      'social_auth_discourse.settings' => [
        'secret' => $this->secret,
        'disabled_groups' => ['group'],
      ],
    ]));
    $container->set('social_auth_discourse.manager', $this->discourseAuthManager);
    $container->set('social_auth.data_handler', $this->dataHandler);
    $container->set('renderer', $this->renderer);
    $container->set('social_auth.user_authenticator', $this->userAuthenticator);
    $container->set('current_user', $this->currentUser);
    $container->set('messenger', $this->messenger);
    $container->set('url_generator', $this->urlGenerator);
    \Drupal::setContainer($container);

    $this->controller = DiscourseAuthController::create($container);
  }

}
