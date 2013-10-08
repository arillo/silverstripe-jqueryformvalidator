;(function($){
	$(document).ready(function(){
		var $form = $("$FormID"),
			validation = JSON.parse("$Validation"),
			defaults = JSON.parse("$Defaults");

		// merge validation objects
		$.extend(validation, defaults);

		// default error message
		$.validator.messages.required = "$DefaultErrorMessage";

		// time validation
		$.validator.addMethod("time", function(value, element) {
			var $el = $(element),
				format = $el.metadata().timeformat,
				name = $el.attr('name'),
				fieldRule = this.settings.rules[name];

			if (!fieldRule)
				return true;

			if (fieldRule.required
				|| (fieldRule.required == false && value)
			) {
				return Date.isValid(value, format);
			}
			return false;
		}, $.validator.messages.required);

		// textarea validation
		$.validator.addMethod("textarea", function(value, element) {
			return (value !== undefined);
		}, $.validator.messages.required);

		// uploadfield validation
		$.validator.addMethod("ss-uploadfield", function(value, element) {
			var $el = $(element),
				$wrap = $el.parents("div.middleColumn"),
				uploads = $wrap.children("ul.files").find("li").size(),
				name = $el.attr('name'),
				fieldRule = this.settings.rules[name];

			if (!fieldRule || uploads > 0)
				return true;

			return false;
		}, $.validator.messages.required);

		// override date validation
		$.validator.methods["date"] = function(value, element) {
			var $el = $(element),
				//format = $el.data('jquerydateformat'),
				format = $el.data('isodateformat').toLowerCase(),
				name = $el.attr('name'),
				fieldRule = this.settings.rules[name];
			if (!fieldRule)
				return true;

			if (fieldRule.required
				|| (fieldRule.required == false && value)
			) {
				return Date.isValid(value, format);
			}
			return false;
		};

		validation.errorPlacement = function(error, element) {
			var type = element.attr("type");
			if (element.parents('.datetime').size() > 0) {
				error.insertAfter(element.parents('.datetime')[0]);
			} else if (element.parents('.confirmedpassword').size() > 0) {
				error.insertAfter(element.parents('.confirmedpassword')[0]);
			} else if (element.parents('.upload').size() > 0) {
				error.insertAfter(element.parents('.upload')[0]);
			} else {
				error.insertAfter(element.parent("div"));
			}
		};

		// fire up validator
		$form.validate(validation);
	});
})(jQuery);