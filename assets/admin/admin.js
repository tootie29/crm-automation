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

	function bindCustomFieldRepeater() {
		const addBtn = document.querySelector('.rm-ca-cf-add');
		const tpl    = document.getElementById('rm-ca-cf-template');
		const tbody  = document.querySelector('.rm-ca-cf-rows');

		if (addBtn && tpl && tbody) {
			addBtn.addEventListener('click', function () {
				const next = parseInt(addBtn.getAttribute('data-next-index') || '0', 10);
				const html = tpl.innerHTML.replace(/__INDEX__/g, String(next));
				const wrap = document.createElement('tbody');
				wrap.innerHTML = html.trim();
				const row = wrap.firstElementChild;
				tbody.appendChild(row);
				addBtn.setAttribute('data-next-index', String(next + 1));

				// Wire mode toggle for the new row.
				const modeSelect = row.querySelector('select[name$="[mode]"]');
				if (modeSelect) {
					syncMappingRow(modeSelect);
					modeSelect.addEventListener('change', function () { syncMappingRow(modeSelect); });
				}
			});
		}

		// Delegate click handler for remove buttons (covers existing + future rows).
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.rm-ca-cf-remove');
			if (!btn) return;
			const row = btn.closest('tr');
			if (row) row.remove();
		});
	}

	function init() {
		bindMapping();
		bindCustomFieldRepeater();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
