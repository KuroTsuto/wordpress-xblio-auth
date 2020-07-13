<?php
namespace BosconianDynamics\XblioAuth\Xblio;

class User {
  const USERMETA_KEY_XUID       = 'xblio-auth-xuid';
  const USERMETA_KEY_AUTH_TOKEN = 'xblio-auth-auth-token';

  protected $xuid;
  private $auth_token;

  public function __construct( string $xuid, string $auth_token, array $data ) {
    $this->xuid = $xuid;
    $this->auth_token = $auth_token;

    $this->data = wp_parse_args(
      $data,
      [
        'gamertag'   => '',
        'avatar_url' => '',
        'gamerscore' => 0,
        'level'      => ''
      ]
    );
  }

  public static function from_wp_user_id( $id ) {
    
  }

  public function get_auth_token() {
    return $this->auth_token;
  }

  public function get_xuid() {
    
  }

  public function get_meta( string $key ) {

  }
}