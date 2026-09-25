<?php

require_once 'vendor/autoload.php';

use League\CommonMark\GithubFlavoredMarkdownConverter;

if (isset($_REQUEST['debug']))
	var_dump($_REQUEST);


$file_requested = '';
if (!empty($_GET['file'])) {
	$file_requested = htmlentities($_GET['file']);
	$file_full = 'notes/'.$_GET['file'].'.md';
}


//Replace the generated headers with clickable targets
function replaceHeaders($page_content, $type, &$replace_from, &$replace_to) {
	preg_match_all('/<'.$type.'>(.*?)<\/'.$type.'>/is', $page_content, $matches);
	foreach($matches[0] as $pos => $match) {
		$key = str_replace(' ', '-', strtolower($matches[1][$pos]));
		$replace_from[] = $match;
		$replace_to[] = str_replace('<'.$type.'>', '<'.$type.' id="'.$key.'">', $match);
	}
}


//Get the list of files
$files_raw = scandir('notes/');
$files = [];
foreach($files_raw as $file_data) {
	//TODO: Handle subdirectories
	if ($file_data == '.' || $file_data == '..' || substr($file_data, -3) != '.md')
		continue;
	$files[] = substr($file_data, 0, -3);
}


//Get the current file
$page_title = 'Index';
if (isset($file_full) && is_file($file_full)) {
	$converter = new GithubFlavoredMarkdownConverter([
		'html_input' => false,
		'allow_unsafe_links' => 'true',
		'max_nesting_level' => 15,
		'use_underscore' => false,
	]);

	$markdown_data = file_get_contents($file_full);

	//Replace ==highlights== with the <mark>highlights</mark> syntax
	$markdown_data = preg_replace('/==(.*?)==/', '<mark>$1</mark>', $markdown_data);
	$page_content = $converter->convertToHtml($markdown_data);

	//Parse page links
	$page_content = preg_replace('/\[\[(.*?).md\]\]/is', '<a href="$1">$1</a>', $page_content);

	//Make headers clickable
	$replace_from = [];
	$replace_to = [];
	replaceHeaders($page_content, 'h1', $replace_from, $replace_to);
	replaceHeaders($page_content, 'h2', $replace_from, $replace_to);
	replaceHeaders($page_content, 'h3', $replace_from, $replace_to);
	replaceHeaders($page_content, 'h4', $replace_from, $replace_to);
	replaceHeaders($page_content, 'h5', $replace_from, $replace_to);
	replaceHeaders($page_content, 'h6', $replace_from, $replace_to);
	$page_content = str_replace($replace_from, $replace_to, $page_content);

	$page_title = ucfirst(str_replace('-', ' ', $file_requested));

	$page_last_update = gmdate('Y-m-d H:i:s', filemtime($file_full));
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title><?=$page_title?> - Notes viewer</title>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<meta name="application-name" content="Markdown Viewer">
	<meta name="msapplication-TileColor" content="#8e82dd">
	<link rel="icon" href="favicon.ico">
	<link rel="icon" href="icon.svg" type="image/svg+xml">
	<link rel="apple-touch-icon" href="apple-touch-icon.png">
	<link rel="manifest" href="manifest.webmanifest">

	<meta name="theme-color" content="#8e82dd">

	<style>
		@import "//www.interordi.com/files/css/normalize.css";
		@import "//www.interordi.com/files/css/base-dark.css";

		body {
			line-height: 1.5;
		}

		.structure {
			display: flex;
		}

		.files {
			margin-right: 12px;
			min-width: 180px;
		}

		.files li {
			padding: 2px;
		}

		.selected {
			background-color: rgba(255, 255, 255, 0.25);
		}

		h2 {
			border-top: 1px solid #BBB;
			padding-top: 10px;
		}

		ul {
			margin-bottom: 6px;
		}

		@media all and (max-width: 700px) {
			h4 {
				margin: 0.75rem 0;
			}

			.structure {
				display: block;
			}

			.files {
				width: 100%;
				border-bottom: 1px dotted #AAA;
				overflow-y: auto;
				max-height: 150px
			}
		}

		.footer {
			display: inline-block;
			margin-top: 20px;
			float: right;

			padding: 5px;
			opacity: 0.9;
			font-size: 80%;
			border: 1px dotted #555;
		}
	</style>
</head>

<body>

	<div class="structure">
		<div class="files">
			<h4>Pages</h4>
			<ul>
				<?php
				foreach($files as $file_name) {
					$selected = ($file_name == $file_requested) ? 'selected' : '';
					echo '	<li class="'.$selected.'"><a href="'.$file_name.'">'.$file_name.'</a></li>';
				}
				?>
			</ul>
		</div>

		<div class="main">
			<?php
			if (isset($page_content)) {
				echo '<h1>'.$page_title.'</h1>';
				echo $page_content;

				echo '<div class="footer">
					Last updated on '.$page_last_update.' UTC
				</div>';
			}
			else
				echo '<p>Select a page.</p>';
			?>
		</div>
	</div>
</body>
</html>
