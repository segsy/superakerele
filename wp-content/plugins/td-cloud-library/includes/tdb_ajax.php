<?php
/**
 * register rest endpoints
 */
add_action( 'rest_api_init', function(){
    $namespace = 'td-cloud-library';

    /**
     * new_template endpoint
     */
    register_rest_route($namespace, '/new_template/', array(
        'methods'  => 'POST',
        'callback' => function($request){

			$duplicate_request = $request->get_param( 'duplicateTemplate' );
			$duplicate_template = !empty( $duplicate_request ) ? $duplicate_request : false;

            if ( $duplicate_template ) {
                $reply['duplicate_template'] = 'We have a duplicate template request!';

                // check for post id
                $post_id = $request->get_param( 'postId' );
                if ( empty($post_id) ) {
                    $reply['error'] = 'The template id ( post id ) is missing and it\'s required!';
                    die( json_encode( $reply ) );
                }
            }

            // permission check
            if ( !current_user_can( 'edit_pages' ) ) {
                $reply['error'] = 'no permission';
                die( json_encode( $reply ) );
            }

            // no empty title templates :) - not required but it's nice to have a title
            $template_title = wp_strip_all_tags($request->get_param( 'templateName' ));
            if ( empty($template_title) ) {
                $reply['error'] = 'Please enter a title for your template.';
                die( json_encode( $reply ) );
            }

            // check the template type
            $template_type = $request->get_param('templateType');
            $template_types = array(
                'single', 'category', 'author', 'search', 'date', 'tag', 'attachment', '404', 'page', 'header', 'footer', 'woo_product', 'cpt', 'cpt_tax',
            );
	        $template_types = apply_filters( 'tdb_template_types', $template_types );

	        if ( in_array( $template_type, $template_types) === false ) {
                $reply['error'] = 'Invalid template type!';
                die( json_encode( $reply ) );
            }

            // check the template content
	        $template_content = '';
            if (!empty($request->get_param( 'templateContent' ))) {
                $template_content = $request->get_param( 'templateContent' );

                $images = $request->get_param( 'images' );
                if (!empty($images)) {
                    $new_images = json_decode($images, true);

                    if ('header' === $template_type) {
                        $template_content = json_decode(base64_decode($template_content), true);
                        foreach (['tdc_header_desktop', 'tdc_header_desktop_sticky', 'tdc_header_mobile', 'tdc_header_mobile_sticky'] as $item ) {
                            $template_content[$item] = tdb_util::parse_template_shortcodes( $template_content[$item], [ 'new_images' => $new_images ] );
                        }
                        $template_content = base64_encode(json_encode($template_content));
                    } else {
	                    $template_content = tdb_util::parse_template_shortcodes( $template_content, [ 'new_images' => $new_images ] );
                    }
                }
            }

            $post_type = 'page' === $template_type ? 'page' : 'tdb_templates';

            global $wpdb;

            // search for titles with same name( template title )
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "
                        SELECT post_title 
                        FROM {$wpdb->posts} 
                        WHERE post_title LIKE '%s' 
                        AND post_type = '%s' 
                        AND post_status = '%s'",
                    array( '%' . $wpdb->esc_like( $template_title ) . '%', $post_type, 'publish' )
                )
            );

            // here we store the titles we need
            $titles = array();

            // the query might return titles like 'Single Post Template 10' as 'Single Post Template 1' so we need to make sure these don't count
            foreach ( $results as $post ) {
                $title = $post->post_title;

                //$reply['initial_found_posts'][] = array(
                //    'title' => $title,
                //    'strpos_val' => strpos( $title, $template_title . ' ' ) !== false,
                //    'temp_title_vs_post_title' => $template_title !== $title,
                //);

                if ( strpos( $title, $template_title . ' ' ) !== false or $template_title === $title ) {
                    $titles[] = $title;
                }
            }

            /*
             * the sql query doesn't return expected results ordered by titles, it orders like this
             * .. Single Post Template 1 (10) ..after.. Single Post Template 1 .. Single Post Template 1 (20) ..after.. Single Post Template 1 (2) and so on..
             * this dose not work for us so we need to sort the titles array using the "natural order" algorithm
            */
            natsort($titles);

            $titles = array_values( $titles );

            foreach ( $titles as $title ) {
                //$reply['posts_after_natsort'][] = $title;
            }

            // count found posts
            $titles_count = count($titles);
            //$reply['posts_count'] = $titles_count;

            // if we have more than one post with the same title we need to alter the template title
            if ( $titles_count >= 1 ) {

                // flag to check whether we set a index template title in the foreach loop
                $flag = false;

                foreach ( $titles as $index => $title ) {

                    // check if the first post is the original template like 'Single Post Template 1'
                    if ( $index == 0 ) {

//                        $reply['original_template'] = array(
//                            '$template_title' => $template_title,
//                            '$title' => $title,
//                        );

                        // if the first post is not the original template
                        if ( $template_title !== $title ) {
                            //$reply['case'] = 'the first post is not the original template';

                            // just set the flag and bail, we don't need to alter the temp title because the original template title is missing
                            $flag = true;
                            break;
                        }

                        continue;
                    }

                    // check for missing template titles
                    if ( ! in_array( $template_title . ' (' . ( $index + 1 ) . ')' , $titles ) ) {
                        $template_title = $template_title . ' (' . ( $index + 1 ) . ')';
                        //$reply['case'] = 'one of the Single Post Template 1 (2) .. (3) .. (4) .. is missing';

                        // set the flag
                        $flag = true;
                        break;
                    }
                }

                // if we haven't set the title above set the posts count title
                if ( !$flag ) {
                    $template_title = $template_title . ' (' . ( $titles_count + 1 ) . ')';
                    //$reply['case'] = 'we haven\'t set the title in the foreach loop so we set the posts count to the template title';
                }

            }

            if ( 'page' === $template_type ) {

                if ( $duplicate_template ) {
                    $new_post = array(
                        'post_title' => $template_title,
                        'post_status' => 'draft',
                        'post_type' => 'page',
                        'post_content' => get_post_field( 'post_content', $post_id ),
                    );

	                $tdc_page_cloud_import_meta = get_post_meta( $post_id, 'tdc_page_cloud_import', true );
	                if ( !empty( $tdc_page_cloud_import_meta ) ) {
		                $new_post['meta_input'] = array(
			                'tdc_page_cloud_import' => 1
		                );
                        $new_post['post_status'] = 'publish';
	                }

	                $tdc_homepage_cloud_import_meta = get_post_meta( $post_id, 'tdc_homepage_cloud_import', true );
	                if ( !empty( $tdc_homepage_cloud_import_meta ) ) {
		                $new_post['meta_input'] = array(
			                'tdc_homepage_cloud_import' => 1
		                );
		                $new_post['post_status'] = 'publish';
	                }

                } else {
                    $new_post = array(
                        'post_title' => $template_title,
                        'post_status' => 'publish',
                        'post_type' => 'page',
	                    'post_content' => $template_content,
                    );
                }

            	if ( '1' === $request->get_param( 'isMobile' ) ) {
            	    $new_post['meta_input'] = array(
                        'tdc_is_mobile_template' => 1
                    );
                }

            	if ( $request->get_param( 'pageCloudImport' ) ) {
            	    $new_post['meta_input'] = array(
                        'tdc_page_cloud_import' => 1
                    );
                }

            	if ( $request->get_param( 'homepageCloudImport' ) ) {
            	    $new_post['meta_input'] = array(
                        'tdc_homepage_cloud_import' => 1
                    );
                }

            } else {

                if ( $duplicate_template ) {
                    $new_post = array(
                        'post_title' => $template_title,
                        'post_status' => 'publish',
                        'post_type' => 'tdb_templates',
                        'post_content' => get_post_field( 'post_content', $post_id ),
                        'meta_input'   => array(
                            'tdb_template_type' => $template_type
                        )
                    );
                } else {
                    $new_post = array(
                        'post_title' => $template_title,
                        'post_status' => 'publish',
                        'post_type' => 'tdb_templates',
                        'post_content' => $template_content,
                        'meta_input'   => array(
                            'tdb_template_type' => $template_type
                        )
                    );
                }

                if ( '1' === $request->get_param( 'isMobile' )) {
            	    $new_post['meta_input']['tdc_is_mobile_template'] = 1;
                }
            }

            // This pre title string is used as a flag by wpml hooks, to avoid touching these posts at creation
            if ( class_exists('SitePress') ) {
	            $new_post['post_title'] = 'NEW_CLOUD_TEMPLATE' . $new_post[ 'post_title' ];
            }

            // new post / page + error check
            $template_id = wp_insert_post ($new_post);
            if ( is_wp_error($template_id) ) {
                $reply['error'] = 'error - ' . $template_id->get_error_message();
                die( json_encode( $reply ) );
            }
            if ( $template_id === 0 ) {
                $reply['error'] = 'wp_insert_post returned 0. Not ok!';
                die( json_encode( $reply ) );
            }
            if ( $duplicate_template && 'header' === $template_type ) {
                add_post_meta(
                    $template_id, 'tdc_header_template_id', $template_id
                );
            }

            if ( 'header' === $template_type ) {
                update_post_meta($template_id, 'tdc_header_template_id', $template_id);
            }

            if ( 'footer' === $template_type ) {
                update_post_meta($template_id, 'tdc_footer_template_id', $template_id);
            }

            if ( 'page' !== $template_type && $duplicate_template ) {

                $meta_is_mobile_template = get_post_meta($post_id, 'tdc_is_mobile_template', true );
                if (!empty($meta_is_mobile_template)) {
                    update_post_meta($template_id, 'tdc_is_mobile_template', 1);
                }

                $meta_mobile_template_id = get_post_meta($post_id, 'tdc_mobile_template_id', true);
                if (!empty($meta_mobile_template_id)) {
                    update_post_meta($template_id, 'tdc_mobile_template_id', $meta_mobile_template_id);
                }
            }


            // WPML FIX - used to ensure translations for saved posts. Without it the post is saved in wp by wp_insert_post, but can't be used
            if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
                global $sitepress;

                $sitepress->verify_post_translations( 'page' );

                do_action( 'wpml_set_element_language_details', [
                    'element_id'    => $template_id,
                    'element_type'  => 'post_tdb_templates',
                    'trid'          => $template_id,
                    'language_code' => ICL_LANGUAGE_CODE,
                ] );
            }

            // saving options
            $options = $request->get_param( 'options' );
            if (!empty($options)) {

                $options = json_decode( $options, true );

                foreach ($options as $key => $val) {
                    switch ($key) {
                        case 'tdc_wm_global_colors':

                            $new_vals = [];
                            if (is_array($val)) {
                                foreach ($val as $key_1 => $val_1) {
                                    switch ($key_1) {
                                        case 'primary':
                                        case 'secondary':
                                        case 'accent_color':
                                            break;
                                        default:
                                            $new_vals[$key_1] = $val_1;
                                    }
                                }
                                if (!empty($new_vals)) {
                                    $existing_vals = td_util::get_option($key);
                                    if (empty($existing_vals)) {
                                        $existing_vals = array();
                                    }
                                    td_util::update_option($key, array_merge($new_vals, $existing_vals));
                                }
                            }

                            break;
                    }
                }
            }


            $reply['template_title'] = $template_title;
            $reply['template_id'] = $template_id;
            $reply['template_edit_url'] = admin_url("post.php?post_id=$template_id&td_action=tdc&tdbTemplateType=$template_type");

            die( json_encode( $reply ) );
        },
        'permission_callback' => '__return_true',
    ));


    /**
     * tagDiv Cloud api proxy - to prevent issues with cross domain requests we proxy all the request via php
     */
    register_rest_route($namespace, '/td_cloud_proxy/', array(
        'methods'  => 'POST',
        'callback' => function($request) {

	        $reply = array();

	        $cloud_end_point = $request->get_param('cloudEndPoint');

            // permission check
            if ( ! current_user_can( 'edit_pages' ) && 'templates/get_all' !== $cloud_end_point ) {
	            $reply['error'] = array(
	            	array(
			            'type' => 'Proxy ERROR',
			            'message' => 'You have no permission to access this endpoint.',
			            'debug_data' => ''
		            )
	            );
                die( json_encode( $reply ) );
            }

            if (empty($cloud_end_point)) {
	            $reply['error'] = array(
	            	array(
			            'type' => 'Proxy ERROR',
			            'message' => 'No cloudEndPoint received. Please use tdApi.cloudRun for proxy requests.',
			            'debug_data' => $request
		            )
	            );
                die( json_encode( $reply ) );
            }

	        $cloud_post = $request->get_param('cloudPost');

	        //POST parameters
	        $cloud_post['envato_key'] = '';
	        $cloud_post['theme_version'] = TD_THEME_VERSION;
	        //$cloud_post['deploy_mode'] = 'deploy'; //TDB_DEPLOY_MODE;
	        $cloud_post['deploy_mode'] = TDB_DEPLOY_MODE;
	        $cloud_post['host'] = $_SERVER['HTTP_HOST'];

	        if (in_array($cloud_end_point, ['templates/activate_domain', 'templates/check_domains'])) {
	            delete_transient('TD_CHECKED_LICENSE');
            }

	        if ( ! isset( $cloud_post['wp_type'] ) ) {
	        	$cloud_post['wp_type'] = '';
	        }

	        $api_url = tdb_util::get_api_url();

            //if (true || TDB_DEPLOY_MODE !== 'dev') {
            if (TDB_DEPLOY_MODE !== 'dev') {
	            $envato_key = base64_decode(td_util::get_option('td_011'));

	            //theme is not registered
//	            if (empty($envato_key)) {
//		            $reply['error'] = array(
//		            	array(
//				            'type' => 'Proxy ERROR',
//				            'message' => 'The theme is not activated. You can activate it from ' . TD_THEME_NAME . ' > Activate Theme section',
//				            'debug_data' => array(
//					            'envato_key' => $envato_key
//				            )
//			            )
//		            );
//		            die(json_encode($reply));
//	            }

	            $cloud_post['envato_key'] = $envato_key;
            }

	        $api_response = wp_remote_post($api_url . '/' . $cloud_end_point, array (
		        'method' => 'POST',
		        'body' => $cloud_post,
		        'timeout' => 12
	        ));

//            $file = fopen("d:\log.txt", "w");
//            ob_start();
//            var_dump( $api_url . '/' . $cloud_end_point );
//            var_dump( $cloud_post );
//            fwrite( $file, ob_get_clean() );
//			fclose( $file );

	        if (is_wp_error($api_response)) {
		        //http error
			    $reply['error'] = array(
				    array(
					    'type' => 'Proxy ERROR',
					    'message' => 'Failed to contact the templates API server.',
					    'debug_data' => $api_response
				    )
			    );
		        die(json_encode($reply));
	        }

	        if (isset($api_response['response']['code']) and $api_response['response']['code'] != '200') {
		        //response code != 200
		        $reply['error'] = array(
			        array(
				        'type' => 'Proxy ERROR',
				        'message' => 'Received a response code != 200 while trying to contact the templates API server.',
				        'debug_data' => $api_response
			        )
		        );
		        die(json_encode($reply));
	        }

	        if (empty($api_response['body'])) {
		        //response body is empty
		        $reply['error'] = array(
			        array(
				        'type' => 'Proxy ERROR',
				        'message' => 'Received an empty response body while contacting the templates API server.',
				        'debug_data' => $api_response
			        )
		        );
		        die(json_encode($reply));
	        }

	        die($api_response['body']);
        },
        'permission_callback' => '__return_true',
    ));



    /**
     * tagDiv Cloud api proxy - work cloud - to prevent issues with cross domain requests we proxy all the request via php
     */
    register_rest_route($namespace, '/td_cloud_proxy_work_cloud/', array(
        'methods'  => 'POST',
        'callback' => function($request) {

	        $reply = array();

	        $cloud_end_point = $request->get_param('cloudEndPoint');

            // permission check
            if ( ! current_user_can( 'edit_pages' ) && 'templates/get_all' !== $cloud_end_point ) {
	            $reply['error'] = array(
	            	array(
			            'type' => 'Proxy ERROR',
			            'message' => 'You have no permission to access this endpoint.',
			            'debug_data' => ''
		            )
	            );
                die( json_encode( $reply ) );
            }

            if (empty($cloud_end_point)) {
	            $reply['error'] = array(
	            	array(
			            'type' => 'Proxy ERROR',
			            'message' => 'No cloudEndPoint received. Please use tdApi.cloudRun for proxy requests.',
			            'debug_data' => $request
		            )
	            );
                die( json_encode( $reply ) );
            }

	        $cloud_post = $request->get_param('cloudPost');

	        //POST parameters
	        $cloud_post['envato_key'] = '';
	        $cloud_post['theme_version'] = TD_THEME_VERSION;
	        $cloud_post['deploy_mode'] = TDB_DEPLOY_MODE;

	        if ( ! isset( $cloud_post['wp_type'] ) ) {
	        	$cloud_post['wp_type'] = '';
	        }

	        $api_url = 'https://work-cloud.tagdiv.com/api';

            if (TDB_DEPLOY_MODE !== 'dev') {
	            $envato_key = base64_decode(td_util::get_option('td_011'));

	            //theme is not registered
//	            if (empty($envato_key)) {
//		            $reply['error'] = array(
//		            	array(
//				            'type' => 'Proxy ERROR',
//				            'message' => 'The theme is not activated. You can activate it from ' . TD_THEME_NAME . ' > Activate Theme section',
//				            'debug_data' => array(
//					            'envato_key' => $envato_key
//				            )
//			            )
//		            );
//		            die(json_encode($reply));
//	            }

	            $cloud_post['envato_key'] = $envato_key;
            }

	        $api_response = wp_remote_post($api_url . '/' . $cloud_end_point, array (
		        'method' => 'POST',
		        'body' => $cloud_post,
		        'timeout' => 12
	        ));

//            $file = fopen("d:\log.txt", "w");
//            ob_start();
//            var_dump( $api_url . '/' . $cloud_end_point );
//            var_dump( $cloud_post );
//            fwrite( $file, ob_get_clean() );
//			fclose( $file );

	        if (is_wp_error($api_response)) {
		        //http error
			    $reply['error'] = array(
				    array(
					    'type' => 'Proxy ERROR',
					    'message' => 'Failed to contact the templates API server.',
					    'debug_data' => $api_response
				    )
			    );
		        die(json_encode($reply));
	        }

	        if (isset($api_response['response']['code']) and $api_response['response']['code'] != '200') {
		        //response code != 200
		        $reply['error'] = array(
			        array(
				        'type' => 'Proxy ERROR',
				        'message' => 'Received a response code != 200 while trying to contact the templates API server.',
				        'debug_data' => $api_response
			        )
		        );
		        die(json_encode($reply));
	        }

	        if (empty($api_response['body'])) {
		        //response body is empty
		        $reply['error'] = array(
			        array(
				        'type' => 'Proxy ERROR',
				        'message' => 'Received an empty response body while contacting the templates API server.',
				        'debug_data' => $api_response
			        )
		        );
		        die(json_encode($reply));
	        }

	        //var_dump($api_response['body']);

	        $body = json_decode($api_response['body'], true);

	        if (isset($body['api_reply'])) {
	        	if (isset($body['api_reply']['error'])) {
	        		//cloud error
			        $proxy_error = array(
				        'type' => 'Proxy ERROR',
				        'message' => 'The templates API server responded with an error.',
				        'debug_data' => ''
			        );
			        array_unshift($body['api_reply']['error'], $proxy_error);
			        $reply['error'] = $body['api_reply']['error'];
		        } elseif(isset($body['api_reply']['fatal_error'])) {
	        		//fatal error
			        $reply['error'] = array(
				        array(
					        'type' => 'Proxy ERROR',
					        'message' => 'The templates API server responded with a fatal error.',
					        'debug_data' => $body['api_reply']['fatal_error']
				        )
			        );
		        } else {
	        		//regular reply
			        $reply = $body['api_reply'];
		        }
	        } else {
		        $reply['error'] = array(
			        array(
				        'type' => 'Proxy ERROR',
				        'message' => 'Invalid API reply, it does not contain the expected response.',
				        'debug_data' => $api_response
			        )
		        );
	        }

            die(json_encode($reply));
        },
        'permission_callback' => '__return_true',
    ));

    /**
     * image download endpoint
     */
	register_rest_route($namespace, '/download_image/', array(
        'methods' => 'POST',
        'callback' => function ($request) {

            // permission check
            if (!current_user_can('edit_pages')) {
	            $reply['error'] = array(
		            array(
			            'type' => 'Proxy ERROR',
			            'message' => 'You have no permission to access this endpoint.',
			            'debug_data' => ''
		            )
	            );
                die(json_encode($reply));
            }

            $image = $request->get_param('image');
            $templateId = $request->get_param('template_id');
            $install_uid = $request->get_param('install_uid');
            $current_step = $request->get_param('current_step');
            $total_steps = $request->get_param('total_steps');


            // params checks
            if (empty($image['uid'])) {
	            $reply['error'] = array(
		            array(
			            'type' => 'Proxy ERROR',
			            'message' => 'No uid provided.',
			            'debug_data' => ''
		            )
	            );
                die(json_encode($reply));
            }

	        $folder_a = substr($image['uid'], 0, 4);
            $folder_b = substr($image['uid'], 4, 2);

            $api_url = tdb_util::get_api_url('images');
	        $image_url = $api_url . '/' . $folder_a . '/' . $folder_b . '/' . $image['uid'] . '.' . $image['ext'];

	        require_once(ABSPATH . 'wp-admin/includes/media.php');
	        require_once(ABSPATH . 'wp-admin/includes/file.php');
	        require_once(ABSPATH . 'wp-admin/includes/image.php');

	        // Set variables for storage, fix file filename for query strings.
	        preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image_url, $matches );
	        $file_array = array();
	        $file_array['name'] = basename( $matches[0] );

	        // Download file to temp location.
	        $file_array['tmp_name'] = download_url( $image_url );

	        // If error storing temporarily, return the error.
	        if ( is_wp_error( $file_array['tmp_name'] ) ) {
	            @unlink($file_array['tmp_name']);
		        $reply['error'] = array(
			        array(
				        'type' => 'Proxy ERROR',
				        'message' => 'is_wp_error - error storing temporarily.',
				        'debug_data' => array(
				        	'image_url' => $image_url,
					        'tmp_name' => $file_array['tmp_name']
				        )
			        )
		        );
		        die(json_encode($reply));
	        }

	        // Do the validation and storage stuff.
	        $id = media_handle_sideload( $file_array, '', '' ); //$id of attachement or wp_error

	        // If error storing permanently, unlink.
	        if ( is_wp_error( $id ) ) {
	            @unlink( $file_array['tmp_name'] );
		        $reply['error'] = array(
			        array(
				        'type' => 'Proxy ERROR',
				        'message' => 'is_wp_error - error storing permanently.',
				        'debug_data' => array(
					        'image_url' => $image_url,
					        '$id' => $id->get_error_messages()
				        )
			        )
		        );
		        die(json_encode($reply));
	        }

	        // The next commented code was used to delete not used images
	        // Instead of it we add 'tdb_image' meta on each attachment
	        update_post_meta( $id, 'tdb_image', true );


//	        // Delete any temp (not finished install) images
//	        if ( 1 === $current_step ) {
//
//	        	$temp_installed_images = get_post_meta( $templateId, 'tdb_temp_installed_images', true );
//
//	        	if ( ! empty( $temp_installed_images ) ) {
//	        		$att_ids = explode(';', $temp_installed_images );
//	        		foreach ( $att_ids as $att_id ) {
//	        			wp_delete_attachment( $att_id, true );
//			        }
//		        }
//
//	        	delete_post_meta( $templateId, 'tdb_temp_installed_images' );
//	        }
//
//
//	        // Delete any images loaded by a previous install
//	        $meta_installed_uid = get_post_meta( $templateId, 'tdb_install_uid', true );
//
//	        // Delete all attachments of the previous installations
//	        if ( $meta_installed_uid !== $install_uid ) {
//	        	$temp_installed_images = get_post_meta( $templateId, 'tdb_temp_installed_images', true );
//
//	        	if ( ! empty( $temp_installed_images ) ) {
//	        		$att_ids = explode(';', $temp_installed_images );
//	        		foreach ( $att_ids as $att_id ) {
//	        			wp_delete_attachment( $att_id, true );
//			        }
//		        }
//
//		        $temp_installed_images = '';
//	        } else {
//		        $temp_installed_images = get_post_meta( $templateId, 'tdb_temp_installed_images', true );
//	        }
//
//	        if ( empty( $temp_installed_images ) ) {
//	            $temp_installed_images = $id;
//	        } else {
//	            $temp_installed_images .= ';' . $id;
//	        }
//
//	        // As final step, delete previous installed images
//	        if ( $total_steps === $current_step ) {
//	            delete_post_meta( $templateId, 'tdb_temp_installed_images' );
//
//	            $installed_images = get_post_meta( $templateId, 'tdb_installed_images', true );
//
//	        	if ( ! empty( $installed_images ) ) {
//	        		$att_ids = explode(';', $installed_images );
//	        		foreach ( $att_ids as $att_id ) {
//	        			wp_delete_attachment( $att_id, true );
//			        }
//		        }
//
//	            update_post_meta( $templateId, 'tdb_installed_images', $temp_installed_images );
//	        } else {
//	        	update_post_meta( $templateId, 'tdb_temp_installed_images', $temp_installed_images );
//	        }

	        update_post_meta( $templateId, 'tdb_install_uid', $install_uid );



            die(json_encode(array(
	            'uid' => $image['uid'],
	            'attachment_id' => $id,
	            'url' => wp_get_attachment_image_src( $id, 'full' )[0]
            )));
        },
        'permission_callback' => '__return_true',
    ));


	/**
     * assign cloud template endpoint
     */
	register_rest_route($namespace, '/assign_template/', array(
        'methods' => 'POST',
        'callback' => function ($request) {

            // permission check
            if (!current_user_can('edit_pages')) {
	            $reply['error'] = array(
		            array(
			            'type' => 'Proxy ERROR',
			            'message' => 'You have no permission to access this endpoint.',
			            'debug_data' => ''
		            )
	            );
                die(json_encode($reply));
            }

            // check for post id
            $ref_id = $request->get_param( 'refId' );
            if (empty($ref_id)) {
                $reply['error'] = 'The ref id is missing and it\'s required!';
                die( json_encode( $reply ) );
            }

            // check for template id
            $template_id = $request->get_param( 'templateId' );
            if (empty($template_id)) {
                $reply['error'] = 'The template id is missing and it\'s required!';
                die( json_encode( $reply ) );
            }

            // check for template type
            $template_type = $request->get_param( 'templateType' );
            if (empty($template_type)) {
                $reply['error'] = 'The template type is missing and it\'s required!';
                die( json_encode( $reply ) );
            }

            // check for mobile assignment
            $templateIsMobile = $request->get_param( 'templateIsMobile' );

            if (!empty($templateIsMobile) && ( '1' == $templateIsMobile || 'true' == $templateIsMobile) ) {
                $result = update_post_meta( $template_id, 'tdc_is_mobile_template', 1 );

                if ( false === $result ) {
                    die(json_encode(array(
                        'post_url' => get_permalink($template_id)
                    )));
                } else {

                    // check for mobile assignment
                    $assignMobile = $request->get_param( 'assignMobile' );
                    if (!empty($assignMobile) && true == $assignMobile ) {
                        if ( get_post($ref_id) instanceof WP_Post ) {
	                        $result = update_post_meta( $ref_id, 'tdc_mobile_template_id', $template_id );

	                        if ( false !== $result ) {
		                        die( json_encode( array(
			                        'post_url' => get_permalink( $template_id )
		                        ) ) );
	                        }
                        }
                    }
                }
            }

            if ( 'single' === $template_type ) {
                $td_post_theme_settings = get_post_meta($ref_id, 'td_post_theme_settings', true);
                $td_post_theme_settings = maybe_unserialize( $td_post_theme_settings );

                $td_post_theme_settings['td_post_template'] = 'tdb_template_' . $template_id;

                $result = update_post_meta($ref_id, 'td_post_theme_settings', $td_post_theme_settings);

                if ( false !== $result ) {
                    die(json_encode(array(
                        'post_url' => get_permalink($ref_id)
                    )));
                }

            } else if ( 'page' === $template_type ) {
                $result = update_post_meta($ref_id, 'tdc_mobile_template_id', $template_id);

                if ( false !== $result ) {
                    die(json_encode(array(
                        'post_url' => get_permalink($ref_id)
                    )));
                }

            } else if ( 'category' === $template_type ) {

                td_panel_data_source::update_category_option( $ref_id, 'tdb_category_template', 'tdb_template_' . $template_id );

                die(json_encode(array(
                    'post_url' => get_category_link(intval($ref_id))
                )));

            } else if ( '404' === $template_type ) {

                if (empty($templateIsMobile)) {
	                td_util::update_option( 'tdb_404_template', 'tdb_template_' . $template_id );
                }

                die(json_encode(array(
                    'post_url' => $ref_id,
                    'encoded_url' => true
                )));

            } else if ( 'date' === $template_type ) {

                if (empty($templateIsMobile)) {
	                td_util::update_option( 'tdb_date_template', 'tdb_template_' . $template_id );
                }

                die(json_encode(array(
                    'post_url' => $ref_id,
                    'encoded_url' => true
                )));
            } else if ( 'search' === $template_type ) {

                if (empty($templateIsMobile)) {
	                td_util::update_option( 'tdb_search_template', 'tdb_template_' . $template_id );
                }

                die(json_encode(array(
                    'post_url' => $ref_id,
                    'encoded_url' => true
                )));
            } else if ( 'attachment' === $template_type ) {

                if (empty($templateIsMobile)) {
	                td_util::update_option( 'tdb_attachment_template', 'tdb_template_' . $template_id );
                }

                die(json_encode(array(
                    'post_url' => $ref_id,
                    'encoded_url' => true
                )));
            } else if ( 'author' === $template_type ) {

                $td_options = &td_options::get_all_by_ref();
                if ( empty($template_id)) {
                    if ( ! empty($td_options['tdb_author_templates'][$ref_id])) {
                        unset($td_options['tdb_author_templates'][$ref_id]);
                    }
                } else {
                    $td_options['tdb_author_templates'][$ref_id] = 'tdb_template_' . $template_id;
                }

                die(json_encode(array(
                    'post_url' => get_author_posts_url( $ref_id )
                )));
            } else if ( 'tag' === $template_type ) {

                $td_options = &td_options::get_all_by_ref();

                $tag = get_tag($ref_id);

                if ( $tag instanceof WP_Term ) {

	                if ( empty( $template_id ) ) {

		                foreach ( $td_options[ 'tdb_tag_templates' ] as $tdb_tag_template_id => $tags ) {
			                $arr_tags = explode( ',', $tags );
			                if ( ! empty( $arr_tags ) ) {
				                $final_arr_tags = [];
                                foreach ( $arr_tags as $val ) {
                                    if ( trim( $val ) !== $tag->slug ) {
                                        $final_arr_tags[] = trim( $val );
                                    }
                                }
				                if ( empty($final_arr_tags)) {
                                    $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = '';
                                } else {
                                    $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = implode( ',', array_unique( $final_arr_tags ) );
                                }
			                }
		                }
                    } else {

	                    $skip_step = false;
	                    if ( empty($td_options[ 'tdb_tag_templates' ][ 'tdb_template_' . $template_id ])) {
		                    $td_options[ 'tdb_tag_templates' ][ 'tdb_template_' . $template_id ] = $tag->slug;
		                    $skip_step = true;
	                    }

                        foreach ( $td_options[ 'tdb_tag_templates' ] as $tdb_tag_template_id => $tags ) {

                            // Add slug in slug list
                            if ( $tdb_tag_template_id === 'tdb_template_' . $template_id ) {
                                if ( $skip_step ) {
                                    continue;
                                }

                                $arr_tags = explode( ',', $tags );
                                if ( empty( $arr_tags ) ) {
                                    $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = $tag->slug;
                                } else {
                                    $arr_tags[] = $tag->slug;
                                    $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = implode( ',', array_unique( $arr_tags ) );
                                }

                            // clear slug from slug list
                            } else {

                                $arr_tags = explode( ',', $tags );
                                if ( ! empty( $arr_tags ) ) {
                                    $final_arr_tags = [];
                                    foreach ( $arr_tags as $val ) {
                                        if ( trim( $val ) !== $tag->slug ) {
                                            $final_arr_tags[] = trim( $val );
                                        }
                                    }
                                    if ( empty($final_arr_tags)) {
                                        $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = '';
                                    } else {
                                        $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = implode( ',', array_unique( $final_arr_tags ) );
                                    }
                                }
                            }
                        }
	                }

	                die( json_encode( array(
		                'post_url' => get_tag_link( $ref_id )
	                ) ) );
                }

            } else if ( 'woo_product' === $template_type ) {

//                if (empty($templateIsMobile)) {
//	                td_util::update_option( 'tdb_woo_product_template', 'tdb_template_' . $template_id );
//                }

                if ( function_exists('wc_get_product')) {

	                $product = wc_get_product( $ref_id );

	                if ( !$product || !$product instanceof WC_Product ) {
		                $reply['error'] = 'Invalid product id: "' . $ref_id . '" is not a product id.';
		                die( json_encode( $reply ) );
	                }

	                $td_post_theme_settings = td_util::get_post_meta_array($ref_id, 'td_post_theme_settings');
                    if ( empty($template_id )) {
                        $td_post_theme_settings['td_post_template']  = '';
                    } else {
                        $td_post_theme_settings['td_post_template']  = 'tdb_template_' . $template_id;
                    }

                    $result = update_post_meta( $ref_id, 'td_post_theme_settings', $td_post_theme_settings );

	                die( json_encode( array(
		                'post_url' => $product->get_permalink()
	                ) ) );
                }
            }

            die(json_encode(array(
	            'post_url' => ''
            )));
        },
        'permission_callback' => '__return_true',
    ));



	/**
     * update transient endpoint
     */
	register_rest_route($namespace, '/transients/', array(
        'methods' => 'POST',
        'callback' => function ($request) {

            // permission check
            if (!current_user_can('edit_pages')) {
	            $reply['error'] = array(
		            array(
			            'type' => 'Proxy ERROR',
			            'message' => 'You have no permission to access this endpoint.',
			            'debug_data' => ''
		            )
	            );
                die(json_encode($reply));
            }

            // check for post id
            $options = $request->get_param( 'options' );
            if (empty($options)) {
                $reply['error'] = 'The options are missing and it\'s required!';
                die( json_encode( $reply ) );
            }

            foreach ($options as $item) {
                switch ($item['op']) {
                    case 'update':
                        set_transient($item['name'], $item['val'], $item['time']);
                        break;
                    case 'delete':
                        delete_transient($item['name']);
                        break;
                }
            };

            die(json_encode(array(
	            'success' => true
            )));
        },
        'permission_callback' => '__return_true',
    ));


	/**
     * update theme options endpoint
     */
	register_rest_route($namespace, '/update_options/', array(
        'methods' => 'POST',
        'callback' => function ($request) {

            // permission check
            if (!current_user_can('edit_pages')) {
	            $reply['error'] = array(
		            array(
			            'type' => 'Proxy ERROR',
			            'message' => 'You have no permission to access this endpoint.',
			            'debug_data' => ''
		            )
	            );
                die(json_encode($reply));
            }

            // check for post id
            $options = $request->get_param( 'options' );
            if (empty($options)) {
                $reply['error'] = 'The options are missing and it\'s required!';
                die( json_encode( $reply ) );
            }

            $options = json_decode( $options, true );

            foreach ($options as $key => $val) {
                switch ($key) {
                    case 'tdc_wm_global_colors':

                        $new_vals = [];
                        if (is_array($val)) {
                            foreach ($val as $key_1 => $val_1) {
                                switch ($key_1) {
                                    case 'primary':
                                    case 'secondary':
                                    case 'accent_color':
                                        break;
                                    default:
                                        $new_vals[$key_1] = $val_1;
                                }
                            }
                            if (!empty($new_vals)) {
                                $existing_vals = td_util::get_option($key);
                                if (empty($existing_vals)) {
                                    $existing_vals = array();
                                }
                                td_util::update_option($key, array_merge($new_vals, $existing_vals));
                            }
                        }

                        break;
                }
            }

            die(json_encode(array(
	            'success' => false
            )));
        },
        'permission_callback' => '__return_true',
    ));


    /**
     * posts ajax autoload(infinite) using iframes
     */
    register_rest_route($namespace, '/ajax_autoload/', array(
        'methods'  => 'POST',
        'callback' => function($request){

            // check for post id
            $id = $request->get_param('currentPostId');

            if ( empty($id) ) {
                $reply['error'] = 'Post id is missing and it\'s required!';
                die( json_encode($reply) );
            }

            global $post;
            $post = get_post($id);

            $tdb_p_infinite_type = td_util::get_option('tdb_p_autoload_type', '');

            switch ( $tdb_p_infinite_type ) {

                case 'next':
                    // get the next post
                    $next_post = get_next_post();

                    break;

                case 'same_cat_prev':
                    // get the previous post from the same category
                    $next_post = get_previous_post(true);

                    break;

                case 'same_cat_next':
                    // get the next post from the same category
                    $next_post = get_next_post(true);

                    break;

                case 'same_cat_latest':

                    // get the loaded posts ids
                    $posts_to_exclude = $request->get_param('loadedPosts');

                    // get the original post id
                    $original_post_id = $request->get_param('originalPostId');

                    // query arguments to get the next post to display
                    $args = array(
                        'ignore_sticky_posts' => 1,
                        'post_status' => 'publish',
                        'posts_per_page' => 1,
                        'category__in' => wp_get_post_categories($original_post_id),
                        'post__not_in' => $posts_to_exclude,
                    );

                    $reply['currentPostId'] = $request->get_param('currentPostId');
                    $reply['originalPostId'] = $request->get_param('originalPostId');
                    $reply['loadedPosts'] = $request->get_param('loadedPosts');
                    $reply['args'] = $args;

                    // get the next post to show
                    $posts = get_posts($args);

                    $reply['nextPost'] = $posts;

                    if ( !empty($posts) ) {
                        // get the post to load from the posts array ( the posts array should contain just one post or be empty )
                        $next_post = $posts[0];
                    }

                    break;

                default:
	                // by default get the previous post
                    $next_post = get_previous_post();

            }

            if ( !empty($next_post) ) {
                $post_to_load_id = $next_post->ID;

                $reply['type'] = $tdb_p_infinite_type;
                $reply['id'] = $post_to_load_id;

                $post_to_load_url = get_permalink($post_to_load_id);
                $post_to_load_edit_url = get_edit_post_link($post_to_load_id);
                $post_to_load_title = get_the_title($post_to_load_id);

                if ( strpos( $post_to_load_url,'?' ) === false ) {
                    $post_iframe_src = $post_to_load_url . '?tdb_action=tdb_ajax';
                } else {
                    $post_iframe_src = $post_to_load_url . '&tdb_action=tdb_ajax';
                }

                ob_start();

                ?>

                <iframe
                        id="tdb-infinte-post-<?php echo $post_to_load_id ?>-iframe"
                        class="tdb-infinte-post-iframe"
                        name="tdb-infinte-post-iframe"
                        src="<?php echo $post_iframe_src ?>"
                        scrolling="auto"
                        style="
                            display: block;
                            width: 100%;
                            height: 0;
                            border: 0;
                            /*outline: #000 dashed 1px;*/
                            opacity: 0;
                            -webkit-transition: opacity 0.4s;
                            transition: opacity 0.4s;
                            overflow: hidden;
                        "
                        data-post-url="<?php echo esc_url($post_to_load_url); ?>"
                        data-post-edit-url="<?php echo esc_url($post_to_load_edit_url); ?>"
                        data-post-title="<?php echo esc_attr($post_to_load_title); ?>"
                        title="<?php echo esc_attr($post_to_load_title); ?>"
                ></iframe>

                <?php

                $reply['article'] = ob_get_contents();

                if ( ob_get_contents() ) {
                    ob_end_clean();
                }

            } else {
                $reply['noPosts'] = array(
                    array(
                        'type' => $tdb_p_infinite_type,
                        'message' => 'No other corresponding post exists to be loaded!'
                    )
                );
            }

            die( json_encode($reply) );
        },
        'permission_callback' => '__return_true',
    ));

});


