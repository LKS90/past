<?php
/**
 * @file
 * Contains \Drupal\past_form\Tests\PastFormTest.
 */

namespace Drupal\past_form\Tests;

use Drupal\past\PastEventInterface;
use Drupal\past_db\Entity\PastEvent;
use Drupal\simpletest\WebTestBase;

/**
 * Generic Form tests using the database backend.
 *
 * @group Past
 */
class PastFormTest extends WebTestBase {

  /**
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $config;

  /**
   * @var PastEventInterface
   */
  protected $eventToBe;

  public static $modules = array(
    'past',
    'past_db',
    'past_form',
    'past_testhidden',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->config = \Drupal::config('past_form.settings');
    $this->config->set('past_form_log_form_ids', '*')->save();
  }

  /**
   * Tests an empty submit handler array.
   */
  public function testFormEmptyArray() {
    // Given a simple form with one button with empty #submit array.
    // Submit the global submit button.
    // Check if the default form handler is executed.
    // Check the logs if the submission is captured.

    $edit = array();
    $form_id = 'past_testhidden_form_empty_submit_array';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertText('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);
  }

  /**
   * Tests the exclusion of views exposed form submission.
   */
  public function testViewsExposedForm() {
    // Check that views_exposed_filter is not recorded.
    $admin = $this->drupalCreateUser(array('view past reports'));
    $this->drupalLogin($admin);

    // The login event is logged.
    $event = $this->getLastEventByMachinename('submit');
    $this->assertTrue('Form submitted: user_login, Log in' == $event->getMessage(), 'user_login submit was not logged.');

    // First check the fake exclude for views with exposed filters.
    $this->drupalGet('admin/reports/past');

    // The last event is still the login.
    $event = $this->getLastEventByMachinename('submit');
    $this->assertTrue('Form submitted: user_login, Log in' == $event->getMessage(), 'views_exposed_form submit was not logged.');

    // Additional submits after the page load submit.
    $this->drupalPostForm('admin/reports/past', array('module' => 'watchdog'), t('Apply'));
    $event = $this->getLastEventByMachinename('submit');
    $this->assertTrue('Form submitted: user_login, Log in' == $event->getMessage(), 'views_exposed_form submit was not logged.');
    // @todo This should add the submission. Wrong currently!

  }

  /**
   * Tests a form without submit handler.
   */
  public function testFormNoSubmithandler() {
    // Given a simple form with one button with default #submit function entry.
    // Same as above.
    $edit = array();
    $form_id = 'past_testhidden_form_default_submit_handler';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertRaw('global submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a forms with global/custom submit hanlder.
   */
  public function testFormCustomSubmithandler() {
    // Given a simple form with one button with special #submit function..
    // Submit a form with global submit handler.
    // Check if the form handler is executed.
    // Check the logs if the submission is captured.
    $edit = array();
    $form_id = 'past_testhidden_form_custom_submit_handler';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertRaw('custom submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $edit = array();
    $form_id = 'past_testhidden_form_mixed_submit_handlers';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertRaw('submit handler called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a form with several submits.
   */
  public function testFormThreeButtons() {
    // Given a simple form with three buttons without specific handlers
    // Submit each button.
    // Check the logs if the submissions where captured.
    // Check the logs which button was pressed each.
    $edit = array();
    $form_id = 'past_testhidden_form_three_buttons';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $button_value = 'Button 1';
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 2';
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 3';
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a form with several submits having submit handlers.
   */
  public function testFormThreeButtonsWithHandlers() {
    // Given a simple form with three buttons.
    // Each of the buttons has an own #submit handler.
    // Submit each button.
    // Check if each specific handler is executed.
    // Check the global handler was not executed.
    // Check the logs if the submissions where captured.
    $edit = array();
    $form_id = 'past_testhidden_form_three_buttons_with_submit_handlers';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $button_value = 'Button 1';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertRaw('custom submit handler ' . $button_value . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 2';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertRaw('custom submit handler ' . $button_value . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Button 3';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertRaw('custom submit handler ' . $button_value . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
      'submit_one' => 'Button 1',
      'submit_two' => 'Button 2',
      'submit_three' => 'Button 3',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests a form with type=button submits having submit handlers.
   */
  public function testFormUseButtonAsSubmit() {
    // Capture #type=button submissions.
    $edit = array();
    $form_id = 'past_testhidden_form_normal_button';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, 'Button');
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertNoRaw('custom submit handler called by ' . $form_id);

    $this->assertNull($this->getLastEventByMachinename('submit'));
  }

  /**
   * Tests a form validation.
   */
  public function testFormValidation() {
    // Enable validation logging.
    $this->config->set('past_form_log_validations', TRUE)->save();
    // Capture case for a failing validations.
    // Capture validation error message in the past log.
    $edit = array('sample_property' => '');
    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, 'Submit');
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertFieldByXPath('//input[contains(@class, "error")]', FALSE, 'Error input form element class found.');

    $this->assertNull($this->getLastEventByMachinename('submit'));

    // Enable validation logging.
    $this->config->set('past_form_log_validations', 1)->save();

    $edit = array('sample_property' => '');
    $form_id = 'past_testhidden_form_multi_validation';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertFieldByXPath('//input[contains(@class, "error")]', FALSE, 'Error input form element class found.');

    $event = $this->getLastEventByMachinename('validate');
    $this->eventToBe = $this->getEventToBe('validate', $form_id, $button_value, 'Form validation error:');
    $errors_to_be = array(
      'sample_property' => array(
        'message' => 'Sample Property field is required.',
        'submitted' => '',
      ),
      'another_sample_property' => array(
        'message' => 'Another Sample Property field is required.',
        'submitted' => 0,
      ),
      'sample_select' => array(
        'message' => 'Sample Select: says, don\'t be a maybe ..',
        'submitted' => '2',
      ),
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, array(), $errors_to_be);

    $edit = array();
    $form_id = 'past_testhidden_form_custom_validation_only';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertNoRaw('global submit handler called by ' . $form_id);
    $this->assertFieldByXPath('//select[contains(@class, "error")]', FALSE, 'Error select form element class found.');

    $event = $this->getLastEventByMachinename('validate');
    $this->eventToBe = $this->getEventToBe('validate', $form_id, $button_value, 'Form validation error:');
    $errors_to_be = array(
      'sample_select' => array(
        'message' => 'Sample Select: says, don\'t be a maybe ..',
        'submitted' => '2',
      ),
    );
    // @todo wrong!
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, array(), $errors_to_be);

    // Test validation logging of nested form elements.
    $form_id = 'past_testhidden_form_nested';
    $button_value = t('Submit');
    $this->drupalGet($form_id);
    $edit = array('wrapper[field_1]' => 'wrong value');
    $this->drupalPostForm(NULL, $edit, $button_value);
    // Check for correct validation error messages and CSS class on field.
    $this->assertText(t("Field 1 doesn't contain the right value"), 'Validation error message gets displayed.');
    $this->assertFieldByXPath('//input[contains(@class, "error")]', FALSE, 'Error class was found on textfield 1.');
    // Load latest validation log record and create an artificial that contains
    // exactly what a correct logging is supposed to contain.
    $event = $this->getLastEventByMachinename('validate');
    $this->eventToBe = $this->getEventToBe('validate', $form_id, $button_value, 'Form validation error:');
    $errors_to_be = array(
      'wrapper][field_1' => array(
        'message' => t("Field 1 doesn't contain the right value"),
        'submitted' => $edit['wrapper[field_1]'],
      ),
    );
    // Compare artificial event with logged event.
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, array(), $errors_to_be, 'Validation of nested elements was logged correctly.');

    // Test if submission is logged as expected.
    $edit = array(
      'wrapper[field_1]' => 'correct value',
      'wrapper[field_2]' => 'some other value',
    );
    $this->drupalPostForm(NULL, $edit, $button_value);
    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'wrapper' => array(
        'field_1' => $edit['wrapper[field_1]'],
        'field_2' => $edit['wrapper[field_2]'],
      ),
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be, array(), 'Submission of nested elements was logged correctly');
  }

  /**
   * Tests a multi step form.
   */
  public function testFormMultistep() {
    // Capture multistep submissions.
    $edit = array();
    $form_id = 'past_testhidden_form_multistep';
    $button_value = 'Next';
    $step = 1;
    $this->drupalGet($form_id);
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertRaw('global submit handler step ' . $step . ' called by ' . $form_id);
    $step++;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
      'next' => 'Next',
    );

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertRaw('global submit handler step ' . $step . ' called by ' . $form_id);
    $step++;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property_2' => 'sample value 2',
      'next' => 'Next',
      'back' => 'Back',
    );

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Back';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertRaw('global submit handler step ' . $step . ' called by ' . $form_id);
    $step--;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property_2' => 'sample value 2',
      'next' => 'Next',
      'back' => 'Back',
    );

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Next';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertRaw('global submit handler step ' . $step . ' called by ' . $form_id);
    $step++;
    $this->assertRaw('form handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'back' => 'Back',
    );

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);

    $button_value = 'Submit';
    $this->drupalPostForm(NULL, $edit, $button_value);
    $this->assertRaw('global submit handler step ' . $step . ' called by ' . $form_id);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property_3' => 'sample value 3',
      'submit' => $button_value,
    );

    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests an ajax form.
   */
  public function testFormAjax() {
    // Capture ajax submissions.
    $edit = array();
    $form_id = 'past_testhidden_form_simple_ajax';
    $button_value = 'Submit';
    $this->drupalGet($form_id);
    $this->assertRaw('form handler called by ' . $form_id);
    $values = $this->drupalPostAJAXForm(NULL, $edit, array('op' => $button_value), 'system/ajax', array(), array(), str_replace('_', '-', $form_id));

    $got_global_handler = FALSE;
    $got_custom_handler = FALSE;
    $got_ajax = FALSE;
    foreach ($values as $value) {
      foreach ($value as $key => $val) {
        if ($key == 'data') {
          if (strrpos($val, 'global submit handler called by ' . $form_id) !== FALSE) {
            $got_global_handler = TRUE;
          }
          elseif (strrpos($val, 'custom submit handler called by ' . $form_id) !== FALSE) {
            $got_custom_handler = TRUE;
          }
          elseif (strrpos($val, 'ajax called by ' . $form_id . ' with sample_property containing: sample value') !== FALSE) {
            $got_ajax = TRUE;
          }
        }
      }
    }
    $this->assertFalse($got_global_handler, 'Global submit handler for ' . $form_id . ' was called.');
    $this->assertTrue($got_custom_handler, 'Custom submit handler for ' . $form_id . ' was called.');
    $this->assertTrue($got_ajax, 'Expected AJAX value is in JSON response.');

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $values_to_be = array(
      'sample_property' => 'sample value',
    );
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $values_to_be);
  }

