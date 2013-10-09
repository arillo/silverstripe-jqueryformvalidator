<?php
/**
 * Adds jquery.validation functionality to silverstripe 3.0+ forms.
 * Visit http://jqueryvalidation.org/ for more information.
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
			'errorMessage' => 'Please check the input of this field.', // default/ fallback error message
			'pwMinLength' => 5 // password min length
		),
		'validator' => array(
			'errorClass' => 'error', // css class for errors
			'validClass' => 'valid', // css class for valid fields
			'errorElement' => 'label', // html wrapper element for errors
			'ignore' => ':hidden', // selector or fields that should be ignored
			'required' => 'required', // css class for required fields
		)
	);

	/**
	 * Recursive extension of $array1 with $array2.
	 * 
	 * @param  array  $array1
	 * @param  array  $array2
	 * @return array
	 */
	public static function array_extend(array $array1, array $array2) {
		$merged = $array1;
		foreach ($array2 as $key => $value) {
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
				$merged[$key] = self::array_extend($merged[$key], $value);
			} else {
				$merged[$key] = $value;
			}
		}
		return $merged;
	}

	/**
	 * Factory method.
	 * 
	 * @param  Form $form
	 * @param  array $config
	 * @return JQueryValidation
	 */
	public static function create(Form $form, array $config = array()) {
		return new JQueryValidation($form, $config);
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
	 * Constructor, sets up the config for this instance. If $config with the same signature is provided it will
	 * extend / override values in {@see $base_config}.
	 * 
	 * @param Form $form
	 * @param array $config
	 */
	public function __construct(Form $form, array $config = array()) {
		// validate input
		if (!$form instanceof Form) throw new InvalidArgumentException('$form must be a Form instance');
		$this->form = $form;

		// translatable default error message
		self::$base_config['defaults']['errorMessage'] = _t('JQueryValidation.DEFAULT_ERROR', 'Please check the input of this field.');
		// create instance config
		$this->config = self::array_extend(self::$base_config, $config);
	}

	/**
	 * Loads your custom js validation file.
	 * Loading of optional jquery.validations files can be forced by $config, like this:
	 * $config = array(
	 *	'additionalMethods' => true,	// load additional-methods.min.js
	 *	'metaData' => true,				// load jquery.metadata.js
	 *	'moment' => true,				// load moment.min.js
	 *	'date' => true					// load date.js
	 * )
	 * 
	 * 
	 * @param  string $jsFile path to custom js file
	 * @param  array  $config
	 * @return JQueryValidation
	 */
	public function custom(string $jsFile, array $config = array()) {
		if (!is_string($jsFile)) {
			throw new InvalidArgumentException("$jsFile must be a string!");
		}
		if (!is_array($config)) {
			throw new InvalidArgumentException("$config must be an array!");
		}
		Requirements::javascript(self::$module .'/javascript/libs/jquery.validate.min.js');

		$modules = array(
			'metadata' => self::$module .'/javascript/libs/jquery.metadata.js',
			'additionalMethods' => self::$module .'/javascript/libs/additional-methods.min.js',
			'moment' => self::$module .'/javascript/libs/moment.min.js',
			'date' => self::$module .'/javascript/libs/date.js'
		);

		foreach ($config as $key) {
			if (isset($modules[$key])) {
				Requirements::javascript($modules[$key]);
			}
		}
		Requirements::javascript($jsFile);
		return $this;
	}

	/**
	 * Provides a form with jquery validation.
	 * Generates jquery.validation by required fields attached to $form.
	 * Behaviour/output can be overwritten by $custom, like this:
	 * $validation->generate(array(
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
	 *		'SomeGroup' => "field1 field2";
	 *	)
	 *));
	 * CAUTION: this can be tricky and can lead to unexpected behaviour, if done wrong.
	 * 
	 * @param  Form $form
	 * @param  array  $config
	 * @return JQueryValidation
	 */
	public function generate(array $custom = array()) {
		// validate input
		if (!is_array($custom)) throw new InvalidArgumentException("$custom must be an array!");

		// extend config
		$this->config = self::array_extend($this->config, $custom);

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
								'required' => 'ss-uploadfield'
							);
							$messages[$field] = array(
								'ss-uploadfield' => sprintf(
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

			$validation['rules'] = $rules;
			$validation['messages'] = $messages;
			$validation['groups'] = $groups;

			// extend $validation with $custom
			$validation = self::array_extend($validation, $custom);

			$jsVars = array(
				'FormID' => "#{$this->form->FormName()}",
				'Validation' => json_encode($validation),
				'Defaults'=> json_encode($this->config['validator']),
				'DefaultErrorMessage' => $this->config['defaults']['errorMessage']
			);

			Requirements::javascript(self::$module .'/javascript/libs/jquery.validate.min.js');

			// load extra js files
			if ($requireExtraJs) $this->addExtraFiles();

			// inject main js
			$this->createMainJS($jsVars);

			return $this;
		}
	}

	/**
	 * Inject additional JS files
	 * 
	 * @return JQueryValidation
	 */
	protected function addExtraFiles() {
		$extraFiles = array(
			self::$module .'/javascript/libs/jquery.metadata.js',
			self::$module .'/javascript/libs/date.js',
		);
		Requirements::combine_files('jquery.validation.extras.js', $extraFiles);
		return $this;
	}

	/**
	 * Inject main validation script
	 * 
	 * @param  array $jsVars
	 * @return JQueryValidation
	 */
	protected function createMainJS(array $jsVars) {
		Requirements::javascriptTemplate(
			self::$module .'/javascript/jquery.form.validation.js',
			$jsVars,
			'JQueryValidation.VALIDATOR'
		);
		return $this;
	}
}