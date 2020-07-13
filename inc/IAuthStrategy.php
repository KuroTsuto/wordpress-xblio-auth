<?php
namespace BosconianDynamics\XblioAuth;

interface IAuthStrategy {
  public function authenticate( \WP_Query $query, array $options );

  public function get_id() : string;

  public function get_profile_id_field() : string;

  public function map_profile( $profile ) : array;
}