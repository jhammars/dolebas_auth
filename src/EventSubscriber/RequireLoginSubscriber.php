<?php

namespace Drupal\dolebas_auth\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Xss;

/**
 * Subscribe to kernal request event to check authentication.
 */
class RequireLoginSubscriber implements EventSubscriberInterface {

  /**
   * Checks authentication status of visiting user.
   *
   * @param object $config
   *   Object containing module configurations.
   * @param string $route_name
   *   The current pages routing name.
   *
   * @return bool
   *   Returns TRUE if user is authenticated and FALSE otherwise.
   */
  public function checkUserAuth($config, $route_name) {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $path = \Drupal::service('path.current')->getPath();
    $path = trim(\Drupal::service('path.alias_manager')->getAliasByPath($path, $lang));
    $page_settings = \Drupal::config('system.site')->get('page');

    // Compare current URL with customizable excluded paths. Returns TRUE when
    // at least one excluded path matches the current page path. Also includes
    // custom configured user login path with exclusions.
    $exclude_paths = explode(PHP_EOL, $config->get('excluded_paths'));
    foreach ($exclude_paths as $key => $exclude_path) {
      if ($exclude_path == '<front>') {
        $front_path = \Drupal::service('path.alias_manager')->getAliasByPath($page_settings['front'], $lang);
        $exclude_paths[$key] = '/' . trim($front_path, '/ ');
      }
    }
    if ($auth_path = Xss::filterAdmin($config->get('auth_path'))) {
      $exclude_paths[] = $auth_path;
    }
    if (\Drupal::service('path.matcher')->matchPath($path, implode(PHP_EOL, $exclude_paths))) {
      return TRUE;
    }

    // Various checks to determine exceptions for current page. Returns TRUE
    // when at least one check has evaluated as TRUE.
    $checks = [
      // Authentication.
      (\Drupal::currentUser()->id() > 0),
      // Cron.
      ($route_name == 'system.cron'),
      // Update.
      ($route_name == 'system.db_update'),
      // Timezone.
      ($route_name == 'system.timezone'),
      // User Pages.
      ($route_name == 'user.login' || $route_name == 'user.register' || $route_name == 'user.pass'),
      // Drush.
      (function_exists('drupal_is_cli') && drupal_is_cli()),
    ];
    foreach ($checks as $check) {
      if ($check) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Redirect non-authenticated users to login page.
   */
  public function checkUserRedirect(GetResponseEvent $event) {
    $config = \Drupal::config('require_login.config');
    $route_name = \Drupal::request()->get(RouteObjectInterface::ROUTE_NAME);
    $path = \Drupal::service('path.current')->getPath();
    $configs = \Drupal::config('system.site')->get('page');

    // Exclude specific system paths from redirect to prevent infinite loops
    // when 'view published content' is disabled (not checked).
    $excluded = [
      '/system/403', $configs['403'],
      '/system/404', $configs['404'],
    ];
    if (in_array($path, $excluded)) {
      return;
    }

    // Check user authentication status. Non-authenticated visitors will
    // automatically be redirected to /user/login (OR custom configured
    // user login path). Display customizable message to user stating
    // authentication requirements.
    if (!$this->checkUserAuth($config, $route_name)) {

      // Display access denied message.
      $message = Xss::filterAdmin($config->get('deny_message'));
      if (!empty($message)) {
        drupal_set_message($message, 'warning');
      }

      // Prepare authentication redirect path.
      $redirect = '/user/login?destination=' . $path;
      if ($auth_path = Xss::filterAdmin($config->get('auth_path'))) {
        $redirect = $auth_path . $path;
      }

      $event->setResponse(new RedirectResponse($redirect));
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkUserRedirect'];
    return $events;
  }

}
