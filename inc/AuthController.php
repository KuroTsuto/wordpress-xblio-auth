<?php
namespace BosconianDynamics\XblioAuth;

use BosconianDynamics\XblioAuth\IAuthStrategy;
use WP_Error;
use WP_User;

class AuthController {
  const META_KEY_STRAT_ID_PREFIX = 'xblio_auth_';
  const ACTION_PREFIX = 'xblio_auth_';

  protected $strategies = [];

  public function __construct(  ) {
    
  }

  public function use( IAuthStrategy $strategy ) {
    $id = $strategy->get_id();
    $this->strategies[ $id ] = $strategy;
  }

  public function authenticate( string $strategy_id, \WP_Query $query, array $options = [] ) {
    return $this->strategies[ $strategy_id ]->authenticate( $query, $options );
  }

  public function get_or_create_user( string $strategy_id, array $profile ) : WP_User {
    if( \is_user_logged_in() )
      $user = \wp_get_current_user();
    else
      $user = $this->get_user_by_profile( $strategy_id, $profile );

    $strategy = $this->get_strategy( $strategy_id );

    if( !$user || !($user instanceof \WP_User) ) {
      // If we can't find a matching WP User, create a new one
      // TODO: make auto-registration configurable. Optionally, redirect to wp-login.php?action=register and pre-fill fields
      $data = $strategy->map_profile( $profile );

      $data['user_pass'] = \wp_generate_password();
      $data['role']      = 'subscriber';
      
      $user_id = \wp_insert_user( $data );

      $user = \get_user_by( 'id', $user_id );
    }

    // TODO: this meta doesn't need to update every single get_or_create
    $this->set_user_profile_id( $strategy_id, $profile[ $strategy->get_profile_id_field() ], $user );

    return $user;
  }

  public function get_strategy( string $strategy_id ) {
    return $this->strategies[ $strategy_id ];
  }

  public function get_user_by_profile( string $strategy_id, array $profile ) {
    return $this->get_user_by_profile_id(
      $strategy_id,
      $profile[ $this->get_strategy( $strategy_id )->get_profile_id_field() ]
    );
  }

  public function get_user_by_profile_id( string $strategy_id, $value ) {
    if( !$value )
      return false;
    
    $users = \get_users([
      'meta_key'   => static::META_KEY_STRAT_ID_PREFIX . $strategy_id . '_id',
      'meta_value' => $value
    ]);

    if( !$users || count( $users ) === 0 )
      return false;

    //TODO: handle multiple WP users connected to provider profile
    return $users[0];
  }

  public function set_user_profile_id( string $strategy_id, $value, \WP_User $user = null ) {
    if( !$user )
      $user = wp_get_current_user();
    
    if( $user->ID === 0 )
      return new WP_Error( 'Invalid user' );
    
    return \update_user_meta(
      $user->ID,
      static::META_KEY_STRAT_ID_PREFIX . $strategy_id . '_id',
      $value
    );
  }

  public function success( IAuthStrategy $strategy, \WP_User $user, array $profile ) {
    if( !\is_user_logged_in() ) {
      \wp_set_auth_cookie( $user->ID, true );
      \wp_set_current_user( $user->ID );
      
      \do_action( static::ACTION_PREFIX . 'login_success', $user, $profile, $strategy );
    }
    else {
      \do_action( static::ACTION_PREFIX . 'connect_success', $user, $profile, $strategy );
    }

    \do_action( static::ACTION_PREFIX . 'auth_success', $user, $profile, $strategy );
  }

  public function fail( IAuthStrategy $strategy, $challenge, $status = 401 ) {

  }

  public function redirect( $url, $status = 302 ) {
    \wp_redirect( $url, $status );
    exit;
  }

  public function error( IAuthStrategy $strategy, $err ) {

  }
}
