<?php
namespace BosconianDynamics\XblioAuth;

use Exception;

class Route {
  protected $id;
  protected $path;
  protected $params;
  protected $actions;
  protected $handlers;

  public function __construct( string $id, string $path, array $params = [], array $callbacks = [] ) {
    $this->id      = $id;
    $this->path    = $path;
    $this->params  = $params;

    foreach( $callbacks as $callback ) {
      if( is_callable( $callback ) )
        $this->add_handler( $callback );
      elseif( is_string( $callback ) )
        $this->add_action( $callback );
      else
        throw new Exception( 'Unkown callback type.' );
    }
  }

  public function add_action( string $action ) {
    $this->actions[] = $action;
  }

  public function add_handler( callable $handler ) {
    $this->handlers[] = $handler;
  }

  public function execute( array $params, \WP_Query $query = null ) {
    if( $query )
      $params[] = $query;

    foreach( $this->handlers as $handler )
      call_user_func_array( $handler, $params );
    
    foreach( $this->actions as $action )
      call_user_func_array( 'do_action', array_merge( [ $action ], $params ) );
  }

  public function get_id() {
    return $this->id;
  }

  public function get_params() {
    return $this->params;
  }

  public function get_path() {
    return $this->path;
  }
}