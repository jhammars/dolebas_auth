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
    
    // Pass along user email to the theme
    $build = [];
    $build['dolebas_user_display_block']['#theme'] = 'dolebas_user_display_block';
    $build['dolebas_user_display_block']['#dolebas_user_email'] = $current_user_email;
    return $build;
  }

}
