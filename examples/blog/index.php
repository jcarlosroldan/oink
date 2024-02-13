<?php

include_once '../../oink.php';

Oink\serve('endpoints.php', base_path: '/examples/blog/api', allow_get: true);

?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='0.9em' font-size='90'%3E🐽%3C/text%3E%3C/svg%3E">
<title>Blog</title>
<style>
*{box-sizing:border-box}
body{font:16px system-ui,sans-serif;margin:2rem;background:#fee}
h1,h2,h3{color:#734;margin:0}
h3{margin:2rem 0 1rem}
#posts{max-width:40rem}
article{margin:0 -1rem;padding:1rem;border-radius:1rem;cursor:pointer}
article:hover{background:#0001}
#comments{position:absolute;display:none;flex-direction:column;top:0;right:0;height:100vh;width:20rem;padding:2rem 1rem;background:#0002}
#comments h2{display:flex;justify-content:space-between}
#comments h2 a{text-decoration:none;color:#000a}
#comments.open{display:flex}
form{display:flex;flex-direction:column;gap:.5rem}
form .error{color:#8b1b24}
input,textarea,button{font:inherit;padding:.5rem}
</style>
</head>
<body>
<h1>🐽 Blog</h1>
<p>Welcome to my blog. Click on a post to see its comments.</p>
<div id="posts"></div>
<div id="comments">
	<h2>
		Comments
		<a href="javascript:$('#comments').classList.remove('open')">&times;</a>
	</h2>
	<section></section>
	<form>
		<h3>Post a comment</h3>
		<input type="hidden" name="post_id">
		<input type="mail" name="author" placeholder="your@mail.com">
		<textarea name="text" placeholder="Your comment"></textarea>
		<button>Add comment</button>
		<div class="error"></div>
	</form>
</div>
<script>
addEventListener('DOMContentLoaded', loadPosts);

function loadPosts() {
	api('post/list').then(data => {
		$('#posts').innerHTML = data.posts.map(post => `
			<article onclick="$('#comments').classList.add('open'); loadComments(${post.id})">
				<h2>${post.title}</h2>
				<div>Created <datetime>${post.created}</datetime></div>
				<p>${post.body}</p>
			</article>
		`).join('');
		$('#comments form').addEventListener('submit', e => {
			e.preventDefault();
			postComment(data.posts[0].id);
		});
	});
}

function loadComments(postId) {
	api('comment/list', {post_id: postId}).then(data => {
		if (data.comments.length === 0) {
			$('#comments section').innerHTML = '<p>No comments yet.</p>';
		} else {
			$('#comments section').innerHTML = data.comments.map(comment => `
				<div>
					<h3>${comment.author}</h3>
					<p>${comment.text}</p>
					<datetime>${comment.created}</datetime>
				</div>
			`).join('');
		}
		$('input[name="post_id"]').value = postId;
	});
}

function postComment() {
	api('comment/create', '#comments form').then(data => {
		if (data.error) {
			$('#comments form .error').textContent = translate(data.error, data.args || []);
		} else {
			loadComments(form.querySelector('input[name="post_id"]').value);
		}
	});
}

const $ = s => document.querySelector(s);

async function api(endpoint, data = {}) {
	if (typeof data === 'string') {
		data = Object.fromEntries(new FormData($(data)));
	}
	return await (await fetch(`/examples/blog/api/${endpoint}`, {
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify(data)
	})).json();
}

function translate(key, args) {
	const translations = {
		'invalidEmail': 'Field {0} has an invalid email address.',
		'tooShort': 'Field {0} is too short. It must be at least {1} characters long.',
		'tooLong': 'Field {0} is too long. It must be at most {1} characters long.'
	};
	return translations[key].replace(/\{(\d+)\}/g, (match, index) => args[index]);
}
</script>
</body>
</html>