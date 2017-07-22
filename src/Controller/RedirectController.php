<?php

namespace Drupal\dolebas_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class RedirectController.
 */
class RedirectController extends ControllerBase {

  /**
   * If the current user is not anonymous, redirect to previous page.
   * If the current user is anonymous, create a new user, sign in and redirect to $path
   *
   */
  public function auth_redirect($path = '') {
    
    // Temporary solution to prevent infinity redirection after auth user sign out
    // Makes anonymous access really slow
    drupal_flush_all_caches();
    
    // If current user is not anonymous, redirect to previous page
    //$previous_url= \Drupal::request()->server->get('HTTP_REFERER');
    
    // If current user is anonymous, create a new user and redirect to path in url
    
    // Create new user
    
    // -- Generate random username and password
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();
    $username = $uuid;
    $pass = user_password();

    // -- Create new user
    $user = \Drupal\user\Entity\User::create();
    $user->setPassword($pass);
    $user->enforceIsNew();
    $user->setEmail('mail@example.com');
    $user->setUsername($username);
    $user->addRole('dolebas_unverified');
    $user->activate();
    $user->save();
    user_login_finalize($user);
    
    return new RedirectResponse('/'. $path); 
    //return new RedirectResponse(URL::fromUserInput('/node')->toString());
    // Url::fromInternalUri('node/add');
    
  }

}
