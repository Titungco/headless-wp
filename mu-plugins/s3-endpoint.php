<?php
/**
 * Plugin Name: S3 Custom Endpoint
 * Description: Points humanmade/s3-uploads at a custom S3-compatible endpoint (MinIO in local dev). No-op in production, where S3_UPLOADS_ENDPOINT is unset and the plugin talks to real AWS S3.
 */

if ( ! getenv( 'S3_UPLOADS_ENDPOINT' ) ) {
	return;
}

add_filter( 's3_uploads_s3_client_params', function ( $params ) {
	$params['endpoint'] = getenv( 'S3_UPLOADS_ENDPOINT' );
	$params['use_path_style_endpoint'] = true;
	// MinIO doesn't support the AWS SDK's newer checksum requirements.
	$params['request_checksum_calculation'] = 'when_required';
	$params['response_checksum_validation'] = 'when_required';
	return $params;
} );