  /**
   * Tests the include filter.
   */
  public function testFormIDFilter() {
    $edit = array();
    $form_id = 'past_testhidden_form_empty_submit_array';
    $button_value = 'Submit';

    // Test exclusion.
    $this->config->set('past_form_log_form_ids', '')->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Event shouldn't be logged.
    $this->assertNull($this->getLastEventByMachinename('submit'));

    // Test inclusion.
    $this->config->set('past_form_log_form_ids', $form_id)->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // This new event should not be logged, so still the old event should be
    // fetched.
    $event = $this->getLastEventByMachinename('submit');
    $this->assertSameEvent($event, $this->eventToBe, 'past_testhidden_form_empty_submit_array', $button_value);

    // Test inclusion of more then one form_id.
    $this->config
      ->set('past_form_log_form_ids', $form_id . "\n" . $this->config->get('past_form_log_form_ids', ''))
      ->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Now the new event should be found.
    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    // Test wildcard.
    $this->config->set('past_form_log_form_ids', '*testhidden_form_*')->save();
    $form_id = 'past_testhidden_form_empty_submit_array';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Again the first form_id should be found.
    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // And also the new one.
    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    // Check if user registration is not logged.
    $this->drupalGet('user/register');
    $register_edit = array(
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    );
    $register_button_value = t('Create new account');
    $this->drupalPostForm(NULL, $register_edit, $register_button_value);

    // Load last logged submission and check whether it's not the user register
    // submission.
    $event = $this->getLastEventByMachinename('submit');
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value, $edit);

