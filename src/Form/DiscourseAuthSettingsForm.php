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

    $form['discourse_settings']['disabled_groups'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disable Discourse login for the following Discourse groups'),
      '#description' => $this->t('Enter comma separated value of discourse groups. This groups will not be able login to drupal site.'),
      '#default_value' => implode(',', $config->get('disabled_groups') ?? []),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Callback for both ajax-enabled buttons.
   */
  public function addmoreCallback(array $form): array {
    return $form['discourse_settings']['disabled_groups'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   */
  public function addOne(array &$form, FormStateInterface $form_state): void {
    $disabled_groups = $form_state->get('disabled_groups');
    $disabled_groups[] = '';
    $form_state->set('disabled_groups', $disabled_groups);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $disabled_groups = explode(',', $form_state->getValue('disabled_groups'));

    $config = $this->config('social_auth_discourse.settings');
    $config->set('url', $form_state->getValue('url'));
    $config->set('secret', $form_state->getValue('secret'));
    $config->set('disabled_groups', array_map(static fn($group) => trim($group), $disabled_groups));
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