add_action('wp_ajax_nopriv_tdb_get_single_templates', 'tdb_get_single_templates');
add_action('wp_ajax_tdb_get_single_templates', 'tdb_get_single_templates');
function tdb_get_single_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'single',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'td_default_site_post_template';
        $td_default_site_post_template = td_util::get_option( $option_id );

        $global_single_template_id = '';
        if ( ! empty( $td_default_site_post_template ) && td_global::is_tdb_template( $td_default_site_post_template, true ) ) {
            $global_single_template_id = td_global::tdb_get_template_id( $td_default_site_post_template );
        }

        $find_current = true;

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $is_current = false;

            $post_id = $_POST['single_id'];

            if ( !empty($post_id) && $find_current ) {

                $td_post_theme_settings = td_util::get_post_meta_array($post_id, 'td_post_theme_settings');
                if ( ! empty($td_post_theme_settings['td_post_template'] ) && $td_post_theme_settings['td_post_template'] == 'tdb_template_' . $post->ID ) {
                    $is_current = true;
                    $find_current = false;
                }
            }

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_global' => intval($global_single_template_id) === intval($post->ID) ? true : false,
                'is_current' => $is_current,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_single_mobile_templates', 'tdb_get_single_mobile_templates');
add_action('wp_ajax_tdb_get_single_mobile_templates', 'tdb_get_single_mobile_templates');
function tdb_get_single_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'single',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=single')
            );
        }
    }

    die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_set_mobile_template', 'tdb_set_mobile_template');
