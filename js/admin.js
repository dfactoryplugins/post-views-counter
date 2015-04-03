jQuery(document).ready(function($) {

	// default chosen
	$('.pvc-chosen').chosen({
		disable_search_threshold: 8,
		display_selected_options: false,
		search_contains: true,
		width: '300px'
	});


	// time types and position
	$('.pvc-chosen-short').chosen({
		disable_search_threshold: 8,
		width: '200px'
	});


	// ask whether reset options to defaults
	$(document).on('click', '.reset_pvc_settings', function() {
		return confirm(pvcArgsSettings.resetToDefaults);
	});


	// removes ip box
	$(document).on('click', '.remove-exclude-ip', function() {
		var parent = $(this).parent(),
			nextParent = parent.parent(),
			addButton = parent.find('.add-exclude-ip').hide(),
			addCurrentIPButton = parent.find('.add-current-ip').hide();

		// removes ip box
		parent.remove();

		var children = nextParent.find('div');

		// was there add button?
		if(addButton.length === 1) {
			children.last().append(addButton.show(), ' ', addCurrentIPButton.show());
			// children.last().append();
		}

		// only one ip box left?
		if(children.length === 1) {
			children.find('.remove-exclude-ip').hide();
		}
	});


	// adds ip box
	$(document).on('click', '.add-exclude-ip', function() {
		var parent = $(this).parent(),
			newBox = parent.clone().hide();

		// clears value
		newBox.find('input').first().val('');

		// removes add buttons
		$(this).remove();
		parent.find('.add-current-ip').remove();

		// adds and displays new ip box
		parent.after(newBox.show());

		parent.parent().find('.remove-exclude-ip').show();
	});


	// adds current ip
	$(document).on('click', '.add-current-ip', function() {
		// fills input with user's current ip
		$(this).parent().find('input').first().val($(this).attr('data-rel'));
	});


	// displays user roles if needed
	$(document).on('change', '.pvc-chosen-groups', function() {
		var foundRoles = false;

		// checks whether roles are selected
		$(this).find(':selected').each(function(i, item) {
			if(item.value === 'roles') {
				foundRoles = true;
			}
		});

		// are roles selected?
		if(foundRoles) {
			$(this).parent().find('.pvc_user_roles').fadeIn(300);
		} else {
			$(this).parent().find('.pvc_user_roles').fadeOut(300);
		}
	});
});