    // Test wildcard that will match all forms.
    $this->config->set('past_form_log_form_ids', '*')->save();
    $form_id = 'past_testhidden_form_empty_submit_array';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // Again the first form_id should be found.
    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    $form_id = 'past_testhidden_form_custom_submit_handler';
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    // And also the new one.
    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);

    // Check if user registration is logged.
    $form_id = 'user_register_form';
    $register_edit = array(
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    );
    $this->drupalGet('user/register');
    $this->drupalPostForm(NULL, $register_edit, $register_button_value);

    // Check if event was logged.
    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $register_button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $register_button_value, $register_edit);

    // Test that additional spaces and special characters don't confuse the form
    // ID check.
    $form_id = 'past_testhidden_form_empty_submit_array';
    $this->config->set('past_form_log_form_ids', ' ' . $form_id . " \r\n other_form")->save();
    $this->drupalGet($form_id);
    $this->drupalPostForm(NULL, $edit, $button_value);

    $event = $this->getLastEventByMachinename('submit');
    $this->eventToBe = $this->getEventToBe('submit', $form_id, $button_value);
    $this->assertSameEvent($event, $this->eventToBe, $form_id, $button_value);
  }

  /**
   * Returns the last event with a given machine name.
   *
   * @param string $machine_name
   *   Machine name of a past event entity.
   *
   * @return PastEventInterface
   *   The loaded past event object.
   */
  public function getLastEventByMachinename($machine_name) {
    $event_id = db_query_range('SELECT event_id FROM {past_event} WHERE machine_name = :machine_name ORDER BY event_id DESC', 0, 1, array(':machine_name' => $machine_name))->fetchField();
    if ($event_id) {
      return entity_load('past_event', $event_id);
    }
  }

  /**
   * Returns a standard past_form event.
   *
   * @param string $machine_name
   *   The machine name of the event.
   * @param string $form_id
   *   The machine name of the form.
   * @param string $button_value
   *   The string shown on the submit button.
   * @param string $message
   *   (optional) A description of the event.
   *
   * @return PastEvent
   *   The event created.
   */
  public function getEventToBe($machine_name, $form_id, $button_value, $message = 'Form submitted:') {
    $event = past_event_create('past_form', $machine_name, $message . ' ' . $form_id . ', ' . $button_value);
    $event->setSeverity(PAST_SEVERITY_DEBUG);
    return $event;
  }

  /**
   * Compares two past_form events.
   *
   * @param PastEventInterface $first
   *   The first comparison object.
   * @param PastEventInterface $second
   *   The second comparison object.
   * @param string $form_id
   *   The machine name of the form.
   * @param string $button_value
   *   The string shown on the submit button.
   * @param array $values
   *   (optional) Values to check on 'values' argument.
   * @param array $errors
   *   (optional) Errors to check on 'errors' argument.
   * @param string|null $message
   *   (optional) A description of the event.
   */
  public function assertSameEvent(PastEventInterface $first, PastEventInterface $second, $form_id, $button_value, array $values = array(), array $errors = array(), $message = NULL) {
    if (!empty($first)) {
      $mismatch = array();

      if ($first->getModule() != $second->getModule()) {
        $mismatch[] = 'module';
      }
      if ($first->getMachineName() != $second->getMachineName()) {
        $mismatch[] = 'machine_name';
      }
      if ($first->getMessage() != $second->getMessage()) {
        $mismatch[] = 'message';
      }
      if ($first->getSeverity() != $second->getSeverity()) {
        $mismatch[] = 'severity';
      }
      if ($first->getArgument('form_id')->getData() != $form_id) {
        $mismatch[] = 'form_id';
      }
      if ($first->getArgument('operation')->getData() != $button_value) {
        $mismatch[] = 'operation';
      }

      if (!empty($values)) {
        $arg_values = $first->getArgument('values')->getData();
        foreach ($values as $k => $v) {
          if ($arg_values[$k] != $v) {
            $mismatch[] = 'value: ' . $v;
          }
        }
      }

      if (!empty($errors)) {
        $arg_errors = $first->getArgument('errors')->getData();
        foreach ($errors as $k => $v) {
          if ($arg_errors[$k] != $v) {
            $mismatch[] = 'error value: ' . $v;
          }
        }
      }

      if (!empty($mismatch)) {
        $this->fail('The following properties do not match: ' . implode(', ', $mismatch));
      }

      $this->assertTrue(empty($mismatch), ($message ? $message : t('Event @first is equal to @second.', array('@first' => $first->getMessage(), '@second' => $second->getMessage()))));
    }
    else {
      $this->pass('Event was not checked for equality.');
    }
  }
}