add_action('wp_ajax_tdb_set_mobile_template', 'tdb_set_mobile_template');
function tdb_set_mobile_template (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'failed to verify nonce !';
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    $mobile_template_id = @$_POST['mobile_template_id'];

    if ( empty($template_id) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'template id is missing and is required !';
        die( json_encode( $reply ) );
    }

    if (empty($mobile_template_id)) {
        $result = delete_post_meta( $template_id, 'tdc_mobile_template_id' );
    } else {
        $result = update_post_meta( $template_id, 'tdc_mobile_template_id', $mobile_template_id );
    }

    if ( false !== $result ) {
        $reply['result'] = 1;
    }

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_single_template_to_post', 'tdb_assign_single_template_to_post');
add_action('wp_ajax_tdb_assign_single_template_to_post', 'tdb_assign_single_template_to_post');
function tdb_assign_single_template_to_post (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $post_id = $_POST['single_id'];
    $template_id = $_POST['template_id'];

    if ( empty($post_id) ) {
        die( json_encode( $reply ) );
    }

    $td_post_theme_settings = td_util::get_post_meta_array($post_id, 'td_post_theme_settings');
    if ( empty($template_id )) {
        $td_post_theme_settings['td_post_template']  = '';
    } else {
        $td_post_theme_settings['td_post_template']  = 'tdb_template_' . $template_id;
    }

    $result = update_post_meta( $post_id, 'td_post_theme_settings', $td_post_theme_settings );

    if ( false !== $result ) {
        $reply['current_template_id'] = $template_id;
    }

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_single_template_global', 'tdb_assign_single_template_global');
add_action('wp_ajax_tdb_assign_single_template_global', 'tdb_assign_single_template_global');
function tdb_assign_single_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'td_default_site_post_template' . $lang;

	td_util::update_option($option_id, 'tdb_template_' . $template_id );

    // read back the global setting
    $default_template_id = td_util::get_option( $option_id );

    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
        $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );

        if ( intval($template_id) === $tdb_template_id ) {
            $reply['global_template_id'] = $template_id;
        }
    }

	$post_id = $_POST['single_id'] ?? '';
	if ( empty( $post_id ) ) {
		die( json_encode( $reply ) );
	}

    $td_post_theme_settings = td_util::get_post_meta_array( $post_id, 'td_post_theme_settings' );
    if ( empty( $td_post_theme_settings['td_post_template'] ) ) {
        $reply['reload'] = true;
    }

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_category_templates', 'tdb_get_category_templates');
add_action('wp_ajax_tdb_get_category_templates', 'tdb_get_category_templates');
function tdb_get_category_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'category',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_category_template';
        $td_default_site_category_template = td_util::get_option( $option_id );

        $global_category_template_id = '';
        if ( ! empty( $td_default_site_category_template ) && td_global::is_tdb_template( $td_default_site_category_template, true ) ) {
            $global_category_template_id = td_global::tdb_get_template_id( $td_default_site_category_template );
        }

        $td_options = td_options::get_all();
        $find_current = true;

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $is_current = false;

            $cat_id = $_POST['category_id'];

            if ( $find_current && !empty($cat_id)
                && !empty($td_options['category_options'][$cat_id]['tdb_category_template'])
                && 'tdb_template_' . $post->ID === $td_options['category_options'][$cat_id]['tdb_category_template'] ) {

                $is_current = true;
                $find_current = false;
            }

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_global' => intval($global_category_template_id) === intval($post->ID) ? true : false,
                'is_current' => $is_current,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_cat_template_to_cat', 'tdb_assign_cat_template_to_cat');
add_action('wp_ajax_tdb_assign_cat_template_to_cat', 'tdb_assign_cat_template_to_cat');
function tdb_assign_cat_template_to_cat (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $cat_id = $_POST['category_id'];
    $template_id = $_POST['template_id'];

    if ( empty($cat_id) ) {
        die( json_encode( $reply ) );
    }

    $option_id = 'tdb_category_template';
    $new_template_id = $template_id;
    $old_template_id = td_util::get_category_option( $cat_id, $option_id);

    if (!empty($new_template_id)) {
        $new_template_id = 'tdb_template_' . $new_template_id;
    }

    td_panel_data_source::update_category_option( $cat_id, $option_id, $new_template_id );

    if ( $old_template_id !== $template_id ) {
        $reply['current_template_id'] = $template_id;
    }

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_cat_template_global', 'tdb_assign_cat_template_global');
add_action('wp_ajax_tdb_assign_cat_template_global', 'tdb_assign_cat_template_global');
function tdb_assign_cat_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_category_template' . $lang;

    td_util::update_option($option_id, 'tdb_template_' . $template_id );
    $reply['global_template_id'] = $template_id;

    // read back the global setting
    $default_template_id = td_util::get_option( $option_id );

    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
        $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );

        if ( intval($template_id) === $tdb_template_id ) {
            $reply['global_template_id'] = $template_id;
        }
    }

	$cat_id = $_POST['category_id'] ?? '';
	if ( empty( $cat_id ) ) {
		die( json_encode( $reply ) );
	}

    $tdb_category_template = td_util::get_category_option( $cat_id, $option_id );
    if ( empty( $tdb_category_template ) ) {
        $reply['reload'] = true;
    }

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_category_mobile_templates', 'tdb_get_category_mobile_templates');
add_action('wp_ajax_tdb_get_category_mobile_templates', 'tdb_get_category_mobile_templates');
function tdb_get_category_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'category',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=category')
            );
        }
    }

    die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_404_templates', 'tdb_get_404_templates');
add_action('wp_ajax_tdb_get_404_templates', 'tdb_get_404_templates');
function tdb_get_404_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => '404',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_404_template';
        $td_default_site_template = td_util::get_option( $option_id );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => intval($global_template_id) === intval($post->ID) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_404_mobile_templates', 'tdb_get_404_mobile_templates');
add_action('wp_ajax_tdb_get_404_mobile_templates', 'tdb_get_404_mobile_templates');
function tdb_get_404_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => '404',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=404')
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_404_template_global', 'tdb_assign_404_template_global');
add_action('wp_ajax_tdb_assign_404_template_global', 'tdb_assign_404_template_global');
function tdb_assign_404_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];

    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_404_template' . $lang;

    td_util::update_option($option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_404_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_404_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_404_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_date_templates', 'tdb_get_date_templates');
add_action('wp_ajax_tdb_get_date_templates', 'tdb_get_date_templates');
function tdb_get_date_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'date',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_date_template';
        $td_default_site_template = td_util::get_option( $option_id );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => intval($global_template_id) === intval($post->ID) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_date_template_global', 'tdb_assign_date_template_global');
add_action('wp_ajax_tdb_assign_date_template_global', 'tdb_assign_date_template_global');
function tdb_assign_date_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_date_template' . $lang;

	td_util::update_option($option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_date_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_date_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_date_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_date_mobile_templates', 'tdb_get_date_mobile_templates');
add_action('wp_ajax_tdb_get_date_mobile_templates', 'tdb_get_date_mobile_templates');
function tdb_get_date_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'date',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=date')
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_search_templates', 'tdb_get_search_templates');
add_action('wp_ajax_tdb_get_search_templates', 'tdb_get_search_templates');
function tdb_get_search_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'search',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_search_template';
        $td_default_site_template = td_util::get_option( $option_id );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => intval($global_template_id) === intval($post->ID) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_search_template_global', 'tdb_assign_search_template_global');
add_action('wp_ajax_tdb_assign_search_template_global', 'tdb_assign_search_template_global');
function tdb_assign_search_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];

    if ( empty($template_id)) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_search_template' . $lang;

    td_util::update_option($option_id, 'tdb_template_' . $template_id);

	// read back the global setting
	$tdb_search_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_search_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_search_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_search_mobile_templates', 'tdb_get_search_mobile_templates');
add_action('wp_ajax_tdb_get_search_mobile_templates', 'tdb_get_search_mobile_templates');
function tdb_get_search_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'search',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=search')
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_woo_product_templates', 'tdb_get_woo_product_templates');
add_action('wp_ajax_tdb_get_woo_product_templates', 'tdb_get_woo_product_templates');
function tdb_get_woo_product_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_product',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_woo_product_template';
        $td_default_site_template = td_util::get_option( $option_id );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        $find_current = true;

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $is_current = false;

            $post_id = $_POST['woo_product_id'];

            if ( !empty($post_id) && $find_current ) {

                $td_post_theme_settings = td_util::get_post_meta_array($post_id, 'td_post_theme_settings');
                if ( ! empty($td_post_theme_settings['td_post_template'] ) && $td_post_theme_settings['td_post_template'] == 'tdb_template_' . $post->ID ) {
                    $is_current = true;
                    $find_current = false;
                }
            }

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_global' => intval($global_template_id) === intval($post->ID) ? true : false,
                'is_current' => $is_current,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_woo_product_template_global', 'tdb_assign_woo_product_template_global');
add_action('wp_ajax_tdb_assign_woo_product_template_global', 'tdb_assign_woo_product_template_global');
function tdb_assign_woo_product_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_woo_product_template' . $lang;

    td_util::update_option($option_id, 'tdb_template_' . $template_id );

    // read back the global setting
    $tdb_woo_product_global_template_id = td_util::get_option( $option_id );

    if ( td_global::is_tdb_template( $tdb_woo_product_global_template_id, true ) ) {
        $tdb_template_id = td_global::tdb_get_template_id( $tdb_woo_product_global_template_id );

        if ( intval($template_id) === $tdb_template_id ) {
            $reply['global_template_id'] = $template_id;
        }
    }

	$product_id = $_POST['woo_product_id'] ?? '';
	if ( empty( $product_id ) ) {
		die( json_encode( $reply ) );
	}

    $td_post_theme_settings = td_util::get_post_meta_array( $product_id, 'td_post_theme_settings' );
    if ( empty( $td_post_theme_settings['td_post_template'] ) ) {
        $reply['reload'] = true;
    }

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_assign_woo_product_template_to_product', 'tdb_assign_woo_product_template_to_product');
add_action('wp_ajax_tdb_assign_woo_product_template_to_product', 'tdb_assign_woo_product_template_to_product');
function tdb_assign_woo_product_template_to_product (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $product_id = $_POST['woo_product_id'];
    $template_id = $_POST['template_id'];

    if ( empty($product_id) ) {
        die( json_encode( $reply ) );
    }

    $td_post_theme_settings = td_util::get_post_meta_array($product_id, 'td_post_theme_settings');
    if ( empty($template_id )) {
        $td_post_theme_settings['td_post_template']  = '';
    } else {
        $td_post_theme_settings['td_post_template']  = 'tdb_template_' . $template_id;
    }

    $result = update_post_meta( $product_id, 'td_post_theme_settings', $td_post_theme_settings );

    if ( false !== $result ) {
        $reply['current_template_id'] = $template_id;
    }

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_woo_product_mobile_templates', 'tdb_get_woo_product_mobile_templates');
add_action('wp_ajax_tdb_get_woo_product_mobile_templates', 'tdb_get_woo_product_mobile_templates');
function tdb_get_woo_product_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_product',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=woo_product')
            );
        }
    }

    die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_woo_archive_templates', 'tdb_get_woo_archive_templates');
add_action('wp_ajax_tdb_get_woo_archive_templates', 'tdb_get_woo_archive_templates');
function tdb_get_woo_archive_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_archive',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if ( !empty( $wp_query_templates->posts ) ) {

        // current term id
	    $woo_term_id = $_POST['woo_archive_id'];

	    // determine woo archive template type
	    // woo_archive(for prod categories)/woo_archive_tag(for prod tags)/woo_archive_attribute(for prod attributes)
	    $term = get_term( $woo_term_id );
	    if ( $term instanceof WP_Term ) {
		    $term_taxonomy = $term->taxonomy;
		    switch ( $term_taxonomy ) {
			    case 'product_tag':
				    $tdb_tpl_option_key = 'tdb_woo_archive_tag_template';
				    break;
			    case taxonomy_is_product_attribute( $term_taxonomy ):
				    $tdb_tpl_option_key = 'tdb_woo_archive_attribute_template';
				    break;
			    case 'product_cat':
			    default:
				    $tdb_tpl_option_key = 'tdb_woo_archive_template';
				    break;
		    }
	    } else {
		    $reply['invalid_term_id'] = $woo_term_id;
		    die( json_encode( $reply ) );
	    }

	    // check for global prod attribute taxonomy template
	    if ( $tdb_tpl_option_key === 'tdb_woo_archive_attribute_template' ) {

		    // check for global tdb template used for prod attributes taxonomy
		    $tdb_pa_tax_woo_archive_attribute_template = td_options::get( 'tdb_woo_attribute_' . $term_taxonomy . '_tax_template' );
		    if ( td_global::is_tdb_template( $tdb_pa_tax_woo_archive_attribute_template, true ) ) {

			    // get the global tdb template for prod attributes taxonomy
			    $td_global_template = $tdb_pa_tax_woo_archive_attribute_template;

		    } else {

			    // get the global tdb template for prod attributes
			    $td_global_template = td_util::get_option( $tdb_tpl_option_key );

		    }

	    } else {
		    $td_global_template = td_util::get_option( $tdb_tpl_option_key );
        }

        $global_template_id = '';
        if ( !empty( $td_global_template ) && td_global::is_tdb_template( $td_global_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_global_template );
        }

        $find_current = true;

        foreach ( $wp_query_templates->posts as $post ) {

            $is_current = false;

            if ( !empty( $woo_term_id ) && $find_current ) {

                $tdb_woo_archive_template = get_term_meta( $woo_term_id, $tdb_tpl_option_key, true);
                if ( !empty( $tdb_woo_archive_template ) && $tdb_woo_archive_template == 'tdb_template_' . $post->ID ) {
                    $is_current = true;
                    $find_current = false;
                }

            }

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta( $post->ID, 'tdc_mobile_template_id', true );

            if ( !empty( $mobile_template_id ) ) {
                $mobile_template = get_post( $mobile_template_id );
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status( $mobile_template_id ) ) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_global' => intval( $global_template_id ) === $post->ID,
                'is_current' => $is_current,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}

// used on frontend to update the global tdb template for prod tags/prod attributes and prod categories from admin bar
// @note this action is different from the one used on backend on wp admin > cloud templates because it uses the woo term id to identify the prod taxonomy
add_action('wp_ajax_nopriv_tdb_assign_woo_archive_template_global', 'tdb_assign_woo_archive_template_global');
add_action('wp_ajax_tdb_assign_woo_archive_template_global', 'tdb_assign_woo_archive_template_global');
function tdb_assign_woo_archive_template_global() {
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $woo_term_id = $_POST['woo_term_id'];
    $template_id = $_POST['template_id'];

    if ( empty( $woo_term_id ) || empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

	// determine woo archive template type
    // woo_archive(for prod categories)/woo_archive_tag(for prod tags)/woo_archive_attribute(for prod attributes)
	$term = get_term( $woo_term_id );
	if ( $term instanceof WP_Term ) {
        $term_taxonomy = $term->taxonomy;
		switch ( $term_taxonomy ) {
			case 'product_tag':
				$tdb_tpl_option_key = 'tdb_woo_archive_tag_template';
                break;
			case taxonomy_is_product_attribute( $term_taxonomy ):
				$tdb_tpl_option_key = 'tdb_woo_archive_attribute_template';
				break;
            case 'product_cat':
            default:
                $tdb_tpl_option_key = 'tdb_woo_archive_template';
                break;
        }
    } else {
		$reply['invalid_term_id'] = $woo_term_id;
		die( json_encode( $reply ) );
    }

    $lang = '';
    if ( class_exists('SitePress', false ) ) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset( $sitepress_settings['custom_posts_sync_option'][ 'tdb_templates'] ) ) {
            $translation_mode = (int) $sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if ( 1 === $translation_mode ) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $tdb_tpl_option_key = $tdb_tpl_option_key . $lang;

    td_util::update_option( $tdb_tpl_option_key, 'tdb_template_' . $template_id );
    $reply['global_template_id'] = $template_id;

    // read back the global setting
    $default_template_id = td_util::get_option( $tdb_tpl_option_key );

    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
        $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );

        if ( intval($template_id) === $tdb_template_id ) {
            $reply['global_template_id'] = $template_id;
        }
    }

    $tdb_woo_archive_template = get_term_meta( $woo_term_id, $tdb_tpl_option_key, true );
    if ( empty( $tdb_woo_archive_template ) ) {
        $reply['reload'] = true;
    }

    wp_die( json_encode( $reply ) );
}

