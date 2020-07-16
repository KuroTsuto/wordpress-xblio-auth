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

require_once dirname( __FILE__ ) . '/build/autoload.php';

if( !class_exists( 'ReduxFramework' ) )
  require_once dirname( __FILE__ ) . '/build/vendor/reduxframework/redux-framework-4/redux-core/framework.php';

require_once dirname( __FILE__ ) . '/config/options.php';

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

  /**
   * Route authentication requests to the authentication controller.
   */
  public function route_authentication( string $provider, string $action, \WP_Query $query ) {
    $strategy_name = 'auth.strategy.' . $provider;

    // If don't have a strategy for the specified provider, bail.
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

function rrmdir($dir) { 
  if (is_dir($dir)) { 
    $objects = scandir($dir);
    foreach ($objects as $object) { 
      if ($object != "." && $object != "..") { 
        if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
          rrmdir($dir. DIRECTORY_SEPARATOR .$object);
        else
          unlink($dir. DIRECTORY_SEPARATOR .$object); 
      } 
    }
    rmdir($dir); 
  } 
}

XblioAuthPlugin::getInstance();