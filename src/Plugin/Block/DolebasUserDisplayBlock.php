<?php

namespace Drupal\dolebas_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'DolebasUserDisplayBlock' block.
 *
 * @Block(
 *  id = "dolebas_user_display_block",
 *  admin_label = @Translation("Dolebas User display block"),
 * )
 */
class DolebasUserDisplayBlock extends BlockBase {

  /**
   * Fetch the current user's email and display it via #theme
   */
  public function build() {
    
    // Load the current user.
    $current_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $current_user_email = $current_user->get('mail')->value;
    
    // Attach library and pass along user email to the theme
    $build['#theme'] = 'dolebas_user_display_block';
    $build['#dolebas_user_email'] = $current_user_email;
    $build['#attached']['library'] = 'dolebas_user/dolebas-user-display-block';
    $build['#cache']['max-age'] = 0;

    return $build;
  }

}