// used on backend on wp admin > cloud templates interface to update the global tags & attributes template or individual prod attributes tax template
add_action('wp_ajax_nopriv_tdb_ct_assign_woo_archive_tpl_global', 'tdb_ct_assign_woo_archive_tpl_global');
add_action('wp_ajax_tdb_ct_assign_woo_archive_tpl_global', 'tdb_ct_assign_woo_archive_tpl_global');
function tdb_ct_assign_woo_archive_tpl_global() {
	$reply = array();

	$nonce = $_POST['_nonce'];
	if ( !wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		die( json_encode( $reply ) );
	}

	$template_id = $_POST['template_id'];
	$woo_tax = $_POST['template_type'];

	if ( empty( $template_id ) || empty( $woo_tax ) ) {
		die( json_encode( $reply ) );
	}

	if ( strpos( $woo_tax, 'pa_' ) !== false ) {
		$tdb_tpl_option_key = 'tdb_woo_attribute_' . $woo_tax . '_tax_template';
	} else {
		$tdb_tpl_option_key = 'tdb_' . $woo_tax . '_template';
	}

    $lang = '';
    if ( class_exists('SitePress', false) ) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset( $sitepress_settings['custom_posts_sync_option'][ 'tdb_templates'] ) ) {
            $translation_mode = (int) $sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if ( 1 === $translation_mode ) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

	$tdb_tpl_option_key = $tdb_tpl_option_key . $lang;

	td_util::update_option( $tdb_tpl_option_key, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_woo_tax_global_template_id = td_util::get_option( $tdb_tpl_option_key );

	if ( td_global::is_tdb_template( $tdb_woo_tax_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_woo_tax_global_template_id );

		if ( intval( $template_id ) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

	wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_assign_woo_archive_template_to_tax', 'tdb_assign_woo_archive_template_to_tax');
add_action('wp_ajax_tdb_assign_woo_archive_template_to_tax', 'tdb_assign_woo_archive_template_to_tax');
function tdb_assign_woo_archive_template_to_tax () {
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $woo_term_id = $_POST['woo_term_id'];
    $template_id = $_POST['template_id'];

    if ( empty( $woo_term_id ) ) {
        die( json_encode( $reply ) );
    }

	// determine woo archive template type
	// woo_archive(for prod categories)/woo_archive_tag(for prod tags)/woo_archive_attribute(for prod attributes)
	$term = get_term( $woo_term_id );
	if ( $term instanceof WP_Term ) {
		$term_taxonomy = $term->taxonomy;
		switch ( $term_taxonomy ) {
			case 'product_tag':
				$tdb_tpl_option_key = 'tdb_woo_archive_tag_template';
				break;
			case taxonomy_is_product_attribute( $term_taxonomy ):
				$tdb_tpl_option_key = 'tdb_woo_archive_attribute_template';
				break;
			case 'product_cat':
			default:
				$tdb_tpl_option_key = 'tdb_woo_archive_template';
				break;
		}
	} else {
		$reply['invalid_term_id'] = $woo_term_id;
		die( json_encode( $reply ) );
	}

    if ( empty( $template_id )) {
        $tdb_woo_archive_template  = '';
    } else {
        $tdb_woo_archive_template = 'tdb_template_' . $template_id;
    }

    $result = update_term_meta( $woo_term_id, $tdb_tpl_option_key, $tdb_woo_archive_template );

    if ( false !== $result ) {
        $reply['current_template_id'] = $template_id;
    }

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_woo_archive_mobile_templates', 'tdb_get_woo_archive_mobile_templates');
add_action('wp_ajax_tdb_get_woo_archive_mobile_templates', 'tdb_get_woo_archive_mobile_templates');
function tdb_get_woo_archive_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_archive',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=woo_archive')
            );
        }
    }

    die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_woo_search_archive_templates', 'tdb_get_woo_search_archive_templates');
add_action('wp_ajax_tdb_get_woo_search_archive_templates', 'tdb_get_woo_search_archive_templates');
function tdb_get_woo_search_archive_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_search_archive',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_woo_search_archive_template';
        $td_default_site_template = td_util::get_option( $option_id );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => intval($global_template_id) === intval($post->ID) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_woo_search_archive_template_global', 'tdb_assign_woo_search_archive_template_global');
add_action('wp_ajax_tdb_assign_woo_search_archive_template_global', 'tdb_assign_woo_search_archive_template_global');
function tdb_assign_woo_search_archive_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];

    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_woo_search_archive_template' . $lang;

    td_util::update_option($option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_woo_search_archive_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_woo_search_archive_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_woo_search_archive_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_woo_search_archive_mobile_templates', 'tdb_get_woo_search_archive_mobile_templates');
add_action('wp_ajax_tdb_get_woo_search_archive_mobile_templates', 'tdb_get_woo_search_archive_mobile_templates');
function tdb_get_woo_search_archive_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_search_archive',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=woo_search_archive')
            );
        }
    }

    die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_woo_shop_base_templates', 'tdb_get_woo_shop_base_templates');
add_action('wp_ajax_tdb_get_woo_shop_base_templates', 'tdb_get_woo_shop_base_templates');
function tdb_get_woo_shop_base_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_shop_base',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_woo_shop_base_template';
        $td_default_site_template = td_util::get_option( $option_id );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => intval($global_template_id) === intval($post->ID) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_woo_shop_base_template_global', 'tdb_assign_woo_shop_base_template_global');
add_action('wp_ajax_tdb_assign_woo_shop_base_template_global', 'tdb_assign_woo_shop_base_template_global');
function tdb_assign_woo_shop_base_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];

    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_woo_shop_base_template' . $lang;

    td_util::update_option($option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_woo_shop_base_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_woo_shop_base_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_woo_shop_base_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_woo_shop_base_mobile_templates', 'tdb_get_woo_shop_base_mobile_templates');
add_action('wp_ajax_tdb_get_woo_shop_base_mobile_templates', 'tdb_get_woo_shop_base_mobile_templates');
function tdb_get_woo_shop_base_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'woo_shop_base',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=woo_shop_base')
            );
        }
    }

    die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_attachment_templates', 'tdb_get_attachment_templates');
add_action('wp_ajax_tdb_get_attachment_templates', 'tdb_get_attachment_templates');
function tdb_get_attachment_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'attachment',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $option_id = 'tdb_attachment_template';
        $td_default_site_template = td_util::get_option( $option_id );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => intval($global_template_id) === intval($post->ID) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_attachment_template_global', 'tdb_assign_attachment_template_global');
add_action('wp_ajax_tdb_assign_attachment_template_global', 'tdb_assign_attachment_template_global');
function tdb_assign_attachment_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];

    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_attachment_template' . $lang;

    td_util::update_option($option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_attachment_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_attachment_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_attachment_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_attachment_mobile_templates', 'tdb_get_attachment_mobile_templates');
add_action('wp_ajax_tdb_get_attachment_mobile_templates', 'tdb_get_attachment_mobile_templates');
function tdb_get_attachment_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'attachment',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=attachment')
            );
        }
    }

    die( json_encode( $reply ) );
}



add_action('wp_ajax_nopriv_tdb_get_author_templates', 'tdb_get_author_templates');
add_action('wp_ajax_tdb_get_author_templates', 'tdb_get_author_templates');
function tdb_get_author_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'author',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $td_default_site_template = td_util::get_option( 'tdb_author_template' );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        $find_current = true;

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $is_current = false;

            $author_id = $_POST['author_id'];

            if ( ! empty($author_id) && $find_current ) {
                $tdb_author_templates = td_options::get( 'tdb_author_templates' );
                if ( ! empty($tdb_author_templates[$author_id]) && 'tdb_template_' . $post->ID === $tdb_author_templates[$author_id] ) {
                    $is_current = true;
                    $find_current = false;
                }
            }

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_global' => intval($global_template_id) === intval($post->ID) ? true : false,
                'is_current' => $is_current,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_author_template_to_author', 'tdb_assign_author_template_to_author');
add_action('wp_ajax_tdb_assign_author_template_to_author', 'tdb_assign_author_template_to_author');
function tdb_assign_author_template_to_author (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $author_id = $_POST['author_id'];
    $template_id = $_POST['template_id'];

    if ( empty($author_id) ) {
        die( json_encode( $reply ) );
    }

    $td_options = &td_options::get_all_by_ref();
    if ( empty($template_id)) {
        if ( ! empty($td_options['tdb_author_templates'][$author_id])) {
            unset($td_options['tdb_author_templates'][$author_id]);
        }
    } else {
        $td_options['tdb_author_templates'][$author_id] = 'tdb_template_' . $template_id;
    }

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_author_template_global', 'tdb_assign_author_template_global');
add_action('wp_ajax_tdb_assign_author_template_global', 'tdb_assign_author_template_global');
function tdb_assign_author_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_author_template' . $lang;

	td_util::update_option($option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_author_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_author_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_author_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

	$author_id = $_POST['author_id'];
	if ( empty( $author_id ) ) {
		die( json_encode( $reply ) );
	}

	$td_options = &td_options::get_all_by_ref();
    if ( empty( $td_options['tdb_author_templates'][$author_id] ) ) {
        $reply['reload'] = true;
    }

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_author_mobile_templates', 'tdb_get_author_mobile_templates');
add_action('wp_ajax_tdb_get_author_mobile_templates', 'tdb_get_author_mobile_templates');
function tdb_get_author_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'author',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=author')
            );
        }
    }

    die( json_encode( $reply ) );
}



