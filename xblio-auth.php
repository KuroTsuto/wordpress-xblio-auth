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
 *
 * @package BosconianDynamics\XblioAUth
 */

namespace BosconianDynamics\XblioAuth;

require_once __DIR__ . '/vendor/autoload.php';

if( ! class_exists( 'ReduxFramework' ) )
  require_once __DIR__ . '/vendor/reduxframework/redux-framework-4/redux-core/framework.php';

require_once __DIR__ . '/config/options.php';

use \BosconianDynamics\XblioAuth\Plugin as XblioAuthPlugin;

/**
 * Initialize the plugin. Set up core hooks.
 *
 * @return void
 */
function init() : void {
  $instance = XblioAuthPlugin::get_instance();

  \add_action( 'init', [ $instance, 'register_rewrite_tags' ] );
  \add_action( 'xblio_auth_auth_success', [ $instance, 'update_xblio_usermeta' ], 10, 3 );
  \add_action( 'xblio_auth_auth_success', [ $instance, 'authentication_redirect' ] );

  \add_filter( 'pre_get_avatar_data', [ $instance, 'get_xbox_avatar_data' ], 10, 2 );
}

init();
