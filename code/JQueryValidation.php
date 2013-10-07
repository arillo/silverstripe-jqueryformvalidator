<?php
/**
 * Provides forms with jquery.validate functionality.
 *
 * @package jquery-validator
 * @author bumbus@arillo <sf@arillo.net>
 */
class JQueryValidation {

	/**
	 * Foldername of this module
	 * @var string
	 */
	public static $module = 'jquery-validator';

	/**
	 * Default settings
	 * @var array
	 */
	public static $base_config = array(
		'defaults' => array(
			'errorClass' => 'required', // css class for errors
			'validClass' => 'valid', // css class for valid fields
			'errorElement' => 'label', // html wrapper element for errors
			'errorMessage' => 'Please check the input of this field.', // default/ fallback error message
			'ignore' => ':hidden', // selector or fields that should be ingnored
			'required' => 'required', // css class for required fields
			'fileMissing' => 'fileMissing',
			'pwMinLength' => 5 // password min length
		)
	);

	/**
	 * Factory
	 * @param  Form $form
	 * @return JQueryValidation
	 */
	public static function create($form) {
		return new JQueryValidation($form);
	}

	/**
	 * The Form we want to validate
	 * @var Form
	 */
	protected $form;

	/**
	 * Instance configuration
	 * @var array
	 */
	protected $config = array();

	/**
	 * __construct description
	 * @param Form $form
	 */
	public function __construct($form) {
		if (!$form instanceof Form) throw new InvalidArgumentException('$form must be a Form instance');
		$this->form = $form;
		self::$base_config['defaults']['errorMessage'] = _t('JQueryValidation.DEFAULT_ERROR', 'Please check the input of this field.');
		// create the instance config
		$this->config = array_merge($this->config, self::$base_config);
	}

	/**
	 * Loads your custom js validation file.
	 * Loading of optional jquery.validations files can be forced by $config, like this:
	 * $config = array(
	 *	'additionalMethods' => true,	// load additional-methods.min.js
	 *	'metaData' => true,				// load jquery.metadata.js
	 *	'moment' => true,				// load moment.min.js
	 *	'date' => true					// load moment.min.js
	 * )
	 * 
	 * 
	 * @param  string $jsFile path to custom js file
	 * @param  array  $config
	 */
	public function custom($jsFile, $config = array()) {
		$form = $this->form;
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
		if (isset($config['date']) && $config['date']) {
			Requirements::javascript(self::$module .'/javascript/libs/date.js');
		}
		Requirements::javascript($jsFile);
	}