add_action('wp_ajax_nopriv_tdb_get_tag_templates', 'tdb_get_tag_templates');
add_action('wp_ajax_tdb_get_tag_templates', 'tdb_get_tag_templates');
function tdb_get_tag_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'tag',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            ),
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $td_default_site_template = td_util::get_option( 'tdb_tag_template' );

        $global_template_id = '';
        if ( ! empty( $td_default_site_template ) && td_global::is_tdb_template( $td_default_site_template, true ) ) {
            $global_template_id = td_global::tdb_get_template_id( $td_default_site_template );
        }

        $tdb_tag_templates = td_options::get( 'tdb_tag_templates' );
        $find_current = true;

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $is_current = false;

            $tag_id = $_POST['tag_id'];

            if ( !empty($tag_id) && $find_current ) {
                $tag = get_tag( $tag_id );
                if ( $tag instanceof WP_Term ) {

                    if ( is_array($tdb_tag_templates)) {
                        foreach ( $tdb_tag_templates as $tdb_tag_template_id => $tags ) {

                            if ( 'tdb_template_' . $post->ID === $tdb_tag_template_id ) {

                                $arr_tags = explode( ',', $tags );

                                if ( !empty($arr_tags) && is_array($arr_tags) ) {

                                    $arr_tags = array_map( function($val) { return trim($val); }, $arr_tags);

                                    if (in_array($tag->slug, $arr_tags)) {

                                        $is_current = true;
                                        $find_current = false;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                }
            }

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_global' => intval($global_template_id) === intval($post->ID) ? true : false,
                'is_current' => $is_current,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}



add_action('wp_ajax_nopriv_tdb_assign_tag_template_to_tag', 'tdb_assign_tag_template_to_tag');
add_action('wp_ajax_tdb_assign_tag_template_to_tag', 'tdb_assign_tag_template_to_tag');
function tdb_assign_tag_template_to_tag (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $tag_id = $_POST['tag_id'];
    $template_id = $_POST['template_id'];

    if ( empty($tag_id) ) {
        die( json_encode( $reply ) );
    }

    $tag = get_tag($tag_id);
    if ( $tag instanceof WP_Term) {

        $td_options = &td_options::get_all_by_ref();

        if ( empty( $template_id ) ) {

            foreach ( $td_options[ 'tdb_tag_templates' ] as $tdb_tag_template_id => $tags ) {
                $arr_tags = explode( ',', $tags );
                if ( ! empty( $arr_tags ) ) {
                    $final_arr_tags = [];
                    foreach ( $arr_tags as $val ) {
                        if ( trim( $val ) !== $tag->slug ) {
                            $final_arr_tags[] = trim( $val );
                        }
                    }
                    if ( empty($final_arr_tags)) {
                        $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = '';
                    } else {
                        $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = implode( ',', array_unique( $final_arr_tags ) );
                    }
                }
            }
        } else {

            $skip_step = false;
            if ( empty($td_options[ 'tdb_tag_templates' ][ 'tdb_template_' . $template_id ])) {
                $td_options[ 'tdb_tag_templates' ][ 'tdb_template_' . $template_id ] = $tag->slug;
                $skip_step = true;
            }

            foreach ( $td_options[ 'tdb_tag_templates' ] as $tdb_tag_template_id => $tags ) {

                // Add slug in slug list
                if ( $tdb_tag_template_id === 'tdb_template_' . $template_id ) {
                    if ( $skip_step ) {
                        continue;
                    }

                    $arr_tags = explode( ',', $tags );
                    if ( empty( $arr_tags ) ) {
                        $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = $tag->slug;
                    } else {
                        $arr_tags[] = $tag->slug;
                        $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = implode( ',', array_unique( $arr_tags ) );
                    }

                // clear slug from slug list
                } else {

                    $arr_tags = explode( ',', $tags );
                    if ( ! empty( $arr_tags ) ) {
                        $final_arr_tags = [];
                        foreach ( $arr_tags as $val ) {
                            if ( trim( $val ) !== $tag->slug ) {
                                $final_arr_tags[] = trim( $val );
                            }
                        }
                        if ( empty($final_arr_tags)) {
                            $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = '';
                        } else {
                            $td_options[ 'tdb_tag_templates' ][ $tdb_tag_template_id ] = implode( ',', array_unique( $final_arr_tags ) );
                        }
                    }
                }
            }
        }
    }

    $reply['reload'] = true;

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_tag_template_global', 'tdb_assign_tag_template_global');
add_action('wp_ajax_tdb_assign_tag_template_global', 'tdb_assign_tag_template_global');
function tdb_assign_tag_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    if ( empty( $template_id ) ) {
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_tag_template' . $lang;

	td_util::update_option($option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_tag_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_tag_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_tag_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

	$tag_id = $_POST['tag_id'];
	if ( empty( $tag_id ) ) {
		die( json_encode( $reply ) );
	}

    $reload = true;

    $tag = get_tag( $tag_id );
    if ( $tag instanceof WP_Term) {

        $tdb_tag_templates = td_options::get( 'tdb_tag_templates' );

        if ( is_array( $tdb_tag_templates ) ) {
            foreach ( $tdb_tag_templates as $tdb_tag_template_id => $tags ) {

                $arr_tags = explode( ',', $tags );

                if ( !empty($arr_tags) && is_array($arr_tags) ) {

                    $arr_tags = array_map( function($val) { return trim($val); }, $arr_tags );

                    if ( in_array( $tag->slug, $arr_tags ) ) {
                        $reload = false;
	                    break;
                    }
                }
            }
        }
    }

    if ( $reload ) {
	    $reply[ 'reload' ] = true;
    }

    wp_die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_get_tag_mobile_templates', 'tdb_get_tag_mobile_templates');
add_action('wp_ajax_tdb_get_tag_mobile_templates', 'tdb_get_tag_mobile_templates');
function tdb_get_tag_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'tag',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=tag')
            );
        }
    }

    die( json_encode( $reply ) );
}

add_action('wp_ajax_nopriv_tdb_delete_template', 'tdb_delete_template');
add_action('wp_ajax_tdb_delete_template', 'tdb_delete_template');
function tdb_delete_template() {

    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( !wp_verify_nonce( $nonce, 'wp_rest' ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'failed to verify nonce !';
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    if ( empty( $template_id ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'template id is missing and is required !';
        die( json_encode( $reply ) );
    }

    // update post
    $post_id = wp_trash_post($template_id);

    // treat errors
    if ( is_wp_error( $post_id ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = $post_id->get_error_messages();
	    $reply['error'] = $post_id->get_error_messages();
        die( json_encode( $reply ) );
    }

    $reply['template_id'] = $template_id;

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_change_template_name', 'tdb_change_template_name');
add_action('wp_ajax_tdb_change_template_name', 'tdb_change_template_name');
function tdb_change_template_name() {

    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'failed to verify nonce !';
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    $template_title = $_POST['template_title'];

    if ( empty ( $template_id ) || empty( $template_title ) ) {
	    $reply['type'] = 'error';

        if ( empty( $template_id ) ) {
	        $reply['msg'] = 'template id is missing and is required !';
        }

        if ( empty( $template_title ) ) {
	        $reply['msg'] = 'template title is missing and is required !';
        }

        die( json_encode( $reply ) );
    }

    // update post
    $post_id = wp_update_post(
        array(
            'ID' => $template_id,
            'post_title' => $template_title,
        )
    );

    // treat errors
    if ( is_wp_error( $post_id ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = $post_id->get_error_messages();
        die( json_encode( $reply ) );
    }

    $reply['template_id'] = $template_id;
    $reply['template_title'] = $template_title;

    die( json_encode( $reply ) );
}




add_action('wp_ajax_nopriv_tdb_work_cloud', 'tdb_work_cloud');
add_action('wp_ajax_tdb_work_cloud', 'tdb_work_cloud');
function tdb_work_cloud(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $option = $_POST['option'];

    if ( empty($option)) {
        die( json_encode( $reply ) );
    }

    if ('get' === $option) {
	    $result['checked'] = get_option('tdb_work_cloud');
    } else if ( 'set' === $option && isset( $_POST['value'] ) ) {
	    $result = update_option('tdb_work_cloud', $_POST['value']);
    } else if ( 'ip' === $option) {
        if( isset( $_SERVER['SERVER_ADDR'] ) ){
			$ip = $_SERVER['SERVER_ADDR'];
		} else if (isset($_SERVER['SERVER_NAME'])) {
			$ip = gethostbyname($_SERVER['SERVER_NAME']);
		}
		if (!empty($ip)) {
			$result = array( 'ip' => trim( $ip ) );
		}
    }
    $reply[] = $result;

    wp_die( json_encode( $reply ) );
}



add_action('wp_ajax_nopriv_tdb_tagdiv_ip', 'tdb_tagdiv_ip');
add_action('wp_ajax_tdb_tagdiv_ip', 'tdb_tagdiv_ip');
function tdb_tagdiv_ip (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $ip = $_POST['ip'];
    $option = $_POST['option'];

    if ( empty($ip) || empty($option)) {
        die( json_encode( $reply ) );
    }

    global $wpdb;

    if ('add' === $option) {
	    $result = $wpdb->query(
		    $wpdb->prepare( "INSERT INTO td_work_cloud.ip_tagdiv(IP) VALUES (%s)", $ip )
	    );
    } else if ('remove' === $option) {
	    $result = $wpdb->query(
		    $wpdb->prepare( "DELETE FROM td_work_cloud.ip_tagdiv WHERE IP = '%s'", $ip )
	    );
    }
    $reply[] = $result;

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_create_mobile_template', 'tdb_create_mobile_template');
add_action('wp_ajax_tdb_create_mobile_template', 'tdb_create_mobile_template');
function tdb_create_mobile_template() {

    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'failed to verify nonce !';
        die( json_encode( $reply ) );
    }

    $template_id = $_POST['template_id'];
    $template_type = $_POST['template_type'];
    $template_title = $_POST['template_title'];

    if ( empty($template_id) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'template id is missing and is required !';
        die( json_encode( $reply ) );
    }

    if ( empty($template_type) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'template type is missing and is required !';
        die( json_encode( $reply ) );
    }

    if ( empty($template_title)) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'template title is missing and is required !';
        die( json_encode( $reply ) );
    }

    $template_types = array(
        'single', 'category', 'author', 'search', 'date', 'tag', 'attachment', '404', 'page', 'header', 'footer', 'woo_product', 'woo_archive', 'woo_search_archive', 'woo_shop_base', 'cpt', 'cpt_tax',
    );

    $copy_content = $_POST['copyContent'];

    if ( in_array( $template_type, $template_types ) === false ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'Invalid template type!';
        die( json_encode( $reply ) );
    }

    $post_type = 'page' === $template_type ? 'page' : 'tdb_templates';

    if ('1' === $copy_content ) {
        $template = get_post($template_id);
        $post_content = $template->post_content;
        $post_content = tdc_util::parse_content_for_mobile( $post_content );
    } else {
        if ( 'header' === $template_type ) {
			// blank header template
			$post_content = base64_encode( '{"tdc_header_desktop":"[tdc_zone type=\"tdc_header_desktop\" tdc_css=\"eyJhbGwiOnsiZGlzcGxheSI6IiJ9fQ==\" h_display=\"\" h_position=\"\" zone_shadow_shadow_offset_horizontal=\"0\"][vc_row][vc_column][/vc_column][/vc_row][/tdc_zone]","tdc_header_desktop_sticky":"[tdc_zone type=\"tdc_header_desktop_sticky\" s_transition_type=\"\" tdc_css=\"eyJhbGwiOnsiZGlzcGxheSI6IiJ9fQ==\" hs_transition_type=\"\" hs_transition_effect=\"slide\" hs_sticky_type=\"\"][vc_row][vc_column][/vc_column][/vc_row][/tdc_zone]","tdc_header_mobile":"[tdc_zone type=\"tdc_header_mobile\" tdc_css=\"eyJwaG9uZSI6eyJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ==\"][vc_row][vc_column][/vc_column][/vc_row][/tdc_zone]","tdc_header_mobile_sticky":"[tdc_zone type=\"tdc_header_mobile_sticky\" tdc_css=\"eyJwaG9uZSI6eyJkaXNwbGF5IjoiIn0sInBob25lX21heF93aWR0aCI6NzY3fQ==\" ms_transition_effect=\"eyJhbGwiOiJvcGFjaXR5IiwicGhvbmUiOiJzbGlkZSJ9\" ms_sticky_type=\"\"][vc_row][vc_column][/vc_column][/vc_row][/tdc_zone]","tdc_is_header_sticky":false,"tdc_is_mobile_header_sticky":false}' );
		} elseif ( 'footer' === $template_type ) {
			// footer content
			$post_content = '[tdc_zone type="tdc_footer"][vc_row][vc_column][/vc_column][/vc_row][/tdc_zone]';
		} else {
			// blank content
			$post_content = '[tdc_zone type="tdc_content"][vc_row][vc_column][/vc_column][/vc_row][/tdc_zone]';
		}
    }

    // update post
    $post_id = wp_insert_post( array(
        'post_title' => $template_title,
        'post_type' => $post_type,
        'post_status' => 'publish',
        'post_content' => $post_content
    ) );

    // treat errors
    if ( is_wp_error( $post_id ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'WP create mobile tpl error: ' . $post_id->get_error_message();
        die( json_encode( $reply ) );
    }

    if ( 0 !== $post_id ) {

        // reply data
	    $reply['template_type'] = $template_type;
	    $reply['template_id']   = $template_id;

	    // update meta
	    update_post_meta( $template_id, 'tdc_mobile_template_id', $post_id );
	    update_post_meta( $post_id, 'tdb_template_type', $template_type );
	    update_post_meta( $post_id, 'tdc_is_mobile_template', 1 );

	    if ( 'header' === $template_type ) {
			update_post_meta( $post_id, 'tdc_header_template_id', $post_id );
		} else if ( 'footer' === $template_type ) {
	        update_post_meta( $post_id, 'tdc_footer_template_id', $post_id );
	    }

	    $reply['mobile_template_id']    = $post_id;
	    $reply['mobile_template_title'] = $template_title;
	    $reply['mobile_template_url']   = admin_url( 'post.php?post_id=' . $post_id . '&td_action=tdc&tdbTemplateType=' . $template_type );

    } else {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'Invalid new mobile tpl post ID error.';
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_page_mobile_templates', 'tdb_get_page_mobile_templates');
add_action('wp_ajax_tdb_get_page_mobile_templates', 'tdb_get_page_mobile_templates');
function tdb_get_page_mobile_templates(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('page'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $page_id = $_POST['page_id'];
    $mobile_template_id = null;
    if ( !empty($page_id)) {
        $mobile_template_id = get_post_meta($page_id, 'tdc_mobile_template_id', true );
    }

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $data = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=page')
            );

            if (!is_null($mobile_template_id) && intval( $mobile_template_id ) === $post->ID) {
                $data['is_current'] = 1;
            }

            $reply[] = $data;
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_header_mobile_templates', 'get_header_mobile_templates');
add_action('wp_ajax_tdb_get_header_mobile_templates', 'get_header_mobile_templates');
function get_header_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'header',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=header')
            );
        }
    }

    die( json_encode( $reply ) );
}

// ajax:set global header template
add_action('wp_ajax_nopriv_tdb_assign_header_template_global', 'tdb_assign_header_template_global');
add_action('wp_ajax_tdb_assign_header_template_global', 'tdb_assign_header_template_global');
function tdb_assign_header_template_global(){
	$reply = array();

	$nonce = $_POST['_nonce'];
	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		die( json_encode( $reply ) );
	}

	$template_id = $_POST['template_id'];
	if ( empty( $template_id ) ) {
		die( json_encode( $reply ) );
	}

	$lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_header_template' . $lang;

	td_util::update_option( $option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_header_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_header_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_header_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

	wp_die( json_encode( $reply ) );
}

// ajax:set global footer template
add_action('wp_ajax_nopriv_tdb_assign_footer_template_global', 'tdb_assign_footer_template_global');
add_action('wp_ajax_tdb_assign_footer_template_global', 'tdb_assign_footer_template_global');
function tdb_assign_footer_template_global(){
	$reply = array();

	$nonce = $_POST['_nonce'];
	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		die( json_encode( $reply ) );
	}

	$template_id = $_POST['template_id'];
	if ( empty( $template_id ) ) {
		die( json_encode( $reply ) );
	}

	$lang = '';
    if ( class_exists('SitePress', false ) ) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset( $sitepress_settings['custom_posts_sync_option'][ 'tdb_templates'] ) ) {
            $translation_mode = (int) $sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if ( 1 === $translation_mode ) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_footer_template' . $lang;

	td_util::update_option( $option_id, 'tdb_template_' . $template_id );

	// read back the global setting
	$tdb_footer_global_template_id = td_util::get_option( $option_id );

	if ( td_global::is_tdb_template( $tdb_footer_global_template_id, true ) ) {
		$tdb_template_id = td_global::tdb_get_template_id( $tdb_footer_global_template_id );

		if ( intval($template_id) === $tdb_template_id ) {
			$reply['global_template_id'] = $template_id;
		}
	}

	wp_die( json_encode( $reply ) );
}

// ajax:assign global homepage page template
add_action('wp_ajax_nopriv_tdb_assign_homepage_template_global', 'tdb_assign_homepage_template_global');
add_action('wp_ajax_tdb_assign_homepage_template_global', 'tdb_assign_homepage_template_global');
function tdb_assign_homepage_template_global(){
	$reply = array();

	$nonce = $_POST['_nonce'];
	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		die( json_encode( $reply ) );
	}

	$homepage_id = $_POST['template_id'];
	if ( empty( $homepage_id ) ) {
		die( json_encode( $reply ) );
	}

    // update homepage settings
	update_option('show_on_front', 'page' );
	update_option('page_on_front', $homepage_id );

	// read back the global settings
	$page_on_front = get_option( 'page_on_front' );
	$show_on_front = get_option( 'show_on_front' );

	if ( $show_on_front === 'page' && (int) $page_on_front === (int) $homepage_id ) {
		$reply['global_template_id'] = $homepage_id;
	}

	wp_die( json_encode( $reply ) );

}


/*
 * Website Manager > Global Colors > ajax callbacks
 */

// updates global colors
add_action( 'wp_ajax_nopriv_tdc_wm_global_colors_update', 'on_ajax_tdc_wm_global_colors_update' );
add_action( 'wp_ajax_tdc_wm_global_colors_update', 'on_ajax_tdc_wm_global_colors_update' );
function on_ajax_tdc_wm_global_colors_update() {

	$reply = array();

	// die if request is fake
	check_ajax_referer( 'tdc-wm-global-colors', 'td_magic_token' );

	// get global colors option
	$tdc_wm_global_colors = td_util::get_option(
		'tdc_wm_global_colors',
		array(
			'accent_color' => array(
				'name' => 'Theme Color',
				'color' => '#fff',
				'default' => true
			)
		)
	);

	// check post data
	$color = $_POST['color'] ?? '';
	$option_id = $_POST['option'] ?? '';

	if (!empty($color) && !empty($option_id)) {
        // set option with new color
        $tdc_wm_global_colors[$option_id]['color'] = $color;

        // update option
	    td_util::update_option('tdc_wm_global_colors', $tdc_wm_global_colors);

	    $reply['option'] = $option_id;
	    $reply['color'] = $color;
    }

	$reply['tdc_wm_global_colors'] = $tdc_wm_global_colors;

	die( json_encode( $reply ) );
}

// updates global color name
add_action( 'wp_ajax_nopriv_tdc_wm_global_colors_color_name_update', 'on_ajax_tdc_wm_global_colors_color_name_update' );
add_action( 'wp_ajax_tdc_wm_global_colors_color_name_update', 'on_ajax_tdc_wm_global_colors_color_name_update' );
function on_ajax_tdc_wm_global_colors_color_name_update() {

	$reply = array();

	// die if request is fake
	check_ajax_referer( 'tdc-wm-global-colors', 'td_magic_token' );

	// check post data
	$option_id = $_POST['option'];
	$color_name = $_POST['color_name'];

	// get global colors option
	$tdc_wm_global_colors = td_util::get_option(
		'tdc_wm_global_colors',
		array(
            'accent_color' => array(
                'name' => 'Theme Color',
                'color' => '#fff',
                'default' => true
            )
		)
	);

    // change name
	$tdc_wm_global_colors[$option_id]['name'] = $color_name;

	// set new op id
	$new_option_id = sanitize_title($color_name);

    // change key
	$tdc_wm_global_colors = tdb_util::change_key( $tdc_wm_global_colors, $option_id, $new_option_id );

	// update option
	td_util::update_option('tdc_wm_global_colors', $tdc_wm_global_colors);

	$reply['option'] = $option_id;
	$reply['new_option'] = $new_option_id;
	$reply['color_name'] = $color_name;
	$reply['color'] = $tdc_wm_global_colors[$new_option_id]['color'];
	$reply['tdc_wm_global_colors'] = $tdc_wm_global_colors;

	die( json_encode( $reply ) );
}

// add new global color
add_action('wp_ajax_nopriv_tdc_wm_global_colors_add_new', 'on_ajax_tdc_wm_global_colors_add_new');
add_action('wp_ajax_tdc_wm_global_colors_add_new', 'on_ajax_tdc_wm_global_colors_add_new');
function on_ajax_tdc_wm_global_colors_add_new() {

	$reply = array();

	// die if request is fake
	check_ajax_referer( 'tdc-wm-global-colors', 'td_magic_token' );

	// check post data
	$color = $_POST['color'];
	$color_name = $_POST['color_name'];

	// get global colors option
	$tdc_wm_global_colors = td_util::get_option(
		'tdc_wm_global_colors',
		array(
            'accent_color' => array(
                'name' => 'Theme Color',
                'color' => '#fff',
                'default' => true
            )
		)
	);

	// sanitize option id
	$option_id = sanitize_title( $color_name );

    // check if option already exists and add a duplication number
	if ( isset( $tdc_wm_global_colors[$option_id] ) ) {
		for ( $i = 2 ; $i < 10; $i++ ) {
			if ( !isset( $tdc_wm_global_colors[$option_id . $i] ) ) {
                $option_id = $option_id . $i;
				$color_name = $color_name . ' (' . $i . ')';
                break;
            }
		}
	}

	// add new option with new color
	$tdc_wm_global_colors[$option_id] = array(
		'name' => $color_name,
		'color' => $color,
		'default' => false
	);

	// update option
	td_util::update_option( 'tdc_wm_global_colors', $tdc_wm_global_colors );

	$reply['color']  = $color;
	$reply['color_name'] = $color_name;
	$reply['color_option_id'] = $option_id;
	$reply['tdc_wm_global_colors'] = $tdc_wm_global_colors;

	die( json_encode( $reply ) );
}

// delete global color
add_action( 'wp_ajax_nopriv_tdc_wm_global_colors_delete', 'on_ajax_tdc_wm_global_colors_delete' );
add_action( 'wp_ajax_tdc_wm_global_colors_delete', 'on_ajax_tdc_wm_global_colors_delete' );
function on_ajax_tdc_wm_global_colors_delete() {

	$reply = array();

	// die if request is fake
	check_ajax_referer( 'tdc-wm-global-colors', 'td_magic_token' );

	// check post data
	$option = $_POST['option'];

	// get global colors option
	$tdc_wm_global_colors = td_util::get_option('tdc_wm_global_colors' );

	// remove option id
	unset( $tdc_wm_global_colors[$option] );

	// update
	td_util::update_option( 'tdc_wm_global_colors', $tdc_wm_global_colors );

	$reply['option'] = $option;
	$reply['tdc_wm_global_colors'] = $tdc_wm_global_colors;

	die( json_encode( $reply ) );
}

// global colors reset
add_action( 'wp_ajax_nopriv_tdc_wm_global_colors_reset', 'on_ajax_tdc_wm_global_colors_reset' );
add_action( 'wp_ajax_tdc_wm_global_colors_reset', 'on_ajax_tdc_wm_global_colors_reset' );
function on_ajax_tdc_wm_global_colors_reset() {

	$reply = array();

	// die if request is fake
	check_ajax_referer( 'tdc-wm-global-colors', 'td_magic_token' );

	// reset
	td_util::update_option('tdc_wm_global_colors',
		array(
            'accent_color' => array(
                'name' => 'Theme Color',
                'color' => '#fff',
                'default' => true
            )
		)
	);

	$reply['tdc_wm_global_colors'] = td_util::get_option('tdc_wm_global_colors' );

	die( json_encode( $reply ) );
}

/*
 * Website Manager > Global Fonts > ajax callbacks
 */

// add new global font
add_action('wp_ajax_nopriv_tdc_wm_global_fonts_add_edit', 'on_ajax_tdc_wm_global_fonts_add_edit');
add_action('wp_ajax_tdc_wm_global_fonts_add_edit', 'on_ajax_tdc_wm_global_fonts_add_edit');
function on_ajax_tdc_wm_global_fonts_add_edit() {

	$reply = array();

	// die if request is fake
	check_ajax_referer( 'tdc-wm-global-fonts', 'td_magic_token' );

	// check post data
	$font_key = $_POST['font_key'];
	$font_name = $_POST['font_name'];
	$font_option_id = $_POST['font_option_id'];

	// get global fonts option
	$tdc_wm_global_fonts = td_util::get_option('tdc_wm_global_fonts' );

    if ( empty( $tdc_wm_global_fonts ) ) {
	    $tdc_wm_global_fonts = array();
    }

    // update
    if ( !empty( $font_option_id ) ) {

	    $option_id = $font_option_id;

	    // check if option exists
	    if ( isset( $tdc_wm_global_fonts[$option_id] ) ) {
		    // update font option with new data
		    $tdc_wm_global_fonts[$option_id] = array(
			    'name' => $font_name,
			    'key' => $font_key
		    );
	    } else {
            // the edited global font not found @todo throw error||notice
        }

    // add new
    } else {

	    // sanitize option id
	    $option_id = sanitize_title( $font_name );

	    // check if option already exists and add a duplication number
	    if ( isset( $tdc_wm_global_fonts[$option_id] ) ) {
		    for ( $i = 2 ; $i < 10; $i++ ) {
			    if ( !isset( $tdc_wm_global_fonts[$option_id . $i] ) ) {
				    $option_id = $option_id . $i;
				    $font_name = $font_name . ' (' . $i . ')';
				    break;
			    }
		    }
	    }

	    // add new option with new font
	    $tdc_wm_global_fonts[$option_id] = array(
		    'name' => $font_name,
		    'key' => $font_key
	    );

    }

	// update option
	td_util::update_option('tdc_wm_global_fonts', $tdc_wm_global_fonts );

	$reply['font_key']  = $font_key;
	$reply['font_name'] = $font_name;
	$reply['font_option_id'] = $option_id;
	$reply['tdc_wm_global_fonts'] = $tdc_wm_global_fonts;

	die( json_encode( $reply ) );

}

// delete global font
add_action( 'wp_ajax_nopriv_tdc_wm_global_fonts_delete', 'on_ajax_tdc_wm_global_fonts_delete' );
add_action( 'wp_ajax_tdc_wm_global_fonts_delete', 'on_ajax_tdc_wm_global_fonts_delete' );
function on_ajax_tdc_wm_global_fonts_delete() {

	$reply = array();

	// die if request is fake
	check_ajax_referer( 'tdc-wm-global-fonts', 'td_magic_token' );

	// check post data
	$option = $_POST['option'];

	// get global fonts option
	$tdc_wm_global_fonts = td_util::get_option('tdc_wm_global_fonts' );

    // if global fonts option array is empty or the font option key is not set return error msg
    if ( empty( $tdc_wm_global_fonts ) ) {
	    $tdc_wm_global_fonts = array();
    }

    // set font name
	$font_name = $tdc_wm_global_fonts[$option]['name'];

	// remove option id
	unset( $tdc_wm_global_fonts[$option] );

	// update
	td_util::update_option( 'tdc_wm_global_fonts', $tdc_wm_global_fonts );

	$reply['option'] = $option;
	$reply['font_name'] = $font_name;
	$reply['tdc_wm_global_fonts'] = $tdc_wm_global_fonts;

	die( json_encode( $reply ) );
}

/*
 * wp admin > Cloud Templates Manager > ajax callbacks
 */

// get all cloud templates
add_action( 'wp_ajax_nopriv_tdb_ct_get_all', 'on_ajax_tdb_ct_get_all' );
add_action( 'wp_ajax_tdb_ct_get_all', 'on_ajax_tdb_ct_get_all' );
function on_ajax_tdb_ct_get_all() {

	$reply = array();

	// die if request is fake
    if ( !wp_verify_nonce( $_POST['_nonce'], 'wp_rest' ) ) {
	    $reply['type'] = 'error';
	    $reply['msg'] = 'failed to verify nonce!';
        die( json_encode( $reply ) );
    }

    // all cloud template types
    $tdb_template_types = array(
	    'header',
	    'footer',

	    'homepage',
	    'page',

	    '404',
	    'single',
	    'category',
	    'author',
	    'attachment',
	    'date',
	    'search',
	    'tag'
    );

	// additional tpl types
	$tdb_template_types = apply_filters( 'tdb_template_types', $tdb_template_types );

	// woo attributes tpl types
	$td_woo_attributes_template_types = apply_filters( 'td_woo_attributes_template_types', array() );
    if ( !empty( $td_woo_attributes_template_types ) ) {
	    $tdb_template_types = array_merge( $tdb_template_types, $td_woo_attributes_template_types );
    }

	// cloud templates array init
	$tdb_templates = array();

    // build cloud templates array
	foreach ( $tdb_template_types as $template_type ) {

        // set tpl type data key
		$template_type_data_key = $template_type;
		if ( $template_type === '404' ) {
			$template_type_data_key = 'a_404';
        }

		// sort by tpl type
		$tdb_templates[$template_type_data_key] = array();

		// tpl type global option init
		$tdb_templates[$template_type_data_key]['global_tpl'] = '';

		// tpl type templates array init
		$tdb_templates[$template_type_data_key]['templates'] = array();

		// init global option id
		$tpl_type_global_option_id = '';

		$lang = '';
        if ( class_exists('SitePress', false ) ) {
            global $sitepress;
            $sitepress_settings = $sitepress->get_settings();
            if ( isset( $sitepress_settings['custom_posts_sync_option'][ 'tdb_templates'] ) ) {
                $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                if ( 1 === $translation_mode ) {
                    $lang = $sitepress->get_current_language();
                }
            }
        }

		// set global option id
		switch ( $template_type ) {

			// default templates
			case 'single':
				$tpl_type_global_option_id = 'td_default_site_post_template' . $lang;
				break;
			case 'header':
			case 'footer':
			case '404':
			case 'attachment':
			case 'author':
			case 'category':
			case 'date':
			case 'search':
			case 'tag':
			// woocommerce(shop) templates
			case 'woo_product':
			case 'woo_archive':
			case 'woo_search_archive':
			case 'woo_shop_base':
			// woocommerce(shop) templates that use the 'woo_archive' cloud tpl type
			case 'woo_archive_tag':
			case 'woo_archive_attribute':
			    $tpl_type_global_option_id = 'tdb_' . $template_type . '_template' . $lang;
				break;
			case ( strpos( $template_type, 'pa_' ) !== false ):
				$tpl_type_global_option_id = 'tdb_woo_attribute_' . $template_type . '_tax_template';
				break;

		}

		// read the global tpl type option
		$tpl_type_global_option = td_util::get_option( $tpl_type_global_option_id );
		if ( td_global::is_tdb_template( $tpl_type_global_option, true ) ) {
			// set tpl type global template option
			$tdb_templates[$template_type_data_key]['global_tpl'] = td_global::tdb_get_template_id( $tpl_type_global_option );
		}

        // for homepage tpl type read the wp homepage option
        if ( $template_type === 'homepage' ) {
            $show_on_front = get_option( 'show_on_front' );
            $page = get_option( 'page_on_front' );

            if ( 'page' === $show_on_front ) {
	            $tdb_templates['homepage']['global_tpl'] = (int) $page;
            }

        }

		// tpl type mob templates array init
		$tdb_templates[$template_type_data_key]['mobile_templates'] = array();

		// get tpl type mobile templates
        if ( $template_type === 'homepage' || $template_type === 'page' ) {

	        // get page templates type mobile templates
	        $wp_query_page_tpl_type_mob_templates = new WP_Query(
		        array(
			        'post_type' => array( 'page' ),
			        'post_status' => 'publish',
			        'posts_per_page' => '-1',
			        'meta_key' => 'tdc_is_mobile_template',
			        'meta_value' => 1,
		        )
	        );
	        if ( !empty( $wp_query_page_tpl_type_mob_templates->posts ) ) {

		        foreach ( $wp_query_page_tpl_type_mob_templates->posts as $mob_template ) {
			        $tdb_templates[$template_type]['mobile_templates'][] = array(
				        'tpl_id' => $mob_template->ID,
				        'tpl_title' => $mob_template->post_title,
				        'tpl_tdc_url' => admin_url( 'post.php?post_id=' . $mob_template->ID . '&td_action=tdc&tdbTemplateType=' . $mob_template->tdb_template_type )
			        );
		        }

	        }

        } else {

	        // get cloud templates type mobile templates
	        $wp_query_tdb_tpl_type_mob_templates = new WP_Query(
		        array(
			        'post_type' => array( 'tdb_templates' ),
			        'post_status' => 'publish',
			        'meta_query' => array(
				        array(
					        'key'     => 'tdb_template_type',
					        'value'   => $template_type,
				        ),
				        array(
					        'key'     => 'tdc_is_mobile_template',
					        'value'   => 1,
				        )
			        ),
			        'posts_per_page' => '-1'
		        )
	        );
	        if ( !empty( $wp_query_tdb_tpl_type_mob_templates->posts ) ) {

		        foreach ( $wp_query_tdb_tpl_type_mob_templates->posts as $mob_template ) {
			        $tdb_templates[$template_type_data_key]['mobile_templates'][] = array(
				        'tpl_id' => $mob_template->ID,
				        'tpl_title' => $mob_template->post_title,
				        'tpl_tdc_url' => admin_url( 'post.php?post_id=' . $mob_template->ID . '&td_action=tdc&tdbTemplateType=' . $mob_template->tdb_template_type )
			        );
		        }

	        }

        }

		// get tpl card data assets
		$tdb_templates[$template_type_data_key]['tpl_card_data_assets'] = get_tpl_card_data_assets($template_type);

    }

    // query cloud templates
    $wp_query_templates = new WP_Query(
        array(
            'post_type' => array( 'tdb_templates' ),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'meta_key' => 'tdc_is_mobile_template',
            'meta_compare' => 'NOT EXISTS',
        )
    );

    if ( !empty( $wp_query_templates->posts ) ) {

        // add tp templates array by tpl type
        foreach ( $wp_query_templates->posts as $template ) {
	        $template_type = get_post_meta( $template->ID, 'tdb_template_type', true );
	        $template_type_data_key = $template_type;

            // modify tpl type data key for 404 templates, this is a hack for getting the right sort order in js
            if ( $template_type === '404' ) {
		        $template_type_data_key = 'a_404';
	        }

            if ( !in_array( $template_type, $tdb_template_types ) )
                continue;

            $tpl_data = (array) $template;

	        // mobile tpl init
	        $tpl_data['mobile_tpl_id'] = '';
	        $mobile_tpl = null;

	        // read mob tpl id meta
	        $mobile_tpl_id = get_post_meta( $template->ID, 'tdc_mobile_template_id', true );

	        if ( !empty( $mobile_tpl_id ) ) {
		        $mobile_tpl = get_post( $mobile_tpl_id );
		        if ( $mobile_tpl instanceof WP_Post && 'publish' === get_post_status( $mobile_tpl_id ) ) {
			        // set mobile tpl
			        $tpl_data['mobile_tpl_id'] = (int) $mobile_tpl_id;
		        }
	        }

            // tpl view link
	        $card_tpl_view_link = !empty( $tdb_templates[$template_type_data_key]['tpl_card_data_assets']['card_tpl_data_view_link'] ) ? $tdb_templates[$template_type_data_key]['tpl_card_data_assets']['card_tpl_data_view_link'] : '';
	        $tpl_data['view_link'] = $card_tpl_view_link ?: get_permalink( $template->ID );

            // tpl edit link
	        $tpl_data['edit_link'] = get_edit_post_link( $template->ID, 'raw' );

            // add tpl data to current tpl type templates
	        $tdb_templates[$template_type_data_key]['templates'][] = $tpl_data;

            // woo archive type cloud templates
            if ( $template_type_data_key === 'woo_archive' ) {

	            // add the woo_archive cloud templates type data to woocommerce(shop) attributes template types
                if ( !empty( $td_woo_attributes_template_types ) ) {
                    foreach ( $td_woo_attributes_template_types as $td_woo_attributes_template_type ) {
	                    $tdb_templates[$td_woo_attributes_template_type]['templates'][] = $tpl_data;
                    }
                }

	            // add the woo_archive cloud templates type data to woocommerce(shop) templates that use the 'woo_archive' cloud tpl type
	            $tdb_templates['woo_archive_tag']['templates'][] = $tpl_data;
	            $tdb_templates['woo_archive_attribute']['templates'][] = $tpl_data;

            }

        }

    }

    // query page templates
	$wp_query_pages = new WP_Query(
		array(
			'post_type' => array( 'page' ),
			'post_status' => 'publish',
			'posts_per_page' => '-1',
            'meta_query' => array(
                array(
                    'key' => 'tdc_page_cloud_import',
                    'value' => 1,
                ),
                array(
                    'key' => 'tdc_is_mobile_template',
                    'compare' => 'NOT EXISTS'
                )
            )
		)
	);

    if ( !empty( $wp_query_pages->posts ) ) {

	    foreach ( $wp_query_pages->posts as $page_template ) {
		    $tpl_data = (array) $page_template;

		    // mobile tpl init
		    $tpl_data['mobile_tpl_id'] = '';
		    $mobile_tpl = null;

		    // read mob tpl id meta
		    $mobile_tpl_id = get_post_meta( $page_template->ID, 'tdc_mobile_template_id', true );

		    if ( !empty( $mobile_tpl_id ) ) {
			    $mobile_tpl = get_post( $mobile_tpl_id );
			    if ( $mobile_tpl instanceof WP_Post && 'publish' === get_post_status( $mobile_tpl_id ) ) {
				    // set mobile tpl
				    $tpl_data['mobile_tpl_id'] = (int) $mobile_tpl_id;
			    }
		    }

		    // tpl view link
		    $tpl_data['view_link'] = get_permalink( $page_template->ID );

		    // tpl edit link
		    $tpl_data['edit_link'] = get_edit_post_link( $page_template->ID, 'raw' );

		    $tdb_templates['page']['templates'][] = $tpl_data;
        }

    }

    // query homepage templates
	$wp_query_homepages = new WP_Query(
		array(
			'post_type' => array( 'page' ),
			'post_status' => 'publish',
			'posts_per_page' => '-1',
			'meta_query' => array(
				array(
					'key' => 'tdc_homepage_cloud_import',
					'value' => 1,
				),
				array(
					'key' => 'tdc_is_mobile_template',
					'compare' => 'NOT EXISTS'
				)
			)
		)
	);

    if ( !empty( $wp_query_homepages->posts ) ) {

	    foreach ( $wp_query_homepages->posts as $homepage_template ) {
		    $tpl_data = (array) $homepage_template;

		    // mobile tpl init
		    $tpl_data['mobile_tpl_id'] = '';
		    $mobile_tpl = null;

		    // read mob tpl id meta
		    $mobile_tpl_id = get_post_meta( $homepage_template->ID, 'tdc_mobile_template_id', true );

		    if ( !empty( $mobile_tpl_id ) ) {
			    $mobile_tpl = get_post( $mobile_tpl_id );
			    if ( $mobile_tpl instanceof WP_Post && 'publish' === get_post_status( $mobile_tpl_id ) ) {
				    // set mobile tpl
				    $tpl_data['mobile_tpl_id'] = (int) $mobile_tpl_id;
			    }
		    }

		    // tpl view link
		    $tpl_data['view_link'] = get_permalink( $homepage_template->ID );

		    // tpl edit link
		    $tpl_data['edit_link'] = get_edit_post_link( $homepage_template->ID, 'raw' );

		    $tdb_templates['homepage']['templates'][] = $tpl_data;
        }

    }

    $reply['templates'] = $tdb_templates;

	die( json_encode( $reply ) );
}

// get template type card data
add_action( 'wp_ajax_nopriv_tdb_ct_get_tpl_card_data', 'on_ajax_tdb_ct_get_tpl_card_data' );
add_action( 'wp_ajax_tdb_ct_get_tpl_card_data', 'on_ajax_tdb_ct_get_tpl_card_data' );
function on_ajax_tdb_ct_get_tpl_card_data() {
	$reply = array();

	// die if request is fake
	if ( !wp_verify_nonce( $_POST['_nonce'], 'wp_rest' ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'failed to verify nonce !';
		die( json_encode( $reply ) );
	}

    // tpl type
	$tpl_type = $_POST['tpl_type'];
	if ( empty( $tpl_type ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'template type is missing and is required !';
		die( json_encode( $reply ) );
	}

    // card data id init
	$card_data = array(
      'templates' => array(),
      'mobile_templates' => array(),
      'global_tpl' => ''
    );

	// get tpl card data assets
	$card_data['tpl_card_data_assets'] = get_tpl_card_data_assets($tpl_type);

	// page & homepage tpl type
    if ( $tpl_type === 'page' || $tpl_type === 'homepage' ) {

        // set page tpl type meta key
        $meta_key = $tpl_type === 'page' ? 'tdc_page_cloud_import' : 'tdc_homepage_cloud_import';

	    // get pages
	    $wp_query_pages = new WP_Query(
		    array(
			    'post_type' => array( 'page' ),
			    'post_status' => 'publish',
			    'posts_per_page' => '-1',
			    //'meta_key' => $meta_key,
			    'meta_query' => array(
				    array(
					    'key' => $meta_key,
					    'value' => 1,
				    ),
				    array(
					    'key' => 'tdc_is_mobile_template',
					    'compare' => 'NOT EXISTS'
				    )
			    )
		    )
	    );
	    if ( !empty( $wp_query_pages->posts ) ) {

		    foreach ( $wp_query_pages->posts as $page_template ) {
			    $page_tpl_data = (array) $page_template;

			    // mobile tpl init
			    $page_tpl_data['mobile_tpl_id'] = '';
			    $mobile_tpl = null;

			    // read mob tpl id meta
                $mobile_tpl_id = get_post_meta( $page_template->ID, 'tdc_mobile_template_id', true );

                if ( !empty( $mobile_tpl_id ) ) {
                    $mobile_tpl = get_post( $mobile_tpl_id );
                    if ( $mobile_tpl instanceof WP_Post && 'publish' === get_post_status( $mobile_tpl_id ) ) {
                        // set mobile tpl
	                    $page_tpl_data['mobile_tpl_id'] = (int) $mobile_tpl_id;
                    }
                }

			    // tpl view link
			    $page_tpl_data['view_link'] = get_permalink( $page_template->ID );

			    // tpl edit link
			    $page_tpl_data['edit_link'] = get_edit_post_link( $page_template->ID, 'raw' );

			    $card_data['templates'][] = $page_tpl_data;
		    }

	    }

	    // get mobile pages
	    $wp_query_page_mob_templates = new WP_Query(
		    array(
			    'post_type' => array( 'page' ),
			    'post_status' => 'publish',
			    'posts_per_page' => '-1',
			    'meta_key' => 'tdc_is_mobile_template',
			    'meta_value' => 1,
            )
	    );
	    if ( !empty( $wp_query_page_mob_templates->posts ) ) {

		    foreach ( $wp_query_page_mob_templates->posts as $page_mob_template ) {
			    $card_data['mobile_templates'][] = array(
				    'tpl_id' => $page_mob_template->ID,
				    'tpl_title' => $page_mob_template->post_title,
				    'tpl_tdc_url' => admin_url( 'post.php?post_id=' . $page_mob_template->ID . '&td_action=tdc&tdbTemplateType=' . $page_mob_template->tdb_template_type )
			    );
		    }

	    }

    // woo attributes global & individual && woo tags tpl type
    } elseif ( strpos( $tpl_type, 'pa_' ) !== false || ( $tpl_type === 'woo_archive_tag' || $tpl_type === 'woo_archive_attribute' ) ) {

	    // get woo_archive templates
	    $wp_query_templates = new WP_Query(
		    array(
			    'post_type'   => array( 'tdb_templates' ),
			    'post_status' => 'publish',
			    'meta_query'  => array(
				    array(
					    'key'     => 'tdb_template_type',
					    'value'   => 'woo_archive',
				    ),
				    array(
					    'key'     => 'tdc_is_mobile_template',
					    'compare' => 'NOT EXISTS'
				    )
			    ),
			    'posts_per_page' => '-1'
		    )
	    );
	    if ( !empty( $wp_query_templates->posts ) ) {

		    foreach ( $wp_query_templates->posts as $template ) {
			    $tpl_data = (array) $template;

			    // tpl view link
			    $card_tpl_view_link = !empty( $card_data['tpl_card_data_assets']['card_tpl_data_view_link'] ) ? $card_data['tpl_card_data_assets']['card_tpl_data_view_link'] : '';
			    $tpl_data['view_link'] = $card_tpl_view_link ?: get_permalink( $template->ID );

			    // tpl edit link
			    $tpl_data['edit_link'] = get_edit_post_link( $template->ID, 'raw' );

			    $card_data['templates'][] = $tpl_data;
		    }

	    }
    // get tpl type templates
    } else {

	    // get templates
	    $wp_query_templates = new WP_Query(
		    array(
			    'post_type'   => array( 'tdb_templates' ),
			    'post_status' => 'publish',
			    'meta_query'  => array(
				    array(
					    'key'     => 'tdb_template_type',
					    'value'   => $tpl_type,
				    ),
				    array(
					    'key'     => 'tdc_is_mobile_template',
					    'compare' => 'NOT EXISTS'
				    )
			    ),
			    'posts_per_page' => '-1'
		    )
	    );
	    if ( !empty( $wp_query_templates->posts ) ) {

		    foreach ( $wp_query_templates->posts as $template ) {
			    $tpl_data = (array) $template;

			    // mobile tpl init
			    $tpl_data['mobile_tpl_id'] = '';
			    $mobile_tpl = null;

			    // read mob tpl id meta
			    $mobile_tpl_id = get_post_meta( $template->ID, 'tdc_mobile_template_id', true );

			    if ( !empty( $mobile_tpl_id ) ) {
				    $mobile_tpl = get_post( $mobile_tpl_id );
				    if ( $mobile_tpl instanceof WP_Post && 'publish' === get_post_status( $mobile_tpl_id ) ) {
					    // set mobile tpl
					    $tpl_data['mobile_tpl_id'] = (int) $mobile_tpl_id;
				    }
			    }

			    // tpl view link
			    $card_tpl_view_link = !empty( $card_data['tpl_card_data_assets']['card_tpl_data_view_link'] ) ? $card_data['tpl_card_data_assets']['card_tpl_data_view_link'] : '';
			    $tpl_data['view_link'] = $card_tpl_view_link ?: get_permalink( $template->ID );

			    // tpl edit link
			    $tpl_data['edit_link'] = get_edit_post_link( $template->ID, 'raw' );

			    $card_data['templates'][] = $tpl_data;
		    }

	    }

	    // get mobile templates
	    $wp_query_mob_templates = new WP_Query(
		    array(
			    'post_type' => array( 'tdb_templates' ),
			    'post_status' => 'publish',
			    'meta_query' => array(
				    array(
					    'key'     => 'tdb_template_type',
					    'value'   => $tpl_type,
				    ),
				    array(
					    'key'     => 'tdc_is_mobile_template',
					    'value'   => 1,
				    )
			    ),
			    'posts_per_page' => '-1'
		    )
	    );
	    if ( !empty( $wp_query_mob_templates->posts ) ) {

		    foreach ( $wp_query_mob_templates->posts as $mob_template ) {
			    $card_data['mobile_templates'][] = array(
				    'tpl_id' => $mob_template->ID,
				    'tpl_title' => $mob_template->post_title,
				    'tpl_tdc_url' => admin_url( 'post.php?post_id=' . $mob_template->ID . '&td_action=tdc&tdbTemplateType=' . $mob_template->tdb_template_type )
			    );
		    }

	    }

    }

	$lang = '';
    if ( class_exists('SitePress', false ) ) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset( $sitepress_settings['custom_posts_sync_option']['tdb_templates'] ) ) {
            $translation_mode = (int) $sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if ( 1 === $translation_mode ) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

	if ( 'cpt_tax' === $tpl_type ) {

		$card_data['global_tpls'] = [];

		$td_cpt_tax = td_util::get_option('td_cpt_tax');
		$option_id = 'tdb_category_template' . $lang;

		$ctaxes = td_util::get_ctaxes();

		foreach ( $ctaxes as $ctax ) {

			if ( !empty( $td_cpt_tax[$ctax->name][$option_id] ) ) {
				$default_template_id = $td_cpt_tax[$ctax->name][$option_id];

				if ( td_global::is_tdb_template( $default_template_id, true ) ) {
					$tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
					if ( !empty($tdb_template_id) ) {
						$card_data['global_tpls'][ $ctax->name] = $tdb_template_id;
					}
				}
			}
		}

	} else if ( 'cpt' === $tpl_type ) {

		$card_data['global_tpls'] = [];

		$td_cpt = td_util::get_option('td_cpt');
		$option_id = 'td_default_site_post_template' . $lang;

		$cpts = td_util::get_cpts();

		foreach ( $cpts as $cpt ) {
			if ( !empty( $td_cpt[$cpt->name][$option_id] ) ) {
				$default_template_id = $td_cpt[$cpt->name][$option_id];

				if ( td_global::is_tdb_template( $default_template_id, true ) ) {
					$tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
					if ( !empty($tdb_template_id) ) {
						$card_data['global_tpls'][ $cpt->name] = $tdb_template_id;
					}
				}
			}
		}

		//td_util::update_option('td_cpt', '');

	} else {

		// set the tpl type global option id
		switch ( $tpl_type ) {
			case 'single':
				$tpl_type_global_option_id = 'td_default_site_post_template' . $lang;
				break;
			case 'header':
			case 'footer':
			case '404':
			case 'category':
			case 'tag':
			case 'attachment':
			case 'author':
			case 'date':
			case 'search':
			case 'woo_product':
			case 'woo_search_archive':
			case 'woo_archive':
			case 'woo_shop_base':
			case 'woo_archive_attribute':
			case 'woo_archive_tag':
				$tpl_type_global_option_id = 'tdb_' . $tpl_type . '_template' . $lang;
				break;
			case ( strpos( $tpl_type, 'pa_' ) !== false ):
				$tpl_type_global_option_id = 'tdb_woo_attribute_' . $tpl_type . '_tax_template';
				break;
			default:
				$tpl_type_global_option_id = '';
				break;
		}

		// read the global tpl type option
		$tpl_type_global_option = td_util::get_option( $tpl_type_global_option_id );
		if ( !empty( $tpl_type_global_option ) && td_global::is_tdb_template( $tpl_type_global_option, true ) ) {
			// set tpl type global template option
			$card_data['global_tpl'] = td_global::tdb_get_template_id( $tpl_type_global_option );
		}

		// for homepage tpl type read the wp homepage option
		if ( $tpl_type === 'homepage' ) {
			$show_on_front = get_option( 'show_on_front' );
			$page = get_option( 'page_on_front' );

			if ( 'page' === $show_on_front ) {
				$card_data['global_tpl'] = (int) $page;
			}

		}

    }

	die( json_encode( $card_data ) );

}

// get template card data assets
function get_tpl_card_data_assets( $tpl_type, $wp_cpt_name = '' ) {

    // That's not an easy way to get the last (or any other) post in a specific language
    // On WPML forums they say to get all posts and to filter them. This is not reliable because
    // it can cause many problems on sites with a huge number of posts (ex.)
    if ( class_exists('SitePress', false ) ) {
         return array(
            'card_tpl_data_id' => '',
            'card_tpl_data_view_link' => ''
        );
    }

    // card data assets init
    $tpl_card_data_assets = array(
	    'card_tpl_data_view_link' => ''
    );
	$card_data_id = '';
	$card_data_view_link = '';
	$card_data_tax = [];

	switch ( $tpl_type ) {
		case '404':
		case 'header':
		case 'footer':
            break;
		case 'single':
			$posts = get_posts(
                array(
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                )
            );
            if ( $posts ) {
	            $card_data_id = $posts[0]->ID;
	            $card_data_view_link = get_permalink( $posts[0]->ID );
            }
			break;
		case 'category':
			$categories = get_categories(
                array(
	                //'hide_empty' => false,
                    'number' => 1
                )
            );

            if ( $categories ) {
                foreach ( $categories as $category ) {
	                $card_data_id = $category->cat_ID;
	                $card_data_view_link = get_category_link( $category->cat_ID );
                    break;
                }
            }

			break;
		case 'tag':
			$tags = get_tags(
                array(
                    'number' => 1
                )
            );
			if ( $tags ) {
				$card_data_id = $tags[0]->term_id;
				$card_data_view_link = get_tag_link( $tags[0]->term_id );
			}
			break;
		case 'attachment':
			$attachments = get_posts(
                array(
                    'post_type' => 'attachment',
                    'posts_per_page' => 1
                )
            );
			if ( $attachments ) {
				$card_data_id = $attachments[0]->ID;
				$card_data_view_link = get_permalink( $attachments[0]->ID );
			}
			break;
		case 'author':
            $authors = get_users(
                array(
                    'number' => 1,
                    'has_published_posts' => array( 'post' ),
                    'fields' => array( 'ID' ),
                    'orderby' => 'post_count',
                    'order' => 'DESC'
                )
            );
            if ( $authors ) {
	            $card_data_id = $authors[0]->ID;
	            $card_data_view_link = get_author_posts_url( $authors[0]->ID );
            }
            break;
		case 'date':
		    $card_data_id = date("Y");
			$card_data_view_link = get_year_link( $card_data_id );
		    break;
		case 'search':
            $card_data_id = 'a';
            $card_data_view_link = get_search_link('a');
		    break;
		case 'woo_product':
            $products = get_posts(
                array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'meta_key' => 'total_sales',
                    'orderby' => 'meta_value_num',
                    'order' => 'desc',
                    'posts_per_page' => 1,
                )
            );
            if ( $products ) {
                $card_data_id = $products[0]->ID;
	            $card_data_view_link = get_permalink( $products[0]->ID );
            }
            break;
		case 'woo_search_archive':
            $card_data_id = 'a';
            $card_data_view_link = home_url( '/?s=a&post_type=product' );
			break;
		case 'woo_archive':
            $product_categories = get_categories(
                array(
	                'taxonomy' => 'product_cat',
                    'number' => 1,
                )
            );
            if ( $product_categories ) {
                $card_data_id = $product_categories[0]->term_id;
	            $card_data_view_link = get_term_link( $product_categories[0]->term_id, 'product_cat' );
            }
		    break;
		case 'woo_shop_base':
		    $wc_shop_page_id = wc_get_page_id('shop');
            if ( $wc_shop_page_id > 0 ) {
	            $card_data_id = $wc_shop_page_id;
	            $card_data_view_link = get_permalink( $wc_shop_page_id );
            }
			break;

        // global tag woocommerce(shop) tpl that uses the 'woo_archive' cloud tpl type
        case 'woo_archive_tag':
	        $product_tags = get_categories(
		        array(
			        'taxonomy' => 'product_tag',
			        'number' => 1,
		        )
	        );
	        if ( $product_tags ) {
		        $card_data_id = $product_tags[0]->term_id;
	        }
            break;

		// global attributes woocommerce(shop) tpl that uses the 'woo_archive' cloud tpl type
		case 'woo_archive_attribute':
			$attributes = wc_get_attribute_taxonomies();
			if ( $attributes && is_array( $attributes ) ) {
				foreach ( $attributes as $att ) {
                    // set load data from one of the first retrieved attribute terms
                    $att_tax_name = wc_attribute_taxonomy_name( $att->attribute_name );
					$att_terms = get_terms( $att_tax_name );

					if ( $att_terms ) {
						$card_data_id = $att_terms[0]->term_id;
					}
					break;
				}
			}
			break;

		// product attributes templates, attribute data
		case ( strpos( $tpl_type, 'pa_' ) !== false ):
			$att_data = array();

			$attributes = wc_get_attribute_taxonomies();
			if ( $attributes && is_array( $attributes ) ) {
				foreach ( $attributes as $att ) {
					if ( $tpl_type === wc_attribute_taxonomy_name( $att->attribute_name ) ) {
						$att_data = (array) $att;
						$att_data['wc_attribute_taxonomy_name'] = $tpl_type;
						break;
					}
				}
			}

            // set card data id to a term from the current att tax
            if ( !empty( $att_data ) ) {
	            $att_terms = get_terms( $att_data['wc_attribute_taxonomy_name'] );

	            if ( $att_terms ) {
		            $card_data_id = $att_terms[0]->term_id;
		            $tpl_card_data_assets['card_tpl_data_view_link'] = get_term_link( $att_terms[0]->term_id, $att_data['wc_attribute_taxonomy_name'] );
                }
            }

			// set card att data
			$tpl_card_data_assets['att_data'] = $att_data;

			break;

        case 'cpt':

            if ( empty( $wp_cpt_name ) ) {
                $wp_cpt_name = 'post';
            }

            $taxonomies = get_object_taxonomies( $wp_cpt_name, 'objects' );

            if ( $taxonomies ) {

                foreach ( $taxonomies as $tax_name => $tax_obj ) {

	                $tax_terms = get_terms(
		                array(
			                'taxonomy' => $tax_name,
			                //'hide_empty' => false,
			                'number' => 1
		                )
	                );

	                if ( $tax_terms ) {

		                foreach ( $tax_terms as $term ) {
			                $card_data_id = $term->term_id;
			                $card_data_view_link = get_term_link( $term->term_id );
			                break;
		                }

	                }

                    break;
                }

	            $card_data_tax = $taxonomies;
            }

			break;
	}

	// set tpl card data id
	$tpl_card_data_assets['card_tpl_data_id'] = $card_data_id;
	$tpl_card_data_assets['card_tpl_data_tax'] = $card_data_tax;

	return $tpl_card_data_assets;

}

// get all trashed cloud templates
add_action( 'wp_ajax_nopriv_tdb_ct_get_all_trashed', 'on_ajax_tdb_ct_get_all_trashed' );
add_action( 'wp_ajax_tdb_ct_get_all_trashed', 'on_ajax_tdb_ct_get_all_trashed' );
function on_ajax_tdb_ct_get_all_trashed() {

	$reply = array();

	// die if request is fake
	if ( !wp_verify_nonce( $_POST['_nonce'], 'wp_rest' ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'failed to verify nonce!';
		die( json_encode( $reply ) );
	}

	// cloud templates array init
	$tdb_templates = array();

	// query all trashed cloud templates
	$wp_query_templates = new WP_Query(
		array(
			'post_type' => array( 'tdb_templates' ),
			'post_status' => 'trash',
			'posts_per_page' => '-1',
		)
	);

	if ( !empty( $wp_query_templates->posts ) ) {

		foreach ( $wp_query_templates->posts as $template ) {
			$tpl_data = (array) $template;

            // tpl type
            $tpl_data['tpl_type'] = get_post_meta( $template->ID, 'tdb_template_type', true );

			$tdb_templates[] = $tpl_data;
		}

	}

	// query all trashed && imported page/homepage templates
	$wp_query_pages = new WP_Query(
		array(
			'post_type' => array( 'page' ),
			'post_status' => 'trash',
			'posts_per_page' => '-1',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'tdc_homepage_cloud_import'
				),
				array(
					'key' => 'tdc_page_cloud_import'
				)
			)
		)
	);

	if ( !empty( $wp_query_pages->posts ) ) {

		foreach ( $wp_query_pages->posts as $page ) {
			$tpl_data = (array) $page;

			// set tpl type
			$tdc_homepage_cloud_import_meta = get_post_meta( $page->ID, 'tdc_homepage_cloud_import', true );
			if ( !empty( $tdc_homepage_cloud_import_meta ) ) {
				$tpl_data['tpl_type'] = 'homepage';
			} else {
				$tpl_data['tpl_type'] = 'page';
            }

			$tdb_templates[] = $tpl_data;
		}

	}

	$reply['templates'] = $tdb_templates;

	die( json_encode( $reply ) );
}

// restore(untrash) cloud template
add_action( 'wp_ajax_nopriv_tdb_restore_tpl', 'on_ajax_tdb_restore_tpl' );
add_action( 'wp_ajax_tdb_restore_tpl', 'on_ajax_tdb_restore_tpl' );
function on_ajax_tdb_restore_tpl() {

	$reply = array();

	$template_id = $_POST['template_id'];
	if ( empty( $template_id ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'template id is missing and is required !';
		die( json_encode( $reply ) );
	}

	// die if request is fake
	if ( !wp_verify_nonce( $_POST['_nonce'], 'wp_rest' ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'Failed to verify nonce.';
		die( json_encode( $reply ) );
	}

	$template = get_post( $template_id );
	if ( $template ) {
		$template_post_type = $template->post_type;
		$template_post_type_object = get_post_type_object( $template_post_type );
	}

	if ( !$template_post_type_object ) {
		$reply['type'] = 'warning';
		$reply['msg'] = 'Invalid post type.';
		die( json_encode( $reply ) );
	}

	if ( !current_user_can( 'delete_post', $template_id ) ) {
		$reply['type'] = 'notice';
		$reply['msg'] = 'Sorry, you are not allowed to restore this item from the Trash.';
		die( json_encode( $reply ) );
	}

	if ( !wp_untrash_post( $template_id ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'Error in restoring the item from Trash.';
		die( json_encode( $reply ) );
	}

	$reply['restored_tpl_id'] = $template_id;

	die( json_encode( $reply ) );
}

// permanently delete cloud template
add_action( 'wp_ajax_nopriv_tdb_perm_delete_tpl', 'on_ajax_tdb_perm_delete_tpl' );
add_action( 'wp_ajax_tdb_perm_delete_tpl', 'on_ajax_tdb_perm_delete_tpl' );
function on_ajax_tdb_perm_delete_tpl() {

	$reply = array();

	$template_id = $_POST['template_id'];
	if ( empty( $template_id ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'template id is missing and is required !';
		die( json_encode( $reply ) );
	}

	// die if request is fake
	if ( !wp_verify_nonce( $_POST['_nonce'], 'wp_rest' ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'Failed to verify nonce.';
		die( json_encode( $reply ) );
	}

	$template = get_post( $template_id );

	if ( !$template ) {
		$reply['type'] = 'warning';
		$reply['msg'] = 'This item has already been deleted.';
		die( json_encode( $reply ) );
	}

	$template_post_type = $template->post_type;
	$template_post_type_object = get_post_type_object( $template_post_type );

	if ( !$template_post_type_object ) {
		$reply['type'] = 'warning';
		$reply['msg'] = 'Invalid post type.';
		die( json_encode( $reply ) );
	}

	if ( !current_user_can( 'delete_post', $template_id ) ) {
		$reply['type'] = 'notice';
		$reply['msg'] = 'Sorry, you are not allowed to delete this item.';
		die( json_encode( $reply ) );
	}

	if ( !wp_delete_post( $template_id, true ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'Error in deleting the item.';
		die( json_encode( $reply ) );
	}

	$reply['deleted_tpl_id'] = $template_id;

	die( json_encode( $reply ) );
}

// delete all trashed cloud templates ( empty trash )
add_action( 'wp_ajax_nopriv_tdb_trashed_templates_delete_all', 'on_ajax_tdb_trashed_templates_delete_all' );
add_action( 'wp_ajax_tdb_trashed_templates_delete_all', 'on_ajax_tdb_trashed_templates_delete_all' );
function on_ajax_tdb_trashed_templates_delete_all() {

	$reply = array();

	// die if request is fake
	if ( !wp_verify_nonce( $_POST['_nonce'], 'wp_rest' ) ) {
		$reply['type'] = 'error';
		$reply['msg'] = 'Failed to verify nonce.';
		die( json_encode( $reply ) );
	}

	// query all trashed cloud templates
	$wp_query_templates = new WP_Query(
		array(
			'post_type' => array( 'tdb_templates' ),
			'post_status' => 'trash',
			'posts_per_page' => '-1',
		)
	);

	// query all trashed && imported page/homepage templates
	$wp_query_pages = new WP_Query(
		array(
			'post_type' => array( 'page' ),
			'post_status' => 'trash',
			'posts_per_page' => '-1',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'tdc_homepage_cloud_import'
				),
				array(
					'key' => 'tdc_page_cloud_import'
				)
			)
		)
	);


	// templates to delete
	$templates_to_delete = array();

	if ( !empty( $wp_query_templates->posts ) ) {
		$templates_to_delete = $wp_query_templates->posts;
	}

	if ( !empty( $wp_query_pages->posts ) ) {
		$templates_to_delete = array_merge(
			$templates_to_delete,
			$wp_query_pages->posts
        );
	}

	if ( !empty( $templates_to_delete ) ) {
		$deleted = 0;

		foreach ( $templates_to_delete as $template  ) {

			if ( !current_user_can( 'delete_post', $template->ID ) ) {
				$reply['type'] = 'notice';
				$reply['msg'] = 'Sorry, you are not allowed to delete this item.';
				die( json_encode( $reply ) );
			}

			if ( !wp_delete_post( $template->ID ) ) {
				$reply['type'] = 'error';
				$reply['msg'] = 'Error in deleting the ' . $template->post_title . ' template.';
				die( json_encode( $reply ) );
			}

			$deleted++;

		}

		$reply['deleted_templates'] = $deleted;
    }

	die( json_encode( $reply ) );
}


// updates global settings
add_action( 'wp_ajax_nopriv_tdc_wm_global_settings_update', 'tdc_wm_global_settings_update' );
add_action( 'wp_ajax_tdc_wm_global_settings_update', 'tdc_wm_global_settings_update' );
function tdc_wm_global_settings_update() {

	$reply = array();

	$nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }


	// check post data
	$settings = $_POST['settings'];
    if ( !empty( $settings ) && is_array( $settings ) ) {
        foreach ( $settings as $key => $val ) {
            td_util::update_option( $key, $val );
        }
    }

    $reply['success'] = true;

	die( json_encode( $reply ) );
}


// get all cpt cloud templates
add_action( 'wp_ajax_nopriv_tdb_cpt_get_all', 'on_ajax_tdb_cpt_get_all' );
add_action( 'wp_ajax_tdb_cpt_get_all', 'on_ajax_tdb_cpt_get_all' );
function on_ajax_tdb_cpt_get_all() {

	$reply = array();

	if ( ! wp_verify_nonce( $_POST[ '_nonce' ], 'wp_rest' ) ) {
		$reply[ 'type' ] = 'error';
		$reply[ 'msg' ]  = 'failed to verify nonce!';
		die( json_encode( $reply ) );
	}

	$lang = '';
    if ( class_exists( 'SitePress', false ) ) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if ( 1 === $translation_mode ) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    // get cloud templates type mobile templates
    $wp_query_tdb_tpl_type_mob_templates = new WP_Query(
        array(
            'post_type' => array( 'tdb_templates' ),
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key'     => 'tdb_template_type',
                    'value'   => 'cpt',
                ),
                array(
                    'key'     => 'tdc_is_mobile_template',
                    'value'   => 1,
                )
            ),
            'posts_per_page' => '-1'
        )
    );

    $mobile_templates = [];
    if ( !empty( $wp_query_tdb_tpl_type_mob_templates->posts ) ) {
        foreach ( $wp_query_tdb_tpl_type_mob_templates->posts as $mob_template ) {
            $mobile_templates[] = array(
                'tpl_id' => $mob_template->ID,
                'tpl_title' => $mob_template->post_title,
                'tpl_tdc_url' => admin_url( 'post.php?post_id=' . $mob_template->ID . '&td_action=tdc&tdbTemplateType=' . $mob_template->tdb_template_type )
            );
        }
    }

    $cpts = td_util::get_cpts();

	$templates = [];
	foreach ( $cpts as $cpt ) {
        $templates[$cpt->name] = [];
        $templates[$cpt->name]['name'] = $cpt->label;
        $templates[$cpt->name]['global_tpl'] = [];
        $templates[$cpt->name]['templates'] = [];
        $templates[$cpt->name]['mobile_templates'] = $mobile_templates;
        $templates[$cpt->name]['tpl_card_data_assets'] = get_tpl_card_data_assets('cpt', $cpt->name);
    }

    // query cloud templates
    $wp_query_templates = new WP_Query(
        array(
            'post_type' => array( 'tdb_templates' ),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'meta_query' => array(
                //'relation' => 'AND',
                array(
                    'key'     => 'tdb_template_type',
                    'value'   => 'cpt',
                ),
                array(
                    'key'     => 'tdc_is_mobile_template',
                    'compare'   => 'NOT EXISTS',
                )
            ),
        )
    );

	$td_cpt = td_util::get_option('td_cpt');

    if ( !empty( $wp_query_templates->posts ) ) {

        $tpls = [];

        foreach ( $wp_query_templates->posts as $template ) {
            $tpl_data = (array) $template;

            // mobile tpl init
		    $tpl_data['mobile_tpl_id'] = '';
		    $mobile_tpl = null;

		    // read mob tpl id meta
		    $mobile_tpl_id = get_post_meta( $template->ID, 'tdc_mobile_template_id', true );

		    if ( !empty( $mobile_tpl_id ) ) {
			    $mobile_tpl = get_post( $mobile_tpl_id );
			    if ( $mobile_tpl instanceof WP_Post && 'publish' === get_post_status( $mobile_tpl_id ) ) {
				    // set mobile tpl
				    $tpl_data['mobile_tpl_id'] = (int) $mobile_tpl_id;
			    }
		    }

            $tpl_data['view_link'] = get_permalink($template->ID);
            $tpl_data['edit_link'] = get_edit_post_link( $template->ID, 'raw' );

            $tpls[] = $tpl_data;
        }

        $option_id = 'td_default_site_post_template' . $lang;

        foreach ( $cpts as $cpt ) {
            $templates[$cpt->name]['templates'] = $tpls;

            if ( !empty( $td_cpt[$cpt->name][$option_id] ) ) {
                $default_template_id = $td_cpt[$cpt->name][$option_id];

                if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                    $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
                    if ( !empty($tdb_template_id) ) {
                        $templates[$cpt->name]['global_tpl'] = $tdb_template_id;
                    }
                }
            }
        }
    }





    // query cloud templates
    $wp_query_templates_tax = new WP_Query(
        array(
            'post_type' => array( 'tdb_templates' ),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'meta_query' => array(
                array(
                    'key'     => 'tdb_template_type',
                    'value'   => 'cpt_tax',
                ),
                array(
                    'key'     => 'tdc_is_mobile_template',
                    'compare'   => 'NOT EXISTS',
                )
            ),
        )
    );

    $td_cpt_tax = td_util::get_option('td_cpt_tax');

    if ( !empty( $wp_query_templates_tax->posts ) ) {

        $tpls_tax = [];

        foreach ( $wp_query_templates_tax->posts as $template ) {
            $tpl_data = (array) $template;

            // mobile tpl init
		    $tpl_data['mobile_tpl_id'] = '';
		    $mobile_tpl = null;

		    // read mob tpl id meta
		    $mobile_tpl_id = get_post_meta( $template->ID, 'tdc_mobile_template_id', true );

		    if ( !empty( $mobile_tpl_id ) ) {
			    $mobile_tpl = get_post( $mobile_tpl_id );
			    if ( $mobile_tpl instanceof WP_Post && 'publish' === get_post_status( $mobile_tpl_id ) ) {
				    // set mobile tpl
				    $tpl_data['mobile_tpl_id'] = (int) $mobile_tpl_id;
			    }
		    }

            $tpl_data['view_link'] = get_permalink($template->ID);
            $tpl_data['edit_link'] = get_edit_post_link( $template->ID, 'raw' );

            $tpls_tax[] = $tpl_data;
        }

        $option_id = 'tdb_category_template' . $lang;

        // get cloud templates type mobile templates
        $wp_query_tdb_tpl_type_mob_templates = new WP_Query(
            array(
                'post_type' => array( 'tdb_templates' ),
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key'     => 'tdb_template_type',
                        'value'   => 'cpt_tax',
                    ),
                    array(
                        'key'     => 'tdc_is_mobile_template',
                        'value'   => 1,
                    )
                ),
                'posts_per_page' => '-1'
            )
        );

        $mobile_templates = [];
        if ( !empty( $wp_query_tdb_tpl_type_mob_templates->posts ) ) {
            foreach ( $wp_query_tdb_tpl_type_mob_templates->posts as $mob_template ) {
                $mobile_templates[] = array(
                    'tpl_id' => $mob_template->ID,
                    'tpl_title' => $mob_template->post_title,
                    'tpl_tdc_url' => admin_url( 'post.php?post_id=' . $mob_template->ID . '&td_action=tdc&tdbTemplateType=' . $mob_template->tdb_template_type )
                );
            }
        }

        foreach ($cpts as $cpt) {
            if ( ! isset( $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'tax_templates' ] ) ) {
                $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'tax_templates' ] = [];
            }
	        foreach ( $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'card_tpl_data_tax' ] as $tax_name => $tax_data ) {
		        $temp                           = [];
		        $temp[ 'name' ]                 = $tax_data->label;
		        $temp[ 'global_tpl' ]           = [];
		        $temp[ 'templates' ]            = $tpls_tax;
		        $temp[ 'mobile_templates' ]     = $mobile_templates;
		        $temp[ 'tpl_card_data_assets' ] = get_tpl_card_data_assets( 'cpt_tax', $cpt->name );

		        if ( ! empty( $td_cpt_tax[ $tax_data->name ][ $option_id ] ) ) {
			        $default_template_id = $td_cpt_tax[ $tax_data->name ][ $option_id ];

			        if ( td_global::is_tdb_template( $default_template_id, true ) ) {
				        $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
				        if ( ! empty( $tdb_template_id ) ) {
					        $temp[ 'global_tpl' ] = $tdb_template_id;
				        }
			        }
		        }

		        $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'tax_templates' ][ $tax_data->name ] = $temp;
	        }
        }
    } else {
        foreach ($cpts as $cpt) {
            if ( ! isset( $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'tax_templates' ] ) ) {
                $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'tax_templates' ] = [];
            }
	        foreach ( $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'card_tpl_data_tax' ] as $tax_name => $tax_data ) {
		        $temp                           = [];
		        $temp[ 'name' ]                 = $tax_data->label;
		        $temp[ 'global_tpl' ]           = [];
		        $temp[ 'templates' ]            = [];
		        $temp[ 'mobile_templates' ]     = $mobile_templates;
		        $temp[ 'tpl_card_data_assets' ] = [];

		        $templates[ $cpt->name ][ 'tpl_card_data_assets' ][ 'tax_templates' ][ $tax_data->name ] = $temp;
	        }
        }
    }

    $reply['templates'] = $templates;

    die( json_encode( $reply ) );
}



