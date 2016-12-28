<?php
namespace Drupal\openweather;

use GuzzleHttp\Client;

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
    switch ($options['radio_value']) {
      case 'city_id':
        $query['id'] = $options['input_value'];
        break;

      case 'city_name':
        $query['q'] = $options['input_value'];
        break;

      case 'geo_coord':
        $pieces = explode(",", $options['input_value']);
        $query['lat'] = $pieces[0];
        $query['lon'] = $pieces[1];
        break;

      case 'zip_code':
        $query['zip'] = $options['input_value'];
        break;
    }
    return $query;
  }

  /**
   * Return the data from the API in xml format.
   */
  public function getWeatherInformation($options) {
    $client = new Client(['base_uri' => 'http://api.openweathermap.org/']);
    $response = $client->request('GET',
      '/data/2.5/weather',
      [
        'query' => $this->createRequest($options),
      ]);
    $r = $response->getBody();
    $data = $r->getContents();
    return $data;
  }

}