	/**
	 * Provides a form with jquery validation.
	 * Generates jquery.validation by required fields attached to $form.
	 * Behaviour/output can be overwritten by $config, like this:
	 * array(
	 *	'messages' => array(
	 *		'MyCheckBoxField' => array(
	 *			'required' => 'Some custom message here.'
	 *		)
	 *	),
	 *	'rules' => array(
	 *		'MyCheckBoxField' => array(
	 *			'required' => true'
	 *		)
	 *	),
	 *	'groups' => array(
	 *		'SomeGroup' = "field1 field2";
	 *	)
	 *);
	 * CAUTION: this can be tricky and can lead to unexpected behaviour, if done wrong.
	 * 
	 * @param  Form $form
	 * @param  array  $config
	 */
	public function generate($config = array()) {
		// validate input
		if (!is_array($config)) throw new InvalidArgumentException("$config must be an array!");

		// merge default settings
		if (isset($config['defaults']) && is_array($config['defaults'])) {
			$this->config['defaults'] = array_merge($this->config['defaults'], $config['defaults']);
		}

		$rules = array();
		$messages = array();
		$groups = array();
		$validation = array();
		$requireExtraJs = false;
		$requiredFields = $this->form->getValidator()->getRequired();
		$formFields = $this->form->Fields();

		// walk over all fields
		if ($formFields) {
			$requiredFields = array_values($requiredFields);
			foreach ($formFields as $formField) {
				$required = array_search($formField->Name, $requiredFields);
				$required = (is_numeric($required) && ($required >= 0)) ? true : false;
				switch ($formField->class) {
					case 'ConfirmedPasswordField':
						$field1 = $formField->Name . '[_Password]';
						$field2 = $formField->Name . '[_ConfirmPassword]';
						$rules[$field1] = array(
							'required' => $required,
							'minlength' => $this->config['defaults']['pwMinLength']
						);
						$rules[$field2] = array(
							'required' => $required,
							'equalTo' => "input[name='" . $field1 . "']"
						);
						$messages[$field1] = array(
							'minlength' => sprintf(
								_t('JQueryValidation.PASSWORD_TOO_SHORT', 'Password should be at least %s characters long.'),
								$this->config['defaults']['pwMinLength']
							)
						);
						$messages[$field2] = array(
							'equalTo' => _t('JQueryValidation.CONFIRM_PWD_ERROR', 'Passwords must be equal!')
						);
						if ($required) {
							$messages[$field1]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->children[0]->Title()
							);
							$messages[$field2]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->children[0]->Title()
							);
						}
						$groups[$formField->Name] = "{$field1} {$field2}";
						break;

					case 'DateField':
						$requireExtraJs = true;
						$rules[$formField->Name] = array(
							'required' => $required,
							'date' => true
						);
						$messages[$formField->Name] = array(
							'date' => sprintf(
								_t('JQueryValidation.INVALID_DATE', 'Please use this format (%s)'),
								$formField->getConfig('dateformat')
							)
						);
						if ($required) {
							$messages[$formField->Name]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->Title()
							);
						}
						break;

					case 'DatetimeField':
						$requireExtraJs = true;
						$field1 = $formField->Name . '[date]';
						$field2 = $formField->Name . '[time]';
						$rules[$field1] = array(
							'required' => $required,
							'date' => true
						);
						$rules[$field2] = array(
							'required' => $required,
							'time' => true
						);
						$messages[$field1] = array(
							'date' => sprintf(
								_t('JQueryValidation.INVALID_DATE', 'Please use this format (%s)'),
								$formField->getDateField()->getConfig('dateformat')
							)
						);
						$messages[$field2] = array(
							'time' => sprintf(
								_t('JQueryValidation.INVALID_DATE', 'Please use this format (%s)'),
								$formField->getTimeField()->getConfig('timeformat')
							)
						);
						if ($required) {
							$messages[$field1]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->Title()
							);
							$messages[$field2]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->Title()
							);
						}
						$groups[$formField->Name] = "{$field1} {$field2}";
						break;

					case 'TimeField':
						$requireExtraJs = true;
						$rules[$formField->Name] = array(
							'required' => $required,
							'time' => true
						);
						$messages[$formField->Name] = array(
							'time' => sprintf(
								_t('JQueryValidation.INVALID_DATE', 'Please use this format (%s)'),
								$formField->getConfig('timeformat')
							)
						);
						if ($required) {
							$messages[$formField->Name]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->Title()
							);
						}
						break;

					case 'EmailField':
						$rules[$formField->Name] = array(
							'required' => $required,
							'email' => true,
						);
						$messages[$formField->Name] = array(
							'email' => _t('JQueryValidation.INVALID_EMAIL', 'This email address seems to be invalid.')
						);
						if ($required) {
							$messages[$formField->Name]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->Title()
							);
						}
						break;

					case 'PasswordField':
						$rules[$formField->Name] = array(
							'required' => $required,
							'minlength' => self::$base_config['defaults']['pwMinLength']
						);
						$messages[$formField->Name] = array(
							'minlength' => sprintf(
								_t('JQueryValidation.PASSWORD_TOO_SHORT', 'Password should be at least %s characters long.'),
								$this->config['defaults']['pwMinLength']
							)
						);
						if ($required) {
							$messages[$formField->Name]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->Title()
							);
						}
						break;
					case 'UploadField':
						if ($required) {
							$field = $formField->Name . '[Uploads][]';
							$rules[$field] = array(
								'required' => true
							);
							$messages[$field] = array(
								'required' => sprintf(
									_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
									$formField->Title()
								)
							);
						}
						break;
					default:
						$rules[$formField->Name] = array(
							'required' => $required
						);
						if ($required) {
							$messages[$formField->Name]['required'] = sprintf(
								_t('JQueryValidation.REQUIRED_MESSAGE', 'This field is required: %s'),
								$formField->Title()
							);
						}
						break;
				}
			}

			if (count($rules)) $validation['rules'] = $rules;
			if (count($messages)) $validation['messages'] = $messages;
			if (count($groups)) $validation['groups'] = $groups;

			if (isset($config['rules']) && is_array($config['rules'])) {
				$validation['rules'] = array_merge($validation['rules'], $config['rules']);
			}
			if (isset($config['messages']) && is_array($config['messages'])) {
				$validation['messages'] = array_merge($validation['messages'], $config['messages']);
			}
			if (isset($config['groups']) && is_array($config['groups'])) {
				$validation['groups'] = array_merge($validation['groups'], $config['groups']);
			}

			$jsVars = array(
				'FormID' => "#{$this->form->FormName()}",
				'Validation' => json_encode($validation),
				'DefaultErrorMessage' => $this->config['defaults']['errorMessage']
			);

			Requirements::javascript(self::$module .'/javascript/libs/jquery.validate.min.js');

			// load extra js files
			if ($requireExtraJs) {
				$this->addExtraFiles();
			}
			$this->createMainJS($jsVars);
		}
	}

	/**
	 * Inject additional JS files
	 */
	protected function addExtraFiles() {
		$extraFiles = array(
			self::$module .'/javascript/libs/jquery.metadata.js',
			self::$module .'/javascript/libs/moment.min.js',
			self::$module .'/javascript/libs/date.js',
		);
		Requirements::combine_files('jquery.validation.extras.js', $extraFiles);
	}

	/**
	 * Inject main validation script
	 * @param  array $jsVars
	 */
	protected function createMainJS($jsVars) {
		Requirements::javascriptTemplate(
			self::$module .'/javascript/jquery.form.validation.js',
			$jsVars,
			'JQueryValidation.VALIDATOR'
		);
	}
}