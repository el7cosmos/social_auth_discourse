<?php

namespace Drupal\social_auth_discourse\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Configure Social Auth Discourse settings for this site.
 */
class DiscourseAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_auth_discourse_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('social_auth_discourse.settings');

    $form['discourse_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Discourse settings'),
      '#open' => TRUE,
    ];

    $form['discourse_settings']['url'] = [
      '#type' => 'url',
      '#required' => TRUE,
      '#title' => $this->t('Discourse URL'),
      '#default_value' => $config->get('url'),
    ];

    $form['discourse_settings']['secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret'),
      '#default_value' => $config->get('secret'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('social_auth_discourse.settings');
    $config->set('url', $form_state->getValue('url'));
    $config->set('secret', $form_state->getValue('secret'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      ...parent::getEditableConfigNames(),
      'social_auth_discourse.settings',
    ];
  }

}
