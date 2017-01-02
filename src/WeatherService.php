<?php
namespace Drupal\openweather;

use Drupal\Component\Utility\Html;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * WeatherService.
 */
class WeatherService {

  /**
   * Get a complete query for the API.
   */
  public function createRequest($options) {
    $query = [];
    $my_config = \Drupal::config('openweather.settings')->get('appid');
    $query['appid'] = $my_config;
    $input_data = Html::escape($options['input_value']);
    switch ($options['radio_value']) {
      case 'city_id':
        $query['id'] = $input_data;
        break;

      case 'city_name':
        $query['q'] = $input_data;
        break;

      case 'geo_coord':
        $pieces = explode(",", $input_data);
        $query['lat'] = $pieces[0];
        $query['lon'] = $pieces[1];
        break;

      case 'zip_code':
        $query['zip'] = $input_data;
        break;
    }
    return $query;
  }

  /**
   * Return the data from the API in xml format.
   */
  public function getWeatherInformation($options) {
    try {
      $client = new Client(['base_uri' => 'http://api.openweathermap.org/']);
      $response = $client->request('GET', '/data/2.5/weather',
      [
        'query' => $this->createRequest($options),
      ]);
    }
    catch (GuzzleException $e) {
      watchdog_exception('openweather', $e);
      return FALSE;
    }
    $r = $response->getBody();
    $data = $r->getContents();
    return $data;
  }

}
