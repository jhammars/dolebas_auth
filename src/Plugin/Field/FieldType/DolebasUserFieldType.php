<?php

namespace Drupal\dolebas_user\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'dolebas_user_field_type' field type.
 *
 * @FieldType(
 *   id = "dolebas_user_field_type",
 *   label = @Translation("Dolebas User Field"),
 *   description = @Translation("Executes preSave() and/or postSave()"),
 *   default_widget = "dolebas_user_widget_type",
 *   default_formatter = "dolebas_user_formatter_type"
 * )
 */
class DolebasUserFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $elements;
  }
  
  public function postSave($update) {
    
    // Check if email already exists with the node owner uid
    $entity = $this->getEntity();
    $dolebas_user_email = $entity->field_dolebas_user_email->value;
    $uid = $entity->getOwnerId();
    $query = \Drupal::entityQuery('node')
    ->condition('field_dolebas_user_email', $dolebas_user_email)
    ->condition('uid', $uid);
    $nids = $query->execute();
    $number_of_identical_emails = count($nids);
    
    // If the same email with the same uid already exists, delete the node
    if ($number_of_identical_emails > 1) {
      $entity->delete();
    }
    
    // Check if the email belongs to any user
    $query = \Drupal::entityQuery('user')
    ->condition('mail', $dolebas_user_email);
    $nids = $query->execute();
    $email_exist_on_another_user = count($nids);
    
    // If the email doesn't belong to any user
    if ($email_exist_on_another_user < 1) {
      
      // Check if any email is registrered on the node owner user account
      $user = \Drupal\user\Entity\User::load($uid);
      $user_email = $user->get('mail')->value;

      // If the registered email is null or nr%@dolebas.com, add the email to the account
      if ($user_email == null or preg_match('/@dolebas.com/',$user_email)) {
        $user->get('mail')->value = $dolebas_user_email;
        $user->save();
      }
      
    }
    
  }

}