add_action('wp_ajax_nopriv_tdb_assign_cpt_template_global', 'tdb_assign_cpt_template_global');
add_action('wp_ajax_tdb_assign_cpt_template_global', 'tdb_assign_cpt_template_global');
function tdb_assign_cpt_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $cpt = $_POST['cpt'];
    if ( empty( $cpt ) ) {
        $reply['type'] = 'error';
		$reply['msg'] = 'custom post type is required!';
        die( json_encode( $reply ) );
    }

    $lang = '';
    if (class_exists('SitePress', false)) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
            if (1 === $translation_mode) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'td_default_site_post_template' . $lang;

    $unset = $_POST['unset'];
    if ( !empty( $unset ) ) {

        $td_cpt = td_util::get_option('td_cpt');
        if ( !empty( $td_cpt[ $cpt ] ) ) {
            unset($td_cpt[ $cpt ][ $option_id ]);
        }
        td_util::update_option( 'td_cpt', $td_cpt );

        $reply[ 'global_template_id' ] = '';

    } else {

        $template_id = $_POST['template_id'];
        if ( empty( $template_id ) ) {
            $reply['type'] = 'error';
            $reply['msg'] = 'template id is required!';
            die( json_encode( $reply ) );
        }

        $option_value = 'tdb_template_' . $template_id;

        $td_cpt = td_util::get_option('td_cpt');
        if (empty($td_cpt)) {
            $td_cpt = [
                $cpt => [
                    $option_id => $option_value,
                ]
            ];
        } else if (empty($td_cpt[$cpt])) {
            $td_cpt[$cpt] = [];
            $td_cpt[$cpt][$option_id] = $option_value;
        } else {
            $td_cpt[$cpt][$option_id] = $option_value;
        }
        td_util::update_option('td_cpt', $td_cpt);

        $td_cpt = td_util::get_option('td_cpt');

        // read back the global setting
        $default_template_id = $td_cpt[$cpt][$option_id];

        if ( td_global::is_tdb_template( $default_template_id, true ) ) {
            $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
            if ( intval($template_id) === $tdb_template_id ) {
                $reply['global_template_id'] = $template_id;
            }
        }

    }

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_assign_cpt_tax_template_global', 'tdb_assign_cpt_tax_template_global');
add_action('wp_ajax_tdb_assign_cpt_tax_template_global', 'tdb_assign_cpt_tax_template_global');
function tdb_assign_cpt_tax_template_global(){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $cpt_tax = $_POST['cpt_tax'];
    if ( empty( $cpt_tax ) ) {
        $reply['type'] = 'error';
		$reply['msg'] = 'custom taxonomy is required!';
        die( json_encode( $reply ) );
    }

    $lang = '';
    if ( class_exists( 'SitePress', false ) ) {
        global $sitepress;
        $sitepress_settings = $sitepress->get_settings();
        if ( isset( $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ] ) ) {
            $translation_mode = (int) $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ];
            if ( 1 === $translation_mode ) {
                $lang = $sitepress->get_current_language();
            }
        }
    }

    $option_id = 'tdb_category_template' . $lang;

    $unset = $_POST['unset'];
    if ( !empty( $unset ) ) {

        $td_cpt_tax = td_util::get_option( 'td_cpt_tax' );
        if ( !empty( $td_cpt_tax[ $cpt_tax ] ) ) {
            unset($td_cpt_tax[ $cpt_tax ][ $option_id ]);
        }
        td_util::update_option( 'td_cpt_tax', $td_cpt_tax );

        $reply[ 'global_template_id' ] = '';

    } else {

        $template_id = $_POST['template_id'];
        if ( empty( $template_id ) ) {
            $reply['type'] = 'error';
            $reply['msg'] = 'template id is required!';
            die( json_encode( $reply ) );
        }

	    $option_value = 'tdb_template_' . $template_id;

	    $td_cpt_tax = td_util::get_option( 'td_cpt_tax' );
	    if ( empty( $td_cpt_tax ) ) {
		    $td_cpt_tax = [
			    $cpt_tax => [
				    $option_id => $option_value,
			    ]
		    ];
	    } else if ( empty( $td_cpt_tax[ $cpt_tax ] ) ) {
		    $td_cpt_tax[ $cpt_tax ]               = [];
		    $td_cpt_tax[ $cpt_tax ][ $option_id ] = $option_value;
	    } else {
		    $td_cpt_tax[ $cpt_tax ][ $option_id ] = $option_value;
	    }
	    td_util::update_option( 'td_cpt_tax', $td_cpt_tax );

	    $td_cpt_tax = td_util::get_option( 'td_cpt_tax' );

	    // read back the global setting
	    $default_template_id = $td_cpt_tax[ $cpt_tax ][ $option_id ];

	    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
		    $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
		    if ( intval( $template_id ) === $tdb_template_id ) {
			    $reply[ 'global_template_id' ] = $template_id;
		    }
	    }
    }

    wp_die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_cpt_templates', 'tdb_get_cpt_templates');
