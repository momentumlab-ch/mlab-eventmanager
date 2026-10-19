/* Event details as tabs: one group at a time instead of a long list. */
(() => {
	document.querySelectorAll('[data-mlab-em-tabs]').forEach((box) => {
		const groups = [...box.querySelectorAll(':scope > .mlab-em-group')];
		if (groups.length < 2) {
			return;
		}
		const key = 'mlab-em-tab';
		const nav = document.createElement('div');
		nav.className = 'mlab-em-tabs';
		nav.setAttribute('role', 'tablist');
		const buttons = groups.map((group, i) => {
			const legend = group.querySelector('legend');
			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'mlab-em-tab';
			button.setAttribute('role', 'tab');
			button.textContent = legend ? legend.textContent : String(i + 1);
			group.setAttribute('role', 'tabpanel');
			nav.append(button);
			return button;
		});
		const show = (index) => {
			groups.forEach((group, i) => {
				group.hidden = i !== index;
				buttons[i].setAttribute('aria-selected', i === index ? 'true' : 'false');
				buttons[i].tabIndex = i === index ? 0 : -1;
			});
			try {
				sessionStorage.setItem(key, String(index));
			} catch (e) {
				// Storage can be blocked; the first tab is fine then.
			}
		};
		buttons.forEach((button, i) => {
			button.addEventListener('click', () => show(i));
			button.addEventListener('keydown', (e) => {
				const step = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
				if (!step) {
					return;
				}
				const next = (i + step + buttons.length) % buttons.length;
				show(next);
				buttons[next].focus();
			});
		});
		// A required field that is empty opens its tab, so the browser can point at it.
		box.addEventListener('invalid', (e) => {
			const index = groups.findIndex((group) => group.contains(e.target));
			if (index >= 0) {
				show(index);
			}
		}, true);
		box.prepend(nav);
		let start = 0;
		try {
			start = Math.min(parseInt(sessionStorage.getItem(key) || '0', 10) || 0, groups.length - 1);
		} catch (e) {
			start = 0;
		}
		show(start);
	});
})();
