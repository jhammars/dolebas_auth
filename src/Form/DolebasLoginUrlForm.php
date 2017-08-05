<?php

namespace Drupal\dolebas_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Enter your email'),
//      '#description' => $this->t('Enter you email to login'),
      '#maxlength' => 512,
      '#size' => 64,
//      '#default_value' => $config->get('wistia_token'),
    ];
    
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get uid from email
    $query = \Drupal::entityQuery('user')
    ->condition('mail', $form_state->getValue('email'));
    $nids = $query->execute();
    $uid = reset($nids);

    // Generate auto login link
    $auto_login_link_destination = 'user/dolebas-login-url';
    $host = \Drupal::request()->getSchemeAndHttpHost() . '/';
    $auto_login_url = $host . \Drupal::service('auto_login_url.create')->create($uid, $auto_login_link_destination);

    // Configure and send mail
    $from = 'info@dolebas.com';
    $to = $form_state->getValue('email');
    $subject = 'Here comes your login link';
    $body = "The link goes here: " . $auto_login_url;
    simple_mail_send($from, $to, $subject, $body);

  }

}
