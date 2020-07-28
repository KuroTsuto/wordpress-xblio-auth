<?php
namespace BosconianDynamics\XblioAuth\Xblio\API;

use BosconianDynamics\XblioAuth\Xblio\API\Client;

class Service {
  protected $client;
  protected $path;

  public function __construct( Client $client ) {
    $this->client = $client;
  }

  public function request( string $path = null, string $method = 'GET', array $data = [] ) {
    $reponse = $this->client->request(
      $this->get_path( $path ),
      $method,
      $data
    );

    return $reponse['data'];
  }

  public function get_path( $path = null ) {
    $parts = [];

    if( $this->path && strlen( $this->path ) )
      $parts[] = $this->path;
    
    if( $path && strlen( $path ) )
      $parts[] = $path;

    return implode( '/', $parts );
  }
}
