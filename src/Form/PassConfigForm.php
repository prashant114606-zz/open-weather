<?php
namespace Drupal\openweather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class PassConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openweather_appid_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openweather.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('openweather.settings');
    $form['appid'] = array(
      '#type' => 'textfield',
      '#title' => t('App Id'),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValues();
    $my_config = \Drupal::config('mymodule.settings')->get('name_of_form_field');
    $this->config('openweather.settings')
      ->set('appid', $form_state->getValue('appid'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
