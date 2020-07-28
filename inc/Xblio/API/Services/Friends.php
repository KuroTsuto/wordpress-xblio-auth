<?php
namespace BosconianDynamics\XblioAuth\Xblio\API\Services;

use BosconianDynamics\XblioAuth\Xblio\API\Service;

class Friends extends Service {
  protected $path = 'friends';

  public function list( string $xuid = null ) {
    $params = [];

    if( $xuid )
      $params[ 'xuid' ] = $xuid;

    return $this->request( null, 'GET', $params );
  }

  public function search( string $gamertag ) {
    throw new \Error('Not Implemented');
  }

  public function add( string $xuid ) {
    return $this->request( 'add/' . $xuid );
  }

  public function remove( string $xuid ) {
    return $this->request( 'remove/' . $xuid );
  }

  public function add_favorite( $xuids ) {
    if( is_string( $xuids ) )
      $xuids = [ $xuids ];
    
    return $this->request(
      'favorite',
      'POST',
      [
        'xuids' => $xuids
      ]
    );
  }

  public function remove_favorite( $xuids ) {
    if( is_string( $xuids ) )
      $xuids = [ $xuids ];
    
    return $this->request(
      'favorite/remove',
      'POST',
      [
        'xuids' => $xuids
      ]
    );
  }
}