<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Radio_Button_Display_Strategy_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Radio_Button_Display_Strategy_Test extends EE_UnitTestCase{

	/**
	 * @group 7121
	 */
	function test_display__normalizing_as_int(){
		$form = new EE_Form_Section_Proper( array(
				'name' => 'test',
				'subsections' => array(
					'use_captcha' => new EE_Radio_Button_Input(
						array(
							1 => __( 'Yes', 'event_espresso' ),
							0  => __( 'No', 'event_espresso' )
						),
						array(
							'html_label_text'       => __( 'Use reCAPTCHA', 'event_espresso' ),
							'default'               => 1,
							'normalization_strategy' => new EE_Int_Normalization()
						)
					)
				)
				));
		$this->assertEquals( 1, $form->get_input( 'use_captcha' )->normalized_value() );
		$this->assertEquals( '1', $form->get_input( 'use_captcha' )->raw_value() );
		$this->assertEquals( '<div id="test-use-captcha-lbl" class="" style="">Use reCAPTCHA</div><label for="test-use-captcha-1"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-1"name="test[use_captcha]"class=""style=""type="radio"value="1" checked="checked">Yes</label><label for="test-use-captcha-0"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-0"name="test[use_captcha]"class=""style=""type="radio"value="0">No</label>', $form->get_input( 'use_captcha' )->get_html_for_input() );

		$form->populate_defaults( array( 'use_captcha' => 0 ) );
		//the first option should be selected now
		$this->assertEquals( 0, $form->get_input ( 'use_captcha' )->normalized_value() );
		$this->assertEquals( '0', $form->get_input( 'use_captcha' )->raw_value() );
		$this->assertEquals( '<div id="test-use-captcha-lbl" class="" style="">Use reCAPTCHA</div><label for="test-use-captcha-1"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-1"name="test[use_captcha]"class=""style=""type="radio"value="1">Yes</label><label for="test-use-captcha-0"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-0"name="test[use_captcha]"class=""style=""type="radio"value="0" checked="checked">No</label>', $form->get_input( 'use_captcha' )->get_html_for_input() );
	}

	/**
	 * @group 7121
	 */
	function test_display__normalizing_as_string(){
		$form = new EE_Form_Section_Proper( array(
				'name' => 'test',
				'subsections' => array(
					'use_captcha' => new EE_Radio_Button_Input(
						array(
							"yes ma'am" => __( 'Yes', 'event_espresso' ),
							"no ma'am"  => __( 'No', 'event_espresso' )
						),
						array(
							'html_label_text'       => __( 'Use reCAPTCHA', 'event_espresso' ),
							'default'               => "yes ma'am",
						)
					)
				)
				));
		$this->assertEquals( "yes ma'am", $form->get_input( 'use_captcha' )->normalized_value() );
		$this->assertEquals( "yes ma'am", $form->get_input( 'use_captcha' )->raw_value() );
		$this->assertEquals( '<div id="test-use-captcha-lbl" class="" style="">Use reCAPTCHA</div><label for="test-use-captcha-yesmaam"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-yesmaam"name="test[use_captcha]"class=""style=""type="radio"value="yes ma&#039;am" checked="checked">Yes</label><label for="test-use-captcha-nomaam"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-nomaam"name="test[use_captcha]"class=""style=""type="radio"value="no ma&#039;am">No</label>', $form->get_input( 'use_captcha' )->get_html_for_input() );

		$form->populate_defaults( array( 'use_captcha' => "no ma'am" ) );
		//the first option should be selected now
		$this->assertEquals( "no ma'am", $form->get_input ( 'use_captcha' )->normalized_value() );
		$this->assertEquals( "no ma'am", $form->get_input( 'use_captcha' )->raw_value() );
		$this->assertEquals( '<div id="test-use-captcha-lbl" class="" style="">Use reCAPTCHA</div><label for="test-use-captcha-yesmaam"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-yesmaam"name="test[use_captcha]"class=""style=""type="radio"value="yes ma&#039;am">Yes</label><label for="test-use-captcha-nomaam"class="ee-radio-label-after big-lbl"><input id="test-use-captcha-nomaam"name="test[use_captcha]"class=""style=""type="radio"value="no ma&#039;am" checked="checked">No</label>', $form->get_input( 'use_captcha' )->get_html_for_input() );
	}

}

// End of file EE_Radio_Button_Display_Strategy_Test.php