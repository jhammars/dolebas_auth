<?php

namespace Drupal\dolebas_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class RedirectController.
 */
class RedirectController extends ControllerBase {

  /**
   * Create a new user and redirect to path
   */
  public function auth_redirect($path = '') {

    // Generate random username and password
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();
    $username = $uuid;
    $pass = user_password();

    // Create new user
    $user = \Drupal\user\Entity\User::create();
    $user->setPassword($pass);
    $user->enforceIsNew();
    $user->setUsername($username);
    $user->addRole('dolebas_unverified');
    $user->activate();
    $user->save();
    $user_id = $user->id();
    $user->setEmail('nr' . $user_id . '@dolebas.com');
    $user->save();
    
    // Sign in the new user
    user_login_finalize($user);
    
    // Redirect to path
    return new RedirectResponse('/user');
    
  }

}
