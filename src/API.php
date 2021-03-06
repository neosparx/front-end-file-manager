<?php
namespace FrontendFileManager\Src\File;

final class Api {

	public function __construct() {
			
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/file/(?P<id>\d+)', array(
		    'methods' => 'GET',
		    'callback' => array( $this, 'single' ),
		  ));
		});

		// Delete multiple
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/trash-multiple', array(
		    'methods' => 'DELETE',
		    'callback' => array( $this, 'trash_multiple' ),
		  ));
		});

		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/delete/(?P<id>\d+)', array(
		    'methods' => 'DELETE',
		    'callback' => array( $this, 'delete' ),
		  ));
		});
		
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/update', array(
		    'methods' => 'POST',
		    'callback' => array( $this, 'update' ),
		  ));
		});
			
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/list/page/(?P<page>\d+)', array(
		    'methods' => 'GET',
		    'callback' => array( $this, 'list_files' ),
		  ));
		});


		add_action( 'rest_api_init', function () {
		  register_rest_route( 'frontend-filemanager/v1', '/upload', array(
		    'methods' => 'POST',
		    'callback' => array( $this, 'upload' ),
		  ));
		});
	}

	public function trash_multiple( $http_request ) {
		
		global $wpdb;

		$params = $http_request->get_params();
		
		$file_ids = array();

		$files = $params['files'];

		// Sanitize the ids.
		
		if ( empty( $files ) ) {
			return new \WP_REST_Response(array(
					'message' => esc_html__('No files were selected'),
					'type' => 'error'
				));
		}

		require_once FEFM_DIR . '/src/Helpers.php';

		$query_string = "DELETE FROM " . Helpers::get_table_name() . " WHERE id IN({$files})";

		$is_deleted = $wpdb->query( $query_string );

		if ( ! $is_deleted ) {
			return new \WP_REST_Response(array(
					'message' => esc_html__('There was an error deleting files.', 'front-end-file-manager'),
					'type' => 'error'
				));
		}

		$file_ids = implode(',', $file_ids);

		if ( ! empty( $file_ids ) ) {
			foreach ($file_ids as $file_id) {
				$file_available = Helpers::get_user_file_path($file_id);
				wp_delete_file($file_available);
				do_action('fefm_file_deleted', $file_available);
			}
		}

		do_action('fefm_files_deleted');

		$response = array(
			'type' => 'success',
			'message' => esc_html__('Selected files were successfully deleted.', 'front-end-file-manager')
		);


		return new \WP_REST_Response($response);

	}

	public function update( $http_request ) {

		global $wpdb;

		$params = $http_request->get_params();

		require_once FEFM_DIR . '/src/Helpers.php';

		$updated = $wpdb->update( 
			Helpers::get_table_name(), 
			array( 
				'file_label' => $params['file_label'],
				'file_description' => $params['file_description'],
				'file_sharing_type' => $params['file_sharing_type'],
				'date_updated' => current_time('mysql', 1)	
			), 
			array( 'id' => $params['id'] ), 
			array( 
				'%s',	// Label
				'%s',	// Description
				'%s'	// Sharing type
			), 
			array( '%d' ) 
		);

		$response = array('test'=>false);

		return new \WP_REST_Response($response);
	}

	public function single( $http_request ) {
		
		global $wpdb;

		$response = array();
		$status = 400;

		$params = $http_request->get_params();
		$file_id = $params['id'];

		require_once FEFM_DIR . '/src/Helpers.php';

		$stmt = $wpdb->prepare( "SELECT * FROM " . Helpers::get_table_name() . " WHERE id = %d", $file_id );

		$result = $wpdb->get_row( $stmt, ARRAY_A );
 
		if ( ! empty( $result ) ):

			$status = 200;

			$response = array('file' => array_map( 'esc_html', $result ) );

		endif;
		

		return new \WP_REST_Response($response, $status);
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

	public function list_files( $http_request ) {

		global $wpdb;

		$params = $http_request->get_params();

		$user_id = get_current_user_id();
		$user_id = 1;
		$search_keywords = '';

		$sort_by = 'date_updated';
		$sort_dir = 'DESC';

		if ( ! empty( $params['sort_by'] ) ) {
			$sort_by = $params['sort_by'];
		}

		if ( ! empty ( $params['sort_dir'] ) ) {
			$sort_dir = $params['sort_dir'];
		}

		if ( ! empty( $params['search_keywords'] )  ) {
			$search_keywords = $wpdb->esc_like( $params['search_keywords'] );
		}

		// Get the total number of rows
		// @Todo. Store the number of rows somewhere in table;
		$count = $wpdb->get_row( $wpdb->prepare("SELECT count(id) as total FROM {$wpdb->prefix}frontend_file_manager 
			WHERE file_owner_id = %d AND file_label LIKE %s " , 
			$user_id, '%'.$search_keywords.'%' ));

		$total = 0;

		if ( ! empty( $count ) ) {
			$total = absint( $count->total );
		}
		
		$page = absint( $params['page'] );
		$limit = 10;

		$num_pages = $total / $limit;
		
		$num_pages = ceil( $num_pages );

		$offset = ( $page - 1 ) * $limit; 

		if ( $page === 1 ) {
			$offset = 0;
		}

		$stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}frontend_file_manager 
			WHERE file_owner_id = %d ORDER BY ".esc_sql($sort_by)." ".esc_sql($sort_dir)." LIMIT %d, %d" , 
			$user_id, $offset, $limit );

		if ( ! empty ( $search_keywords ) ) {
			$stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}frontend_file_manager 
				WHERE file_owner_id = %d AND file_label LIKE %s ORDER BY ".esc_sql($sort_by)." ".esc_sql($sort_dir)." LIMIT %d, %d" , 
				$user_id, '%'.$search_keywords.'%', $offset, $limit );
		}
		
		$results = $wpdb->get_results( $stmt, OBJECT );
		$files = array();
		if ( ! empty ( $results ) ) {
			foreach( $results as $result ) {
				$result->date_updated = sprintf( _x( '%s ago', '%s = human-readable time difference', 'front-end-file-manager' ), 
					human_time_diff( strtotime( $result->date_updated  ), current_time( 'timestamp' ) ) );
				$files[] = $result;
			}
		}
		$response = array(
				'message' => 'error_unauthorized',
				'files' => array(),
				'page' => absint( $page ),
				'total' => absint( $total ),
				'limit' => absint( $limit ),
				'num_pages' => absint( $num_pages ),
			);

		if ( empty ( $results ) ) {
			$response['message'] = 'error_unauthorized';
		} else {
			$response['message'] = 'success';
			$response['files'] = array_map( array( $this, 'sanitize_string'), $files);
		}

		return new \WP_REST_Response($response);
	}

	public function sanitize_string($files) {
		$node = array();
		if ( ! empty ( $files ) ) {
			foreach ( $files as $key => $val ) {
				$node[$key] = esc_html( $val );
			}
		}
		return $node;
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