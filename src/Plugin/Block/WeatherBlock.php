<?php
namespace Drupal\openweather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openweather\WeatherService;

/**
 * Provides a 'WeatherBlock' Block.
 *
 * @Block(
 *   id = "current_weather_block",
 *   admin_label = @Translation("Current Weather Block"),
 * )
 */
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $weatherservice;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @var string $weatherservice
   *   The information from the Weather service for this block.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WeatherService $weatherservice) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->weatherservice = $weatherservice;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openweather.weather_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['input_options'] = array(
      '#type' => 'radios',
      '#title' => t('Select your option'),
      '#options' => array(
        'city_name' => 'City Name',
        'city_id' => 'City Id',
        'zip_code' => 'Zip Code',
        'geo_coord' => 'Geographic Coordinates',
      ),
      '#default_value' => !empty($config['input_options']) ? $config['input_options'] : 'city_name',
    );
    $form['input_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter the Value for selected option'),
      '#required' => TRUE,
      '#description' => t('In case of geo coordinates please follow the format lat,lon for example: 130,131'),
      '#default_value' => $config['input_value'],
    );

    if (\Drupal::moduleHandler()->moduleExists("token")) {
      $form['token_help'] = array(
        '#type' => 'markup',
        '#token_types' => array('user'),
        '#theme' => 'token_tree_link',
      );
    }

    $form['count'] = array(
      '#type' => 'number',
      '#min' => '1',
      '#title' => t('Enter the number count'),
      '#default_value' => !empty($config['count']) ? $config['count'] : '1',
      '#required' => TRUE,
      '#description' => t('Select the count in case of hourlyforecast maximum value should be 36 and in case of daily forecast maximum value should be 7. in case of current weather forecast value is the default value'),
    );

    $form['display_select'] = array(
      '#type' => 'select',
      '#title' => t('Select your option'),
      '#options' => array(
        'current_details' => 'Current Details',
        'forecast_hourly' => 'Forecast after 3 hours each',
        'forecast_daily' => 'Daily Forecast',
      ),
      '#default_value' => !empty($config['display_type']) ? $config['display_type'] : 'current_details',
    );

    $weatherdata = array(
      'name' => t('City Name'),
      'humidity' => t('Humidity'),
      'temp_min' => t('Temp Min'),
      'temp_max' => t('Temp Max'),
      'coord' => t('Coordinates'),
      'weather' => t('Weather details include icon and description'),
      'temp' => t('current Temperature'),
      'pressure' => t('pressure'),
      'sea_level' => t('Sea Level'),
      'grnd_level' => t('Ground level'),
      'wind_speed' => t('Wind Speed'),
      'wind_deg' => t('Wind flow in degree'),
      'date' => t('Date'),
      'time' => t('Time'),
      'day' => t('Day'),
      'country' => t('Country'),
      'sunrise' => t('Sunrise time'),
      'sunset' => t('sunset time'),
    );
    $form['weatherdata'] = array(
      '#type' => 'details',
      '#title' => t('Output Option available for current weather'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['weatherdata']['items'] = array(
      '#type' => 'checkboxes',
      '#options' => $weatherdata,
      '#description' => t('Select output data you want to display.'),
      '#default_value' => !empty($config['outputitems']) ? $config['outputitems'] : array(
        'name',
        'weather',
        'temp',
        'country',
        'time',
        'humidity',
        'date',
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $token_service = \Drupal::token();
    $message = $token_service->replace($form_state->getValue('input_value'), array('user' => $user));
    $result = $form_state->getValue('weatherdata')['items'];
    $this->setConfigurationValue('outputitems', $form_state->getValue('weatherdata')['items']);
    if (!empty($message)) {
      $this->setConfigurationValue('input_value', $message);
    }
    else {
      $this->setConfigurationValue('input_value', $form_state->getValue('input_value'));
    }
    $this->setConfigurationValue('count', $form_state->getValue('count'));
    $this->setConfigurationValue('input_options', $form_state->getValue('input_options'));
    $this->setConfigurationValue('display_type', $form_state->getValue('display_select'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $html = [];
    $output = json_decode($this->weatherservice->getWeatherInformation($config), TRUE);
    if (empty($output)) {
      return array(
        '#markup' => $this->t('The @input not found!', array('@input' => $config['input_options'])),
        '#cache' => array('max-age' => 0),
      );
    }

    switch ($config['display_type']) {
      case 'current_details':
        $build = $this->weatherservice->getCurrentWeatherInformation($output, $config);
        break;

      case 'forecast_hourly':
        $build = $this->weatherservice->getHourlyForecastWeatherInformation($output, $config);
        break;

      case 'forecast_daily':
        break;
    }

    return $build;
  }

}
