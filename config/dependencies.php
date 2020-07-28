<?php
use DI\Container;

use function DI\create;
use function DI\get;
use function DI\autowire;

use \BosconianDynamics\XblioAuth\Xblio;
use BosconianDynamics\XblioAuth\AuthController;
use BosconianDynamics\XblioAuth\Route;
use BosconianDynamics\XblioAuth\Router;
use Psr\Container\ContainerInterface;

return [
  'api.host'                   => 'xbl.io',
  'api.root_url'               => DI\string( 'https://{api.host}/api/v2' ),
  'api.auth_url'               => DI\string( 'https://{api.host}/app/auth' ),
  'api.token_url'              => DI\string( 'https://{api.host}/app/claim' ),

  'api.service.account'        => autowire( Xblio\API\Services\Account::class ),
  'api.service.achievements'   => autowire( Xblio\API\Services\Achievements::class ),
  'api.service.friends'        => autowire( Xblio\API\Services\Friends::class ),  

  Xblio\API\Client::class                => create( Xblio\API\Client::class )
    ->constructor(
      get( 'options.xblio_public_key' ),
      [
        'auth'  => get( 'api.auth_url' ),
        'token' => get( 'api.token_url' ),
        'api'   => get( 'api.root_url' )
      ]
    ),
  
  'route.auth_provider_action' => create( Route::class )
    ->constructor(
      'provider_action_route',
      '^(\w+)/(\w+)/?',
      [ 'provider', 'action' ]
    ),

  'options.key'                => 'bd_xblio_auth',
  
  'options'                    => function( Container $c ) {
    return $c->call(
      'get_option',
      [
        'option' => $c->get( 'options.key' )
      ]
    );
  },

  'options.xblio_public_key'   => function( ContainerInterface $c ) {
    return $c->get( 'options' )[ 'public_key' ];
  },

  'usermeta.keys'              => [
    'xblio.user_id' => 'xblio-auth-xblio-xuid'
  ],
  
  'auth.path'                  => 'auth',
  'auth.provider.xblio'        => 'xblio',
  'auth.actions'               => [
    'auth' => 'authenticate'
  ],

  'auth.controller'            => create( AuthController::class ),

  AuthController::class        => get( 'auth.controller' ),

  'auth.strategy.xblio'        => autowire( Xblio\AuthStrategy::class )
    ->constructorParameter( 'public_key', get( 'options.xblio_public_key') )
    ->constructorParameter( 'auth_url', get( 'api.auth_url' ) )
    ->constructorParameter( 'token_url', get( 'api.token_url' ) )
    ->constructorParameter( 'verify', [ get( 'auth.controller' ), 'get_or_create_user' ] ),

  'auth.router'                => create( Router::class )
    ->constructor(
      'auth',
      get( 'auth.path' ),
      [ get( 'route.auth_provider_action' ) ]
    )
];