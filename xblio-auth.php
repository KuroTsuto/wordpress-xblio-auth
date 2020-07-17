<?php
/**
 * Plugin Name:       XBL.io Authentication
 * Plugin URI:        https://github.com/bosconian-dynamics/wordpress-xblio-auth
 * Description:       Enables Xbox Live authentication and API requests by way of the https://xbl.io service.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Adam Bosco
 * License:           LGPL v2.1 or later
 */

require_once __DIR__ . '/vendor/autoload.php';

if( !class_exists( 'ReduxFramework' ) )
  require_once __DIR__ . '/vendor/reduxframework/redux-framework-4/redux-core/framework.php';

require_once __DIR__ . '/config/options.php';

use BosconianDynamics\XblioAuth\IAuthStrategy;
use \DI\ContainerBuilder;

use function BosconianDynamics\XblioAuth\rrmdir;

class XblioAuthPlugin {
  const CONTAINER_CACHE_DIR = 'build/php-di';
  const REDUX_OPTION_KEY    = 'bd_xblio_auth';

  protected static $instance = null;

  protected $container;
  protected $router = null;
  protected $auth = null;
  protected $provider;
  protected $dev_mode;

  protected function __construct() {
    $this->dev_mode = defined( 'WP_DEBUG' ) && WP_DEBUG;

    $container_cache_dir = __DIR__ . '/' . static::CONTAINER_CACHE_DIR;

    $builder = new ContainerBuilder();
    $builder->addDefinitions( __DIR__ . '/config/dependencies.php' );

    if( !$this->dev_mode ) {
      $builder->enableCompilation( $container_cache_dir );
      $builder->writeProxiesToFile( true, $container_cache_dir . '/proxies' );
    }
    else if( file_exists( $container_cache_dir ) ) {
      rrmdir( $container_cache_dir );
    }

    $this->container  = $builder->build();
    $this->router     = $this->container->get( 'auth.router' );
    $this->provider   = $this->container->get( 'auth.provider.xblio' );

    $this->container
      ->get( 'route.auth_provider_action' )
      ->add_handler( [$this, 'route_authentication'] );

    \add_action( 'init', [$this, 'register_rewrite_tags'] );
    \add_action( 'xblio_auth_auth_success', [$this, 'update_xblio_usermeta'], 10, 3 );
    \add_action( 'xblio_auth_auth_success', [$this, 'authentication_redirect'] );
    
    \add_filter( 'pre_get_avatar_data', [$this, 'get_xbox_avatar_data'], 10, 2 );
  }

  public static function get_option( string $key, $default = null ) {
    return Redux::get_option( static::REDUX_OPTION_KEY, $key, $default );
  }

  public static function get_instance() {
    if( !static::$instance )
      static::$instance = new static();
    
    return static::$instance;
  }

  public function get_xbox_avatar_data( array $args, $id_or_email ) {
    if( !is_numeric( $id_or_email ) )
      return $args;
    
    if(
      !(bool) static::get_option( 'force_xbl_avatar' )
      && !(bool) \get_user_meta( $id_or_email, 'xblio_auth_use_avatar', true )
    ) {
      return $args;
    }
    
    $args['url'] = \get_user_meta( $id_or_email, 'xblio_auth_profile', true )['avatar'];
    // TODO: set other avatar data args

    return $args;
  }

  /**
   * Register query tags not managed by the router
   */
  public function register_rewrite_tags() {
    \add_rewrite_tag( '%code%', '([^&])+' );
  }

  public function update_xblio_usermeta( \WP_User $user, array $profile, IAuthStrategy $strategy ) {
    if( $strategy->get_id() !== 'xblio' )
      return;

    $user_id = $user->ID;

    // TODO: Gravatar default image can trip this - need a different way to determine if the user has an avatar set
    if( !\get_avatar_url( $user_id ) ) {
      // Set meta to trigger get_avatar_data filter replacing gravatar
      \update_user_meta( $user_id, 'xblio_auth_use_avatar', true );
    }

    \update_user_meta( $user_id, 'xblio_auth_app_key', $profile['app_key'] );
    \update_user_meta(
      $user_id,
      'xblio_auth_profile',
      [
        'avatar'       => $profile['avatar'],
        'gamertag'     => $profile['gamertag'],
        'xuid'         => $profile['xuid'],
        'level'        => $profile['level'],
        'gamerscore'   => $profile['gamerscore'],
        'last_updated' => time()
      ]
    );
  }

  public function authentication_redirect() {
    \wp_redirect( static::get_option( 'auth_success_redirect', '/' ) );
    exit;
  }

  /**
   * Route authentication requests to the authentication controller.
   */
  public function route_authentication( string $provider, string $action, \WP_Query $query ) {
    $strategy_name = 'auth.strategy.' . $provider;

    // If there isn't a strategy for the specified provider, bail.
    if( !$this->container->has( $strategy_name ) )
      return;
    
    $controller = $this->container->get( 'auth.controller' );
    $controller->use( $this->container->get( $strategy_name ) ); // Load up the appropriate provider strategy
    
    if( $action === 'grant' || $action === 'callback' ) {
      $this->container->call(
        [
          $controller,
          'authenticate'
        ],
        [
          'strategy_id' => $provider,
          'query'       => $query
        ]
      );
    }
  }
}

XblioAuthPlugin::get_instance();