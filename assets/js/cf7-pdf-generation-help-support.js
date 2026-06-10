(function () {
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.cf7-pdf-generation-help-faq-question').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var item = btn.closest('.cf7-pdf-generation-help-faq-item');
				if (!item) {
					return;
				}
				item.classList.toggle('is-open');
				var open = item.classList.contains('is-open');
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
				var answer = document.getElementById(btn.getAttribute('aria-controls'));
				if (answer) {
					answer.setAttribute('aria-hidden', open ? 'false' : 'true');
				}
				var sym = btn.querySelector('span[aria-hidden="true"]');
				if (sym) {
					sym.textContent = open ? '\u2212' : '+';
				}
			});
		});
	});
})();
