<?php
/**
 * Plugin Name: XBL.io Authentication
 * Description:       Enables Xbox Live authentication and API requests by way of the https://xbl.io service.
 * Version:           0.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Adam Bosco
 * Author URI:        https://adambos.co
 * License:           LGPL v2.1 or later
 */

require_once 'vendor/autoload.php';

//require_once 'vendor/reduxframework/redux-framework-4/redux-core/framework.php';
if( !class_exists( 'ReduxFramework' ) )
  require_once dirname( __FILE__ ) . '/vendor/reduxframework/redux-framework-4/redux-core/framework.php';

require_once 'config/options.php';

use \DI\ContainerBuilder;

class XblioAuthPlugin {
  const CONTAINER_CACHE_DIR = 'build/php-di';

  protected static $instance = null;

  protected $container;
  protected $router = null;
  protected $auth = null;
  protected $provider;
  protected $dev_mode;

  protected function __construct() {
    $this->dev_mode = defined( 'WP_DEBUG' ) && WP_DEBUG;

    $builder = new ContainerBuilder();
    $builder->addDefinitions( __DIR__ . '/config/dependencies.php' );

    if( !$this->dev_mode ) {
      $builder->enableCompilation( __DIR__ . '/' . static::CONTAINER_CACHE_DIR );
      $builder->writeProxiesToFile( true, __DIR__ . '/' . static::CONTAINER_CACHE_DIR . '/proxies' );
    }

    $this->container  = $builder->build();

    $this->router     = $this->container->get( 'auth.router' );
    $this->provider   = $this->container->get( 'auth.provider.xblio' );
    $this->auth       = $this->container->get( 'auth.controller' );

    $this->container
      ->get( 'route.auth_provider_action' )
      ->add_handler( [$this, 'route_authentication'] );

    \add_action( 'init', [$this, 'register_rewrite_tags'] );
  }

  public static function getInstance() {
    if( !static::$instance )
      static::$instance = new static();
    
    return static::$instance;
  }

  /**
   * Register query tags not managed by the router
   */
  public function register_rewrite_tags() {
    \add_rewrite_tag( '%code%', '([^&])+' );
  }

  public function route_authentication( string $provider, string $action, \WP_Query $query ) {
    // Only handle the xblio provider (for now...)
    if( $provider !== $this->provider )
      return;
    
    // Load up the appropriate provider strategy
    $this->auth->use( $this->container->get( 'auth.strategy.' . $provider ) );
    
    if( $action === 'grant' || $action === 'callback' ) {
      $this->container->call(
        [
          $this->auth,
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

XblioAuthPlugin::getInstance();