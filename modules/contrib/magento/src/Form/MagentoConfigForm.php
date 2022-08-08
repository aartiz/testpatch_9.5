<?php

namespace Drupal\magento\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MagentoConfigForm.
 *
 * @package Drupal\magento\Form
 */
class MagentoConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'magento.magento_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'magento.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('magento.settings');
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $config->get('access_token'),
      '#description' => $this->t('Magento Integration Access Token'),
      '#required' => TRUE,
    ];
    $form['magento_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Magento URL'),
      '#default_value' => $config->get('magento_url'),
      '#description' => $this->t('Magento Site URL'),
      '#required' => TRUE,
    ];
    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Mode'),
      '#default_value' => $config->get('debug_mode'),
      '#description' => $this->t('Provide useful debugging information in console'),
      '#required' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $magento_url = $form_state->getValue('magento_url');
    if (!UrlHelper::isValid($magento_url, TRUE)) {
      $form_state->setErrorByName('magento_url', $this->t('Invalid URL.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('magento.settings')
      ->set('access_token', $form_state->getValue('access_token'))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->set('magento_url', rtrim($form_state->getValue('magento_url'), '/'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
