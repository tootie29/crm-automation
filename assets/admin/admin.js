/**
 * RichardMedina CRM Automation — admin scripts.
 * - Toggle the static-vs-field input visibility on mapping rows when "Mode" changes.
 */
(function () {
	'use strict';

	function syncMappingRow(modeSelect) {
		const tr = modeSelect.closest('tr');
		if (!tr) {
			return;
		}
		if (modeSelect.value === 'static') {
			tr.classList.add('is-static');
		} else {
			tr.classList.remove('is-static');
		}
	}

	function bindMapping() {
		document.querySelectorAll('.rm-ca-mapping select[name$="[mode]"]').forEach(function (select) {
			syncMappingRow(select);
			select.addEventListener('change', function () {
				syncMappingRow(select);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindMapping);
	} else {
		bindMapping();
	}
})();
