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
    $form['sample_radios'] = array(
      '#type' => 'radios',
      '#title' => t('Select your option'),
      '#options' => array(
        'city_name' => 'City Name',
        'city_id' => 'City Id',
        'zip_code' => 'Zip Code',
        'geo_coord' => 'Geographic Coordinates',
      ),
    );
    $form['input_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter the Selected Value'),
    );

    if (\Drupal::moduleHandler()->moduleExists("token")) {
      $form['token_help'] = array(
        '#type' => 'markup',
        '#token_types' => array('user'),
        '#theme' => 'token_tree_link',
      );
    }
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
      '#description' => t('Select output data you want to see.'),
      '#default_value' => array(
        'name',
        'weather',
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
    $this->setConfigurationValue('radio_value', $form_state->getValue('sample_radios'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    if (empty($config['input_value'])) {
      $city_name = $this->t('not found');
      return $city_name;
    }
    $html = [];
    $data = $this->weatherservice->getWeatherInformation($config);
    $k = json_decode($data, TRUE);
    $current_time = REQUEST_TIME;
    $date = format_date($current_time, $type = 'long', $format = '', $timezone = NULL, $langcode = NULL);
    $date_input = explode(",", $date);
    $time_input = explode("-", $date_input[2]);

    $dependency = NULL;
    foreach ($config['outputitems'] as $key => $value) {
      if (!empty($config['outputitems'][$value])) {
        switch ($config['outputitems'][$value]) {
          case 'humidity':
            $html[$value] = $k['main']['humidity'] . '%';
            break;

          case 'temp_max':
            $html[$value] = round($k['main']['temp_max'] - 273.15, 2) . '°C';
            break;

          case 'temp_min':
            $html[$value] = round($k['main']['temp_min'] - 273.15, 2) . '°C';
            break;

          case 'name':
            $html[$value] = $k['name'];
            break;

          case 'date':
            $html[$value] = $date_input[1] . $time_input[0];
            break;

          case 'coord':
            $html[$value]['lon'] = $k['coord']['lon'];
            $html[$value]['lat'] = $k['coord']['lat'];
            break;

          case 'weather':
            $html[$value]['desc'] = $k['weather'][0]['main'];
            $html[$value]['image'] = $k['weather'][0]['icon'];
            break;

          case 'temp':
            $html[$value] = round($k['main']['temp'] - 273.15) . '°C';
            break;

          case 'pressure':
            $html[$value] = $k['main']['pressure'];
            break;

          case 'sea_level':
            $html[$value] = $k['main']['sea_level'];
            break;

          case 'grnd_level':
            $html[$value] = $k['main']['grnd_level'];
            break;

          case 'wind_speed':
            $html[$value] = round($k['wind']['speed'] * (60 * 60 / 1000), 1) . 'km/h';
            break;

          case 'wind_deg':
            $html[$value] = $k['wind']['deg'];
            break;

          case 'time':
            $dependency = $current_time;
            $html[$value] = $time_input[1];
            break;

          case 'day':
            $html[$value] = $date_input[0];
            break;

          case 'country':
            $html[$value] = $k['sys']['country'];
            break;

          case 'sunrise':
            $sunrise = format_date($k['sys']['sunrise'], $type = 'long', $format = '', $timezone = NULL, $langcode = NULL);
            $dependency = $sunrise;
            $sunrise_time = explode("-", $sunrise);
            $html[$value] = $sunrise_time[1];
            break;

          case 'sunset':
            $sunset = format_date($k['sys']['sunset'], $type = 'long', $format = '', $timezone = NULL, $langcode = NULL);
            $dependency = $sunset;
            $sunset_time = explode("-", $sunset);
            $html[$value] = $sunset_time[1];
            break;

        }
      }
    }

    $build[] = [
      '#theme' => 'openweather',
      '#openweather_detail' => $html,
      '#attached' => array(
        'library' => array(
          'openweather/openweather_theme',
        ),
      ),
      '#cache' => array('max-age' => 0),
    ];

    if ($dependency) {
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($build, $dependency);
    }
    if (!empty($k)) {
      return $build;
    }
  }

}
