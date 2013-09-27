;(function($){
	$(document).ready(function(){
		var $form = $("$FormID"),
			validation = JSON.parse("$Validation");
		// time validation
		$.validator.addMethod("time", function(value, element) {
			var $el = $(element),
				format = $el.metadata().timeformat,
				name = $el.attr('name'),
				fieldRule = this.settings.rules[name];
			if (!fieldRule) return true;
			if (fieldRule.required
				|| (fieldRule.required == false && value)
			) {
				return window.moment(value, format).isValid();
			}
			return true;
		}, "time error");
		// override date validation
		$.validator.methods["date"] = function(value, element) {
			var $el = $(element),
				format = $el.data('jquerydateformat'),
				name = $el.attr('name'),
				fieldRule = this.settings.rules[name];
			if (!fieldRule) return true;
			if (fieldRule.required
				|| (fieldRule.required == false && value)
			) {
				return window.moment(value, format).isValid();
			}
			return true;
		};
		validation.errorPlacement = function(error, element) {
			var type = element.attr("type");
			if (element.parents('.datetime').size() > 0) {
				error.insertAfter(element.parents('.datetime')[0]);
			} else {
				if (!type) {
					if (element.hasClass('dropdown')) type = 'dropdown';
				}
				if (type) {
						error.insertAfter(element.parent("div"));
				}
				/*
				type = type || 'none';

				switch(type.toLowerCase()) {
					case 'none':
						break;
					default:
						error.insertAfter(element.parent("div"));
						//error.insertAfter(element.parent("p"));
						break;
				}
				*/
			}
		};
		//console.log(JSON.parse("$Validation"));
		$form.validate(validation);
		//var obj = JSON.parse("$Validation");
		//console.log(obj.Rules.Rule);
	});
})(jQuery);