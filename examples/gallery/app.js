addEventListener('DOMContentLoaded', loadLatest);

function loadLatest() {
	api('latest').then(data => {
		$('#gallery').innerHTML = data.map(image => `<figure>
			<img src="/api/image/${image.id}.${image.extension}" alt="${image.title}">
			<figcaption>${image.title}</figcaption>
		</figure>`).join('');
		$('form').addEventListener('submit', e => {
			e.preventDefault();
			upload();
		});
	});
}

function upload() {
	api('upload', 'form').then(data => {
		if (data.error) {
			$('#message').textContent = translate(data.error, data.args);
		} else {
			loadLatest();
			$('form').reset();
			$('#message').textContent = '';
		}
	});
}

const $ = s => document.querySelector(s);

async function api(endpoint, data = {}) {
	const dataSelector = typeof data === 'string'
	return await (await fetch(`/api/${endpoint}`, {
		method: 'POST',
		body: dataSelector ? new FormData($(data)) : JSON.stringify(data),
		headers: dataSelector ? undefined : {'Content-Type': 'application/json'}
	})).json();
}


function translate(key, args) {
	const translations = {
		'notSet': 'The {0} is required.',
		'tooShort': 'The {0} must be at least {1} characters long.',
		'tooLong': 'The {0} must be at most {1} characters long.',
		'invalidExtension': 'The {0} must be one of {1}.',
	};
	return translations[key].replace(/\{(\d+)\}/g, (match, index) => args[index]);
}