jQuery(document).ready(function($) {
	var vars = window.mergeTagsL10n;
	var $from = $('#from-tags');

	$from.suggest('admin-ajax.php?action=ajax-tag-search&tax=' + vars.taxonomy, {multiple: true});
	$('#to-tag').suggest('admin-ajax.php?action=ajax-tag-search&tax=' + vars.taxonomy);

	$('.actions select').each(function(i) {
		var $self = $(this);

		var extra = i ? '2' : '';
		var $field = $('<span> ' + vars.to_tag + ': <input name="bulk_to_tag' + extra + '" type="text" size="20"></span>').hide();

		$self.after($field);

		$self.find('option:first').after('<option value="bulk-merge-tag">' + vars.action + '</option>');

		$self.change(function() {
			if ( $self.val() == 'bulk-merge-tag' )
				$field.show().find('input').focus();
			else
				$field.hide();
		});
	});
});
