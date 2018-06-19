<?php
namespace FrontendFileManager\Src\File;

final class Api {

	public function __construct() {
		
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/delete/(?P<id>\d+)', array(
		    'methods' => 'DELETE',
		    'callback' => array( $this, 'delete' ),
		  ));
		});

		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/list', array(
		    'methods' => 'GET',
		    'callback' => array( $this, 'list' ),
		  ));
		});


		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/upload', array(
		    'methods' => 'POST',
		    'callback' => array( $this, 'upload' ),
		  ));
		});
	}

	public function delete( $http_request ) {
		
		global $wpdb;
		
		$params = $http_request->get_params();
		$file_id = $params['id'];
		$response = array( 'message' => 'not okay.' );
		$status = 400;
		
		require_once FEFM_DIR . '/src/Helpers.php';

		// First, fetch the file.
		$file_available = Helpers::get_user_file_path($file_id);

		if ( $file_available ) {

			$deleted = $wpdb->delete( Helpers::get_table_name(), array( 'id' => $file_id ), array( '%d' ) );
			
			if ( false !== $deleted  ) {
				$status = 200;
				$response['message'] = 'delete okay.';
				// delete the actual file.
				wp_delete_file( $file_available );
			} 
		} else {
			$response['message'] = 'user not authenticated.';
		}

		return new \WP_REST_Response($response, $status);
	}

	public function upload() {
		
		$data = array();

		add_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'change_file_name_on_upload' ) );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$uploadedfile = $_FILES['file'];

		$upload_overrides = array( 'test_form' => false );

		$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
		    
		    $file = new File();
		  	
		  	// Set the ownder id.
		    $file->setFileOwnerId( get_current_user_id() );
		    // Set the file name.
		    $file->setFileName( wp_basename( $movefile['file'] ) );
		    // Set the file label.
		    $file->setFileLabel( $_FILES['file']['name'] );
		    // Set the file type.
		    $file->setFileType( $movefile['type'] );
		    // Set the file description.
		    $file->setFileDescription('');
		    // Set the sharing type.
		    $file->setFileSharingType('private');
		    // Set date updated.
		    $file->setDateUpdated( current_time('mysql', 1) );
		    // Set date created.
		    $file->setDateCreated( current_time('mysql', 1) );

		    $file_crud = new FileCrud( $file );

		    $inserted = $file_crud->save($file);

		    if ( $inserted ) {

		    	$file = $file_crud->fetch( $inserted ); // The var $inserted contains the last inserted id.
		    	
		    	if ( ! empty ( $file ) ) {
		    		$file->date_updated = sprintf( _x( '%s ago', '%s = human-readable time difference', 'front-end-file-manager' ), 
					human_time_diff( strtotime( $file->date_updated  ), current_time( 'timestamp' ) ) );
		    		$data['file'] = $file;
		    	} else {
		    		$data['file'] = null;
		    	}

		    }

		} else {
		    /**
		     * Error generated by _wp_handle_upload()
		     * @see _wp_handle_upload() in wp-admin/includes/file.php
		     */
		    echo $movefile['error'];
		}
		
		return new \WP_REST_Response($data);

	}

	public function list() {
		global $wpdb;
		
		$stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}frontend_file_manager WHERE file_owner_id = %d ORDER BY id ASC" , get_current_user_id());
		
		$results = $wpdb->get_results( $stmt, OBJECT );
		$files = array();
		if ( ! empty ( $results ) ) {
			foreach( $results as $result ) {
				$result->date_updated = sprintf( _x( '%s ago', '%s = human-readable time difference', 'front-end-file-manager' ), 
					human_time_diff( strtotime( $result->date_updated  ), current_time( 'timestamp' ) ) );
				$files[] = $result;
			}
		}
		$response = array();

		if ( empty ( $results ) ) {
			$response = array(
					'message' => 'error_unauthorized'
				);
		} else {
			$response = array(
				'message' => 'success',
				'files' => $files
			);
		}

		return new \WP_REST_Response($response);
	}

	public function set_upload_dir( $dirs ) {

		$user_id = get_current_user_id();

		$upload_dir = apply_filters( 'frontend-filemanager-upload-dir',
		sprintf( 'frontend-filemanager/%d/', $user_id ) );

	    $dirs['subdir'] = $upload_dir;
	    $dirs['path'] = trailingslashit( $dirs['basedir'] ) . $upload_dir;
	    $dirs['url'] = trailingslashit( $dirs['baseurl'] ) . $upload_dir;

	    return $dirs;

	}

	public function change_file_name_on_upload( $file ){

		$ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$file['name'] = sha1_file($file['tmp_name']).'.'.$ext;

		return $file;
	}

	public function __destruct() {
		
		remove_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );

		remove_filter( 'wp_handle_upload_prefilter', array( $this, 'change_file_name_on_upload' ) );

	}

}

new Api;