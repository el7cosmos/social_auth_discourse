<?php

namespace Drupal\social_auth_discourse;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\social_auth\AuthManager\OAuth2Manager;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class DiscourseAuthManager extends OAuth2Manager {

  protected static string $state;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected UrlGeneratorInterface $urlGenerator;

  /**
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected UnroutedUrlAssemblerInterface $unroutedUrlAssembler;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    UrlGeneratorInterface $urlGenerator,
    UnroutedUrlAssemblerInterface $unroutedUrlAssembler
  ) {
    parent::__construct($configFactory->get('social_auth_discourse.settings'), $loggerChannelFactory);
    $this->urlGenerator = $urlGenerator;
    $this->unroutedUrlAssembler = $unroutedUrlAssembler;
  }

  /**
   * {@inheritDoc}
   */
  public function authenticate(): void {
  }

  /**
   * {@inheritDoc}
   */
  public function getAuthorizationUrl() {
    $return_url = Url::fromRoute('social_auth_discourse.callback', [], [
      'absolute' => TRUE,
    ]);
    $return_url->setUrlGenerator($this->urlGenerator);

    $payload = [
      'nonce' => $this->getState(),
      'return_sso_url' => $return_url->toString(),
    ];

    $query = http_build_query($payload);
    $base64_payload = base64_encode($query);
    $url_encoded_payload = str_replace(
      ['+', '/', '='],
      ['-', '_', ''],
      $base64_payload
    );
    $hex_signature = hash_hmac('sha256', $url_encoded_payload, $this->settings->get('secret'));

    $url = Url::fromUri($this->settings->get('url') . '/session/sso_provider', [
      'query' => [
        'sso' => $url_encoded_payload,
        'sig' => $hex_signature,
      ],
    ]);
    $url->setUnroutedUrlAssembler($this->unroutedUrlAssembler);

    return $url->toString();
  }

  /**
   * {@inheritDoc}
   */
  public function getState(): string {
    if (empty(static::$state)) {
      static::$state = Crypt::randomBytesBase64(16);
    }
    return static::$state;
  }

  /**
   * {@inheritDoc}
   */
  public function getUserInfo(Request $request = NULL) {
    if (!$request) {
      return FALSE;
    }

    $sso = base64_decode(urldecode($request->query->get('sso')));
    if (!$sso) {
      $this->loggerFactory->get('social_auth_discourse')
        ->error('Failed to decode SSO parameter');
      return FALSE;
    }

    parse_str($sso, $info);
    return $info;
  }

  /**
   * {@inheritDoc}
   */
  public function requestEndPoint($method, $path, $domain = NULL, array $options = []) {
    return NULL;
  }

}
