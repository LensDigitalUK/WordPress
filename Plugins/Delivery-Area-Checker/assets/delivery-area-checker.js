(function () {
	'use strict';

	function initChecker(root) {
		if (root.dataset.dacReady === '1') return;
		root.dataset.dacReady = '1';

		var form = root.querySelector('.dac-checker__form');
		var input = root.querySelector('.dac-checker__input');
		var button = root.querySelector('.dac-checker__button');
		var result = root.querySelector('.dac-checker__result');

		if (!form || !input || !button || !result || typeof DACChecker === 'undefined') return;

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			var postcode = input.value.trim();
			if (!postcode) {
				showResult(DACChecker.invalidPostcode, 'error');
				input.focus();
				return;
			}

			button.disabled = true;
			var originalText = button.textContent;
			button.textContent = DACChecker.checking;
			showResult('', '');

			var body = new URLSearchParams();
			body.append('action', 'dac_check_postcode');
			body.append('nonce', DACChecker.nonce);
			body.append('postcode', postcode);
			body.append('zone_id', root.getAttribute('data-zone') || '0');

			fetch(DACChecker.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			})
				.then(function (response) {
					return response.json().then(function (data) {
						if (!response.ok || !data.success) {
							throw new Error(data && data.data && data.data.message ? data.data.message : DACChecker.genericError);
						}
						return data.data;
					});
				})
				.then(function (data) {
					var message = data.eligible ? root.getAttribute('data-success') : root.getAttribute('data-failure');
					showResult(message || data.message, data.eligible ? 'success' : 'error');
				})
				.catch(function (error) {
					showResult(error.message || DACChecker.genericError, 'error');
				})
				.finally(function () {
					button.disabled = false;
					button.textContent = originalText;
				});
		});

		function showResult(message, state) {
			result.textContent = message;
			result.className = 'dac-checker__result' + (state ? ' is-' + state : '');
		}
	}

	function boot() {
		document.querySelectorAll('.dac-checker').forEach(initChecker);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
