<?php
namespace BosconianDynamics\XblioAuth\Xblio\API\Services;

use BosconianDynamics\XblioAuth\Xblio\API\Service;

class Achievements extends Service {
  protected $path = 'achievements';

  public function get_title_details( string $title_id, string $ctoken = null, string $max_items = null ) {
    $params = [];

    if( $ctoken )
      $params['continuation_token'] = $ctoken;
    
    if( $max_items )
      $params['max_items'] = $max_items;

    $data = $this->request(
      'title/' . $title_id,
      'GET',
      $params
    );

    return $data;
  }

  public function get_user_title_details( string $xuid, string $title_id, string $ctoken = null, string $max_items = null ) {
    $params = [];

    if( $ctoken )
      $params['continuation_token'] = $ctoken;
    
    if( $max_items )
      $params['max_items'] = $max_items;

    $data = $this->request(
      'player/' . $xuid . '/title/' . $title_id,
      'GET',
      $params
    );

    return $data;
  }

  public function get_user_summary( string $xuid = null, string $ctoken = null, int $max_items = null ) {
    $params = [];

    if( $ctoken )
      $params['continuation_token'] = $ctoken;
    
    if( $max_items )
      $params['max_items'] = $max_items;

    $data = $this->request(
      $xuid ? 'player/' . $xuid : null,
      'GET',
      $params
    );

    return $data;
  }

  public function get_user_title_summary( string $title_id ) {
    $data = $this->request( $title_id );

    return $data;
  }
}