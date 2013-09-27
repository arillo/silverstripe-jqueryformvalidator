<?php
/**
 * Provides forms with jquery.validate functionality.
 *
 * @package jquery-validator
 * @author bumbus@arillo
 */
class JQueryValidation {

	public static $module = 'jquery-validator';
	/**
	 * Default settings
	 * @var array
	 */
	private static $config = array(
		'defaults' => array(
			'errorClass' => 'required',
			'validClass' => 'valid',
			'errorElement' => 'label',
			'ignore' => ':hidden',
			'required' => 'required',
			'fileMissing' => 'fileMissing'
		)
	);

	/**
	 * Loads your custom js validation file.
	 * Loading of optional jquery.validations files can be forced by $config, like this:
	 * $config = array(
	 *	'additionalMethods' => true,	// load additional-methods.min.js
	 *	'metaData' => true,				// load jquery.metadata.js
	 *	'moment' => true 				// load moment.min.js
	 * )
	 * 
	 * 
	 * @param  string $jsFile relative path to custom js file
	 * @param  array  $config
	 */
	public static function custom($form, $jsFile, $config = array()) {
		if (!is_string($jsFile)) {
			throw new InvalidArgumentException("$jsFile must be a string!");
		}
		if (!is_array($config)) {
			throw new InvalidArgumentException("$config must be an array!");
		}
		Requirements::javascript(self::$module .'/javascript/libs/jquery.validate.min.js');
		if (isset($config['metaData']) && $config['metaData']) {
			Requirements::javascript(self::$module .'/javascript/libs/jquery.metadata.js');
		}
		if (isset($config['additionalMethods']) && $config['additionalMethods']) {
			Requirements::javascript(self::$module .'/javascript/libs/additional-methods.min.js');
		}
		if (isset($config['moment']) && $config['moment']) {
			Requirements::javascript(self::$module .'/javascript/libs/moment.min.js');
		}
		Requirements::javascript($jsFile);
	}

	/**
	 * Provides a form with jquery validation scripts.
	 * Generates jquery.validation by required fields attached to the $form.
	 * 
	 * @param  Form $form
	 * @param  array  $config
	 */
	public static function generate($form, $config = array()) {
		// validate input
		if (!$form instanceof Form) throw new InvalidArgumentException('$form must be a Form instance');
		if (!is_array($config)) throw new InvalidArgumentException("$config must be an array!");

		// merge default settings
		if (isset($config['defaults']) && is_array($config['defaults'])) {
			self::$config['defaults'] = array_merge(self::$config['defaults'], $config['defaults']);
		}
		$rules = array();
		$messages = array();
		$groups = array();
		$validation = array();
		$requireSpecials = false;
		$required = $form->getValidator()->getRequired();
		if (count($required)) {
			$required = array_values($required);
			foreach ($required as $field) {
				if ($formField = $form->Fields()->fieldByName($field)) {
					switch ($formField->class) {
						case 'ConfirmedPasswordField':
							break;
						case 'DateField':
							$requireSpecials = true;
							$rules[$formField->Name] = array(
								'required' => true,
								'date' => true
							);
							$messages[$formField->Name] = array(
								'required' => sprintf(
									_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
									$formField->Title()
								),
								'date' => sprintf(
									_t('JQueryValidation.INVALID_DATE', 'Please use this format (%s)'),
									$formField->getConfig('dateformat')
								)
							);
							break;
						case 'DatetimeField':
							$requireSpecials = true;
							$name1 = $formField->Name . '[date]';
							$name2 = $formField->Name . '[time]';
							$rules[$name1] = array(
								'required' => true,
								'date' => true
							);
							$rules[$name2] = array(
								'required' => true,
								'time' => true
							);
							$messages[$name1] = array(
								'required' => sprintf(
									_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
									$formField->Title()
								)
							);
							$messages[$name2] = array(
								'required' => sprintf(
									_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
									$formField->Title()
								)
							);
							$groups[$formField->Name] = "{$name1} {$name2}";
							break;
						case 'TimeField':
							$requireSpecials = true;
							$rules[$formField->Name] = array(
								'required' => true,
								'time' => true
							);
							$messages[$formField->Name] = array(
								'required' => sprintf(
									_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
									$formField->Title()
								),
								'time' => sprintf(
									_t('JQueryValidation.INVALID_DATE', 'Please use this format (%s)'),
									$formField->getConfig('timeformat')
								)
							);
							break;
						case 'EmailField':
							$rules[$formField->Name] = array(
								'required' => true,
								'email' => true
							);
							$messages[$formField->Name] = array(
								'required' => sprintf(
									_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
									$formField->Title()
								),
								'email' => _t('JQueryValidation.INVALID_EMAIL', 'This email address seems to be invalid.')
							);
							break;
						case 'PasswordField':
							break;
						case 'UploadField':
							break;
						default:
							$rules[$formField->Name] = array(
								'required' => true
							);
							$messages[$formField->Name] = array(
								'required' => sprintf(
									_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
									$formField->Title()
								)
							);
							break;
					}
				}
			}

			if (count($rules)) $validation['rules'] = $rules;
			if (count($messages)) $validation['messages'] = $messages;
			if (count($groups)) $validation['groups'] = $groups;

			$jsVars = array(
				'FormID' => "#{$form->FormName()}",
				'Validation' => json_encode($validation)
			);

			// render js file
			Requirements::javascript(self::$module .'/javascript/libs/jquery.validate.min.js');
			if ($requireSpecials) {
				Requirements::javascript(self::$module .'/javascript/libs/jquery.metadata.js');
				Requirements::javascript(self::$module .'/javascript/libs/moment.min.js');
			}
			Requirements::javascriptTemplate(
				self::$module .'/javascript/jquery.form.validation.js',
				$jsVars,
				'JQueryValidation.VALIDATOR'
			);
		}
	}
}