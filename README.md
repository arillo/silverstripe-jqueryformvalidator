# JQueryValidation

Provides silverstripe forms with jquery.validation functionality.
Visit http://jqueryvalidation.org/ for more information.

## Usage
Start up validation on a form like this:

	$form = new Form(
		$this,
		'Form',
		$fields,
		new FieldList(
			new FormAction(
				'FormHandler',
				'Submit'
			)
		),
		RequiredFields::create(....)
	);

### JQueryValidation::create($form, $config)
Expects a form and an optional config array, which overrides values from the base config. Take a look into
JQueryValidation::$base_config for available key / value pairs.

	JQueryValidation::create(
		$form,
		array(
			'validator' => array(
				'errorElement' => 'div'
			)
			...
			..
			.
		)
	);

### JQueryValidation->generate()
For automated creation use:

	// This will inspect all form fields and add their validation methods.
	// It also will add required checks provided by the form's RequiredFields.
	JQueryValidation::create($form)->generate();

	// It is also possible to provide custom error messages and behaviour through passing a config array like this:
	JQueryValidation::create($form)->generate(array(
		'messages' => array(
			'CheckboxField_DATA' => array(
				'required' => 'Custom message'
			)
		)
	));
	// Expected hooks are messages, rules, groups.

### JQueryValidation->custom()
If you want to provide your own validation file you can use this:

	JQueryValidation::create($form)->custom('path/to/your.js');

	// It is also possible to provide information about which additional js files should be loaded, like this:
	JQueryValidation::create($form)->custom(
		'path/to/your.js',
		array(
			'additionalMethods' => true,	// load additional-methods.min.js
			'metaData' => true,				// load jquery.metadata.js
			'moment' => true,				// load moment.min.js
			'date' => true					// load date.js
		)
	);