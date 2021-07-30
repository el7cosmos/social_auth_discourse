<?php

namespace Drupal\social_auth_discourse\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\social_auth_discourse\DiscourseAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DiscourseAuthController extends ControllerBase {

  protected const PLUGIN_ID = 'social_auth_discourse';

  protected DiscourseAuthManager $authManager;

  protected Config $config;

  protected SocialAuthDataHandler $dataHandler;

  protected RendererInterface $renderer;

  protected UserAuthenticator $userAuthenticator;

  public function __construct(
    DiscourseAuthManager $authManager,
    Config $config,
    SocialAuthDataHandler $dataHandler,
    RendererInterface $renderer,
    UserAuthenticator $userAuthenticator
  ) {
    $this->authManager = $authManager;
    $this->config = $config;
    $this->dataHandler = $dataHandler;
    $this->renderer = $renderer;
    $this->userAuthenticator = $userAuthenticator;

    // Sets the session prefix.
    $this->dataHandler->setSessionPrefix(self::PLUGIN_ID);

    // Sets the plugin id in user authenticator.
    $this->userAuthenticator->setPluginId(self::PLUGIN_ID);

    // Sets the session keys to nullify if user could not logged in.
    $this->userAuthenticator->setSessionKeysToNullify(['nonce']);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $container->get('config.factory');

    return new self(
      $container->get('social_auth_discourse.manager'),
      $configFactory->get('social_auth_discourse.settings'),
      $container->get('social_auth.data_handler'),
      $container->get('renderer'),
      $container->get('social_auth.user_authenticator'),
    );
  }

  public function redirectToProvider(Request $request): TrustedRedirectResponse {
    $context = new RenderContext();

    /** @var \Drupal\Core\Routing\TrustedRedirectResponse $response */
    $response = $this->renderer->executeInRenderContext($context, function () use ($request) {
      /*
       * If destination parameter is set, save it.
       *
       * The destination parameter is also _removed_ from the current request
       * to prevent it from overriding Social Auth's TrustedRedirectResponse.
       *
       * @see https://www.drupal.org/project/drupal/issues/2950883
       *
       * TODO: Remove the remove() call after 2950883 is solved.
       */
      $destination = $request->get('destination');
      if ($destination) {
        $this->userAuthenticator->setDestination($destination);
        $request->query->remove('destination');
      }

      // Generates the URL for authentication.
      $authorizationUrl = $this->authManager->getAuthorizationUrl();

      $state = $this->authManager->getState();
      $this->dataHandler->set('nonce', $state);

      $this->userAuthenticator->dispatchBeforeRedirect($destination);
      return new TrustedRedirectResponse($authorizationUrl);
    });

    // Add bubbleable metadata to the response.
    if ($response instanceof TrustedRedirectResponse && !$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      $response->addCacheableDependency($bubbleable_metadata);
    }

    return $response;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function callback(Request $request): RedirectResponse {
    $nonce = $this->dataHandler->get('nonce');
    $this->userAuthenticator->nullifySessionKeys();

    if (!$request->query->has('sso') || !$request->query->has('sig')) {
      throw new NotFoundHttpException();
    }

    $sso = $request->query->get('sso');
    $sig = hash_hmac('sha256', urldecode($sso), $this->config->get('secret'));
    if (!hash_equals($sig, $request->query->get('sig'))) {
      throw new AccessDeniedHttpException();
    }

    $info = $this->authManager->getUserInfo($request);

    if (empty($info['nonce']) || ($info['nonce'] !== $nonce)) {
      throw new AccessDeniedHttpException();
    }

    $data = array_filter($info, static function ($key) {
      switch ($key) {
        case 'username':
        case 'email':
        case 'external_id':
        case 'nonce':
          return FALSE;

        default:
          return TRUE;
      }
    }, ARRAY_FILTER_USE_KEY);

    $this->userAuthenticator->authenticateUser($info['username'], $info['email'], $info['external_id'], '', $info['avatar_url'], $data);

    return $this->redirect('user.login');
  }

}
