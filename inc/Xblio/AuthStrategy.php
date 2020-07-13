<?php
namespace BosconianDynamics\XblioAuth\Xblio;

use BosconianDynamics\XblioAuth\IAuthStrategy;
use BosconianDynamics\XblioAuth\Xblio\APIClient;
use WP_Error;

class AuthStrategy implements IAuthStrategy {
  const ID               = 'xblio';
  const PROFILE_ID_FIELD = 'xuid';

  private $verify;

  protected $auth_url;
  protected $token_url;
  protected $api_client;
  protected $public_key;

  public function __construct( APIClient $api_client, string $public_key, string $auth_url, string $token_url, callable $verify ) {
    $this->api_client = $api_client;
    $this->public_key = $public_key;
    $this->auth_url   = $auth_url;
    $this->token_url  = $token_url;
    $this->verify     = $verify;
  }

  public function authenticate( \WP_Query $query, array $options = [] ) {
    if( !isset( $query->query_vars[ 'code' ] ) )
      $this->redirect( $this->auth_url . '/' . $this->public_key );
    
    $response = $this->api_client->request_user_auth_token( $query->query_vars[ 'code' ] );

    if( \is_wp_error( $response ) )
      return $this->error( $response );
    
    $status = $response['status']['code'];
    
    if( $status >= 400 ) {
      $message = $response['status']['message'];

      if( $status > 400 && $status < 500 ) {
        return $this->fail( ['message' => $message], $status );
      }

      return $this->error( new WP_Error( 'xblio_auth_request_token_' . $status, $message, $response ) );
    }

    // TODO: ever need to handle the case when auth returns more than one user profile? Probably not... but maybe...
    $profile = (array) $response['data']->data[0];
    $user = call_user_func( $this->verify, $this->get_id(), $profile );

    if( \is_wp_error( $user ) )
      return $this->error( $user );

    return $this->success( $user, $profile );
  }

  /**
   * Map profile fields to core WP user data
   */
  public function map_profile( $profile ) : array {
    return [
      'user_login'  => $profile['gamertag']
    ];
  }

  public function get_id() : string {
    return static::ID;
  }

  public function get_profile_id_field() : string {
    return static::PROFILE_ID_FIELD;
  }

  public function success( \WP_User $user, array $profile ) {
    if( !\is_user_logged_in() ) {
      \wp_set_auth_cookie( $user->ID, true );
      \wp_set_current_user( $user->ID );
      die('Login Success!');
    }

    die('Connection Success!');
  }

  public function fail( $challenge, $status = 401 ) {

  }

  public function redirect( $url, $status = 302 ) {
    \wp_redirect( $url, $status );
    exit;
  }

  public function error( $err ) {

  }
}