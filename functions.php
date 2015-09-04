<?php
date_default_timezone_set('America/New_York');
include 'simple-html-dom.php';

function fetch_index() {
	global $db;

	$html = fetch_index_html( 'http://feminist.org/news/newsbyte/uswire.asp?offset=' );
	$db_data = array();
	foreach( $html->find('#news div h3') as $node) {
		$h3 = str_get_html( $node->outertext );

		$text = $h3->plaintext;
		$date = get_the_date( $text );
		$href = $h3->find('a', 0)->href;
		$title = $h3->find('a', 0)->plaintext;
		$id = get_the_id_from_url( $href );

		$db_data[] = array(
			'id' => $id,
			'title' => $title,
			'date' => $date,
		);
	}

	if( $inserted = $db->insert( 'news', $db_data ) ) {
		array_map( 'intval', $inserted );
		send_json_success( $inserted );
		return;
	}

	send_json_fail( $db->error() );
}

function fetch_national_index() {
	global $db;

	$national_ids = array();
	$html = fetch_index_html( 'http://feminist.org/news/newsbyte/uswire.asp?national=1&offset=' );
	foreach( $html->find('#news div h3 a') as $a ) {
		$href = $a->href;
		$id = get_the_id_from_url( $href );
		$national_ids[] = $id;
	}

	send_json_success( $national_ids );
}

function fetch_global_index() {
	global $db;

	$global_ids = array();
	$html = fetch_index_html( 'http://feminist.org/news/newsbyte/uswire.asp?global=1&offset=' );
	foreach( $html->find('#news div h3 a') as $a ) {
		$href = $a->href;
		$id = get_the_id_from_url( $href );
		$global_ids[] = $id;
	}

	send_json_success( $global_ids );
}

function fetch_article() {
	global $db;

	$id = intval( $_GET['offset'] );
	$html = fetch_index_html( 'http://feminist.org/news/newsbyte/uswirestory.asp?id=' );
	$content = $html->find('#news h2', 1)->next_sibling()->outertext;
	$resources = $html->find('#news h2', 1)->next_sibling()->next_sibling()->plaintext;
	$resources = str_replace( 'Media Resources: ', '', $resources );
	$resources = trim( $resources );

	$db_data = array(
		'content' => $content,
		'resources' => $resources,
		'national' => 0,
		'global' => 0,
	);
	if( isset( $_GET['national'] ) && $_GET['national'] == 1 ) {
		$db_data['national'] = 1;
	}
	if( isset( $_GET['global'] ) && $_GET['global'] == 1 ) {
		$db_data['global'] = 1;
	}

	if( $updated = $db->update( 'news', $db_data, array( 'id' => $id ) ) ) {
		send_json_success( $updated );
		return;
	}

	$bad_query = $db->last_query();
	$new_query = str_replace( 'UPDATE "news" SET "content"', 'UPDATE `news` SET `content`', $bad_query );
	$new_query = str_replace( ', "resources" =', ', `resources` =', $new_query );
	$new_query = str_replace( 'WHERE "id" =', 'WHERE `id` =', $new_query );

	if( $updated = $db->query( $new_query ) ) {
		send_json_success( $updated );
		return;
	}

	send_json_fail( $db->error() );

}

function fetch_index_html( $url ) {
	$offset = intval( $_GET['offset'] );
	$url = $url . $offset;

	return file_get_html( $url );
}

function get_the_date( $str ) {
	$pieces = explode(' - ', $str);
	$date_string = trim( $pieces[0] );
	$the_date = date( 'Y-m-d H:i:s', strtotime( $date_string ) );

	return $the_date;
}

function get_the_id_from_url( $url ) {
	$pieces = explode( 'id=', $url );
	$pieces2 = explode( '&', $pieces[1] );

	return intval( $pieces2[0] );
}

function send_json_success( $data ) {
	http_response_code( 200 );
	header( 'Content-Type: application/json;' );
	echo json_encode( $data );
}

function send_json_fail( $data ) {
	http_response_code( 500 );
	header( 'Content-Type: application/json;' );
	echo json_encode( $data );
}

// See http://stackoverflow.com/questions/3258634/php-how-to-send-http-response-code
// For 4.3.0 <= PHP <= 5.4.0
if( !function_exists('http_response_code') ) {
    function http_response_code( $newcode = NULL ) {
        static $code = 200;
        if( $newcode !== NULL ) {
            header('X-PHP-Response-Code: '.$newcode, true, $newcode);
            if( !headers_sent() ) {
                $code = $newcode;
			}
        }
        return $code;
    }
}
