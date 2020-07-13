<?php
namespace BosconianDynamics\XblioAuth;

use Exception;
use WP_Query;
use BosconianDynamics\XblioAuth\Route;

class Router {
  const QUERY_PARAM_PREFIX = 'bd_router';
  const ROUTER_QUERY_PARAM = 'bd_router_id';
  const ROUTE_QUERY_PARAM  = 'bd_router_route';

  protected $id;
  protected $root_path;
  protected $routes;

  public function __construct( string $id, string $root_path = '', array $routes = [] ) {
    $this->id = $id;
    $this->root_path = $root_path;

    foreach( $routes as $route )
      $this->use( $route );

    \add_action( 'init', [ $this, 'register_rewrites' ], 100 );
    \add_action( 'pre_get_posts', [ $this, 'route_request' ] );
  }

  /**
   * Register prefixed rewrite rules and tags for provided routes
   */
  public function register_rewrites() {
    $all_query_params = [];

    foreach( $this->routes as $route ) {
      $query_params = $route->get_params();
      
      $query_vars = [];
      $query_vars[ static::ROUTER_QUERY_PARAM ] = $this->id;
      $query_vars[ static::ROUTE_QUERY_PARAM ] = $route->get_id();
      
      foreach( $query_params as $i => $param ) {
        $param = $this->prefix_query_param( $param );
        
        if( !in_array( $param, $all_query_params ) )
          $all_query_params[] = $param;
        
        $query_vars[ $param ] = '$matches[' . ($i + 1) . ']';
      }

      $path = $route->get_path();

      if( !empty( $this->root_path ) ) {
        if( $path[0] === '^' )
          $path = substr( $path, 1 );
        
        $path = '^' . $this->root_path . '/' . $path;
      }

      \add_rewrite_rule( $path, 'index.php?' . \build_query( $query_vars ), 'top' );
    }

    foreach( $all_query_params as $query_param )
      \add_rewrite_tag( '%' . $query_param . '%', '([^&]+)' );

    \add_rewrite_tag( '%' . static::ROUTER_QUERY_PARAM . '%', '([^&]+)' );
    \add_rewrite_tag( '%' . static::ROUTE_QUERY_PARAM . '%', '([^&]+)' );
  }

  /**
   * 
   */
  public function use( $route, callable $handler = null ) {
    if( is_string( $route ) ) {
      if( !isset( $this->routes[ $route ] ) )
        throw new Exception( 'Unkown route id "' . $route . '"' );
      elseif( isset( $handler ) )
        $this->routes[ $route ]->add_handler( $handler );
    }
    elseif( $route instanceof Route ) {
      $id = $route->get_id();

      if( !isset( $this->routes[ $id ] ) ) {
        $route->add_action( 'bd_router_' . $this->id . '_route_' . $id );
        $this->routes[ $id ] = $route;
      }

      if( isset( $handler ) )
        $route->add_handler( $handler );
    }
    else {
      throw new Exception( 'Invalid $route type' );
    }
  }

  /**
   * Execute route handlers and actions based on query parameters. Handlers and
   * actions receive a positional argument for each route parameter in the order
   * which they were defined, followed by an extra argument containing the
   * WP_Query object which triggered the route.
   */
  public function route_request( WP_Query $query ) {    
    if( !$this->is_router_query( $query ) )
      return;
    
    $route = $this->get_query_route( $query );

    $query_vars = [];

    foreach( $query->query_vars as $param => $val )
      $query_vars[ $this->normalize_query_param( $param ) ] = $val;

    $params = array_map(
      function ( $param ) use( $query_vars ) {
        return $query_vars[ $param ];
      },
      $route->get_params()
    );

    $route->execute( $params, $query );
  }

  /**
   * Checks if query was created via rewrites registered by this router.
   */
  public function is_router_query( WP_Query $query = null ) {
    global $wp_query;

    if( !$query )
      $query = $wp_query;
    
    return isset( $query->query_vars[ static::ROUTER_QUERY_PARAM ] )
      && $query->query_vars[ static::ROUTER_QUERY_PARAM ] === $this->id;
  }

  /**
   * Get a registered Route associated with a WP_Query object, if any.
   */
  public function get_query_route( WP_Query $query = null ) {
    global $wp_query;

    if( !$query )
      $query = $wp_query;
    
    if( !$this->is_router_query( $query ) )
      return;
    
    //die(print_r($query->query_vars, true));
    //die(print_r($this->routes, true));
    
    return $this->routes[ $query->query_vars[ static::ROUTE_QUERY_PARAM ] ];
  }

  public function prefix_query_param( string $param ) {
    $prefix = static::QUERY_PARAM_PREFIX . '_' . $this->id . '_';

    if( strpos( $param, $prefix ) === 0 )
      return $param;
    
    return static::QUERY_PARAM_PREFIX . '_' . $this->id . '_' . $param;
  }

  public function normalize_query_param( string $param ) {
    $prefix = static::QUERY_PARAM_PREFIX . '_' . $this->id . '_';

    if( strpos( $param, $prefix ) !== 0 )
      return $param;

    return substr( $param, strlen( $prefix ) );
  }
}