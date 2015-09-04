<?php
global $db;
include 'db.php';
include 'functions.php';

if( isset( $_GET['action'] ) ) {
	switch( $_GET['action'] ) {
		case 'scrape-index':
			fetch_index();
		break;

		case 'scrape-national':
			fetch_national_index();
		break;

		case 'scrape-global':
			fetch_global_index();
		break;

		case 'scrape-article':
			fetch_article();
		break;
	}

	die();
}
?>
<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">

  <title>Sc-Sc-Scrapin' Feminism</title>
  <script src='http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js'></script>
  <style>
	.done {
		color: blue;
	}
  </style>
</head>

<body>
	<h1>Scraping!</h1>
	<p>Articles found: <span id="articles-found">--</span></p>
	<p>Index pages scraped: <span id="index-progress">--</span></p>
	<p>National articles: <span id="national-articles">--</span></p>
	<p>Global articles: <span id="global-articles">--</span></p>
	<p>Article progress: <span id="article-progress">--</span></p>

	<p id="errors"></p>
<script>
jQuery(document).ready(function($) {
	var offset = 0;
	var cutoff = 150;
	var archiveUrl = 'http://feminist.org/news/newsbyte/uswire.asp?offset=';
	var articleIDs = [];
	var totalArticles = 0;
	var nationalIDs = [];
	var globalIDs = [];

	function fetchIndexes() {
		var maxOffset = 14251;
		$.ajax({
			type: 'GET',
			url: 'index.php',
			data: {
				action: 'scrape-index',
				offset: offset
			}
		}).success( function(data) {
			articleIDs = articleIDs.concat( data );
			$('#articles-found').text( articleIDs.length );
			$('#index-progress').text( offset / 25 );
			offset += 25;
			if( offset <= maxOffset ) {
				fetchIndexes();
			} else {
				offset = 0;
				totalArticles = articleIDs.length;
				fetchNationalIndexes();
			}
		}).fail( function( data ) {
			$('#error').append('<p>Error: ' + data + '</p>');
		});
	}

		fetchIndexes();

		function fetchNationalIndexes() {
			var maxOffset = 12936;
			$.ajax({
				type: 'GET',
				url: 'index.php',
				data: {
					action: 'scrape-national',
					offset: offset
				}
			}).success( function(data) {
				nationalIDs = nationalIDs.concat( data );
				$('#national-articles').text( nationalIDs.length );
				offset += 25;
				if( offset <= maxOffset ) {
					fetchNationalIndexes();
				} else {
					offset = 0;
					fetchGlobalIndexes();
				}
			}).fail( function( data ) {
				$('#error').append('<p>Error: ' + data + '</p>');
			});
		}

		function fetchGlobalIndexes() {
			var maxOffset = 2666;
			$.ajax({
				type: 'GET',
				url: 'index.php',
				data: {
					action: 'scrape-global',
					offset: offset
				}
			}).success( function(data) {
				globalIDs = globalIDs.concat( data );
				$('#global-articles').text( globalIDs.length );
				offset += 25;
				if( offset <= maxOffset ) {
					fetchGlobalIndexes();
				} else {
					fetchArticle();
				}
			}).fail( function( data ) {
				$('#error').append('<p>Error: ' + data + '</p>');
			});
		}

		function fetchArticle() {
			var id = parseInt( articleIDs.pop() );
			var nationalArticle = 0;
			if( nationalIDs.indexOf(id) > 0 ) {
				nationalArticle = 1;
			}
			var globalArticle = 0;
			if( globalIDs.indexOf(id) > 0 ) {
				globalArticle = 1;
			}

			$.ajax({
				type: 'GET',
				url: 'index.php',
				data: {
					action: 'scrape-article',
					offset: id,
					global: globalArticle,
					national: nationalArticle
				}
			}).success( function(data) {
				$('#article-progress').text( articleIDs.length + ' to go!' );
				if( articleIDs.length > 0 ) {
					fetchArticle();
				} else {
					$('body').append( '<h1 class="done">Done!</h1>' );
				}
			}).fail( function( data ) {
				$('#error').append('<p>Error: ' + data + '</p>');
			});
		}
});
</script>
</body>
</html>