add_action('wp_ajax_tdb_get_cpt_templates', 'tdb_get_cpt_templates');
function tdb_get_cpt_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $cpt_id = $_POST['cpt_id'];
    if ( empty( $cpt_id ) ) {
        $reply['type'] = 'error';
		$reply['msg'] = 'custom post type id is required !';
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'cpt',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $test = false;
    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $lang = '';
        if (class_exists('SitePress', false)) {
            global $sitepress;
            $sitepress_settings = $sitepress->get_settings();
            if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                if (1 === $translation_mode) {
                    $lang = $sitepress->get_current_language();
                }
            }
        }

        $global_template_id = '';
        $option_id = 'td_default_site_post_template' . $lang;
        $td_cpt = td_util::get_option('td_cpt');
        $cpts = td_util::get_cpts();

        foreach ($cpts as $cpt) {
            if ($cpt_id === $cpt->name && !empty($td_cpt[$cpt->name][$option_id])) {
                $default_template_id = $td_cpt[$cpt->name][$option_id];

                if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                    $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
                    if ( !empty($tdb_template_id) ) {
                        $global_template_id = $tdb_template_id;
                        break;
                    }
                }
            }
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => (!empty($global_template_id) && intval($global_template_id) === intval($post->ID)) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_cpt_mobile_templates', 'tdb_get_cpt_mobile_templates');
add_action('wp_ajax_tdb_get_cpt_mobile_templates', 'tdb_get_cpt_mobile_templates');
function tdb_get_cpt_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'cpt',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=cpt')
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_cpt_tax_templates', 'tdb_get_cpt_tax_templates');
add_action('wp_ajax_tdb_get_cpt_tax_templates', 'tdb_get_cpt_tax_templates');
function tdb_get_cpt_tax_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $cpt_tax_id = $_POST['cpt_tax_id'];
    if ( empty( $cpt_tax_id ) ) {
        $reply['type'] = 'error';
		$reply['msg'] = 'custom taxonomy id is required !';
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'cpt_tax',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => '-1'
    );

    $test = false;
    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        $lang = '';
        if (class_exists('SitePress', false)) {
            global $sitepress;
            $sitepress_settings = $sitepress->get_settings();
            if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                if (1 === $translation_mode) {
                    $lang = $sitepress->get_current_language();
                }
            }
        }

        $global_template_id = '';
        $option_id = 'tdb_category_template' . $lang;
        $td_cpt_tax = td_util::get_option('td_cpt_tax');
        $ctaxes = td_util::get_ctaxes();

        foreach ($ctaxes as $ctax) {

            if ($cpt_tax_id === $ctax->name && !empty($td_cpt_tax[$ctax->name][$option_id])) {
                $default_template_id = $td_cpt_tax[$ctax->name][$option_id];

                if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                    $tdb_template_id = td_global::tdb_get_template_id( $default_template_id );
                    if ( !empty($tdb_template_id) ) {
                        $global_template_id = $tdb_template_id;
                        break;
                    }
                }
            }
        }

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $mobile_template = null;
            $mobile_template_title = '';
            $mobile_template_id = get_post_meta($post->ID, 'tdc_mobile_template_id', true );

            if ( ! empty($mobile_template_id)) {
                $mobile_template = get_post($mobile_template_id);
                if ( $mobile_template instanceof WP_Post && 'publish' === get_post_status($mobile_template_id)) {
                    $mobile_template_title = $mobile_template->post_title;
                } else {
                    $mobile_template_id = '';
                }
            }

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'is_current' => (!empty($global_template_id) && intval($global_template_id) === intval($post->ID)) ? true : false,
                'mobile_template_id' => empty($mobile_template_id) ? '' : $mobile_template_id,
                'mobile_template_title' => empty($mobile_template_title) ? '' : $mobile_template_title
            );
        }
    }

    die( json_encode( $reply ) );
}


add_action('wp_ajax_nopriv_tdb_get_cpt_tax_mobile_templates', 'tdb_get_cpt_tax_mobile_templates');
add_action('wp_ajax_tdb_get_cpt_tax_mobile_templates', 'tdb_get_cpt_tax_mobile_templates');
function tdb_get_cpt_tax_mobile_templates (){
    $reply = array();

    $nonce = $_POST['_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        die( json_encode( $reply ) );
    }

    $args = array(
        'post_type' => array('tdb_templates'),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key'     => 'tdb_template_type',
                'value'   => 'cpt_tax',
            ),
            array(
                'key'     => 'tdc_is_mobile_template',
                'value'   => 1,
            )
        ),
        'posts_per_page' => '-1'
    );

    $wp_query_templates = new WP_Query( $args );

    if (!empty($wp_query_templates->posts)) {

        /**
         * @var $post WP_Post
         */
        foreach ($wp_query_templates->posts as $post) {

            $reply[] = array(
                'template_id' => $post->ID,
                'template_title' => $post->post_title,
                'template_url' => admin_url( 'post.php?post_id=' . $post->ID . '&td_action=tdc&tdbTemplateType=cpt_tax')
            );
        }
    }

    die( json_encode( $reply ) );
}
