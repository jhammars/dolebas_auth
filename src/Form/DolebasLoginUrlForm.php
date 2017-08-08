<?php

namespace Drupal\dolebas_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Class DolebasLoginUrlForm.
 */
class DolebasLoginUrlForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dolebas_login_url_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#prefix'] = '<div id="my-form-wrapper-id">';
    $form['#suffix'] = '</div>';
    
    $form['email'] = [
      '#type' => 'email',
      '#maxlength' => 512,
      '#size' => 64,
    ];
    
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send login link to my email'),
      '#attributes' => [
          'class' => [
              'btn',
              'btn-md',
              'btn-primary',
              'use-ajax-submit'
          ]
      ],   
      '#ajax' => [
        'wrapper' => 'my-form-wrapper-id',
        'callback' => array($this, 'submissionMessageAjax'),        
      ],
      '#suffix' => '<span class="email-valid-message"></span>'      
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Ajax callback to send login link and display message after form submission.
   */
  public function submissionMessageAjax(array &$form, FormStateInterface $form_state) {

    
    // Get current user mail
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $current_user_email = $user->get('mail')->value;

    // If the current user email is null or nr%@dolebas.com, add the email to dolebas_user_email
    if ($current_user_email == null or preg_match('/@dolebas.com/',$current_user_email)) {
      $uuid = \Drupal::service('uuid')->generate();
      $node = \Drupal\node\Entity\Node::create(array(
          'type' => 'dolebas_user_email',
          'uuid' => $uuid,
          'title' => $uuid,
          'field_dolebas_user_email' => $form_state->getValue('email'),
          'field_dolebas_user_email_source' => 'DolebasLoginUrlForm'
      ));
      $node->save();
    // Otherwise, create a new user
    } else {
      // -- Generate random username and password
      $uuid_service = \Drupal::service('uuid');
      $uuid = $uuid_service->generate();
      $username = $uuid;
      $pass = user_password();
      // -- Create new user
      $user = \Drupal\user\Entity\User::create();
      $user->setPassword($pass);
      $user->enforceIsNew();
      $user->setUsername($username);
      $user->setEmail($form_state->getValue('email'));
      $user->addRole('dolebas_unverified');
      $user->activate();
      $user->save();
    }

    // Get uid from email
    $query = \Drupal::entityQuery('user')
    ->condition('mail', $form_state->getValue('email'));
    $nids = $query->execute();
    $uid = reset($nids);

    // Generate auto login link
    $previous_url = \Drupal::request()->server->get('HTTP_REFERER');
    $auto_login_link_destination = $previous_url;
    $host = \Drupal::request()->getSchemeAndHttpHost() . '/';
    $auto_login_url = $host . \Drupal::service('auto_login_url.create')->create($uid, $auto_login_link_destination);

    // Configure and send mail
    $from = 'info@dolebas.com';
    $to = $form_state->getValue('email');
    $subject = 'Here is your login link';
    $body = "Use the link to sign in to your account: " . $auto_login_url;
    simple_mail_send($from, $to, $subject, $body);

    // Return message  
    $response = new AjaxResponse();
    $message = $this->t('A link should arrive in your mailbox in just a few seconds...');
    $response->addCommand(new HtmlCommand('.email-valid-message', $message));
    return $response;
  }

}
