<?php
namespace BosconianDynamics\XblioAuth\Xblio\API\Services;

use BosconianDynamics\XblioAuth\Xblio\API\Service;

class Account extends Service {
  protected $path = 'account';

  public function get_profile_users( string $xuid ) {
    $data = $this->request( $xuid );

    return $data->profileUsers;
  }
}