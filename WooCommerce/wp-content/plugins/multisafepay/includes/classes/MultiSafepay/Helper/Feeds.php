<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


    echo '<pre>';
    echo 'Parameters<br/>-----------------------<br/>';
    print_r ($_GET);

    $results = feeds ($_GET);

    // Reindex array
    $results = reOrderArray($results);

    // create JSON
    $json = json_encode($results);
    echo 'JSON<br/>-----------------------<br/>';
    echo prettyPrint($json);

//  return ($json);
    die(PHP_EOL . '------------------------------'. PHP_EOL . 'Ready with the feed');



    function feeds ($params){

        if ( $params['identifier'] == 'test')
            return (array ('Feed wordt correct aangeroepen'));

        // no API-Key provided
        if (!isset ($params['api_key']))
            die('no API-Key provided');

        // Invalid API-Key
        if ($params['api_key'] != get_option('multisafepay_api_key'))
            die('Invalid API-Key');

        // no identifier provided
        if (!isset ($params['identifier']))
            die(' no identifier provided');


        if ( $params['identifier'] == 'products'  && isset($params['product_id']) && $params['product_id'] != '')
			return (productById( $params['product_id']));

        if ( $params['identifier'] == 'products'  && isset($params['category_id']) && $params['category_id'] != '' )
            return (productByCategory($params['category_id']));

        if ( $params['identifier'] == 'category')
            return (categories($params));

        if ( $params['identifier'] == 'stock'  && isset($params['product_id']) && $params['product_id'] != '' )
            return (stock($params['product_id']));

        if ( $params['identifier'] == 'stores')
            return (stores($params));

        if ( $params['identifier'] == 'shipping')
            return (shipping($params));

        return (array ('No results available'));
    }


    function productById ($product_id=0){

		$results = array();
		$product 	= WC()->product_factory->get_product( $product_id );
		if ($product)
			$results 	= get_product_details ($product);

		return($results);
	}

    function productByCategory ($category_id=0){

		$results = array();
		$args = array(
			'post_type'             => 'product',
			'post_status'           => 'publish',
			'ignore_sticky_posts'   => 1,
			'posts_per_page'        => '-1',
			'meta_query'            => array(
				array(
					'key'           => '_visibility',
					'value'         => array('catalog', 'visible'),
					'compare'       => 'IN'
				)
			),
			'tax_query'             => array(
				array(
					'taxonomy'      => 'product_cat',
					'field'         => 'term_id', //This is optional, as it defaults to 'term_id'
					'terms'         => $category_id,
					'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
				)
			)
		);

		$loop = new WP_Query( $args );

		while ( $loop->have_posts() ){
			$loop->the_post();
			$id = get_the_ID();
			$_product 	= WC()->product_factory->get_product($id);
			$results[] = get_product_details ($_product);
		}

		return ($results);
	}

	function stock ($product_id=0){

		$results = array();
		$product 	= WC()->product_factory->get_product( $product_id );
		if ($product) {
			$data 	= get_product_details ($product);

			$results 	= array( 	'product_id'	=>	$product->get_id(),
									'stock'			=> 	$product->get_stock_quantity());
		}
		return ($results);
	}

    function categories (){
		$results = _categories();
		return ($results);
	}

    function shipping (){

		$active_methods   = array();
		$shipping_methods = WC()->shipping->get_shipping_methods();
		foreach ( $shipping_methods as $id => $shipping_method ) {
			if ( isset( $shipping_method->enabled ) && $shipping_method->enabled == 'yes' ) {

				$active_methods[] = array( 	'id'				=> $id,
											'type' 				=> $shipping_method->method_title,
											'provider' 			=> '',
											'name' 				=> $shipping_method->method_title,
											'price' 			=> '',
											'excluded_areas'	=> array (),
											'included_areas' 	=> array ()
										);
			}
		}

		return $active_methods;
}

    function stores (){

		$store = array ('allowed_countries'  	=> array (),
						'shipping_countries'  	=> array (),
						'languages'  			=> array (),
						'stock_updates'  		=> get_option('woocommerce_manage_stock') 		== 'yes' ? true : false,
						'allowed_currencies'	=> get_woocommerce_currency(),
						'including_tax'  		=> get_option('woocommerce_prices_include_tax') == 'yes' ? true : false,

						'shipping_tax'  		=> array(),
						'require_shipping'  	=> wc_shipping_enabled(),
						'base_url'  			=> get_home_url(),
						'order_push_url'  		=> get_option('multisafepay_nurl'),
						'coc'  					=> '',
						'email'  				=> '',
						'contact_phone'  		=> '',
						'address'  				=> '',
						'housenumber'  			=> '',
						'zipcode'  				=> '',
						'city'  				=> '',
						'country'  				=> '',
						'vat_nr'  				=> '',
						'terms_and_conditions'  => '',
						'faq'  					=> '',
						'open'  				=> '00:00',
						'closed'  				=> '23:59',
						'days'  				=> array ( 	'sunday'	=> true,
															'monday'	=> true,
															'tuesday'	=> true,
															'wednesday'	=> true,
															'thursday'	=> true,
															'friday'	=> true,
															'saturday'	=> true ),
						'social'  				=> array (	'facebook'	=> '',
															'twitter'	=> '',
															'linkedin'	=> ''),

						'languages'		 		=> array (	get_locale() ),
						'shipping_tax'			=> array (  'id'	=> '',
															'name'	=> '',
															'rules'	=> array ( get_locale() => '' )));
		return ($store);
}


	function get_product_details ($product){

		// get categories, maximal=2
		$categories = strip_tags( $product->get_categories());
		list ($primary_category, $secondary_category, $rest) = array_pad(explode(', ', $categories, 3), 3, null);

		// get (all) taxes incl info
		$tax_id 	= $product->get_tax_class() ? $product->get_tax_class() : 'Standard';
		$rates  	= reset (WC_Tax::get_rates( $product->get_tax_class() ));
		$location 	= WC_tax::get_tax_location();

		// get meta tags
		$metadata = array();
		$tags = explode(", ", strip_tags ($product->get_tags()));
		foreach( $tags as $tag ) {
			array_push ($metadata, array ( get_locale() => array (	'title' 		=> $tag,
																	'keyword' 		=> $tag,
																	'description' 	=> $tag)));
		}

		// get main image
		$images['product_image_urls']	= array();
		$_images = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );
		if ($_images) {
			$main_image = reset ($_images);
			array_push ($images['product_image_urls'], array ( 	'url' 	=> $main_image,
																'main'  => true));
		}

		// get other images
		$attachment_ids = $product->get_gallery_attachment_ids();
		foreach( $attachment_ids as $attachment_id )
		{
			$_images = wp_get_attachment_url( $attachment_id );
			if ($_images != $main_image)
				array_push ($images['product_image_urls'], array ( 	'url' 	=> $_images,
																	'main'  => false));
		}

		// Variens?
		$variants = array();
		if( $product->has_child() ) {

			$available_variations = $product->get_available_variations();
			foreach ($available_variations as $variation) {

				$attributes	= array();
				foreach ($variation['attributes'] as $key => $attr){
					$key = str_replace ('attribute_pa_', '', $key);
					$attributes['attributes'][$key] = array ( get_locale() => array ('label' => $key, 'value' => $attr));
				}

				$variants[] = array (	'product_id' 		=> $variation['variation_id'],
										'sku_number' 		=> $variation['sku'],
										'gtin' 				=> false,
										'unique_identifier' => false,
										'product_image_url'	=> $images,
										'stock' 			=> $variation['max_qty'],
										'sale_price' 		=> $variation['display_regular_price'],
										'retail_price' 		=> $variation['display_regular_price'],
										'attributes' 		=> $attributes);
			}
		}

		$_product[]  = array (	'product_id' 				=> $product->get_id(),
								'parentproduct_id' 			=> $product->get_parent(),
								'product_name' 				=> $product->get_title(),
								'brand' 					=> null,

								'sku_number' 				=> $product->get_sku(),
								'product_url' 				=> $product->post->guid,
								'primary_category' 			=> array ( get_locale() => $primary_category ),
								'secondary_category' 		=> array ( get_locale() => $secondary_category ),
								'shortproduct_description' 	=> array ( get_locale() => $product->post->post_excerpt ),
								'longproduct_description' 	=> array ( get_locale() => $product->post->post_content ),
								'sale_price' 				=> $product->get_regular_price(),
								'retail_price' 				=> $product->get_regular_price(),
								'tax' 						=> array (	'id'		=> $tax_id,
																		'name'		=> $rates['label'],
																		'rules'		=> array ($location[0] => $rates['rate'])),

								'gtin' 						=> '',
								'mpn' 						=> '',
								'unique_identifier' 		=> false,
								'stock' 					=> $product->get_stock_quantity(),
								'options' 					=> '',
								'attributes' 				=> '',
								'metadata' 					=> $metadata,
								'created' 					=> $product->post->post_date,
								'updated' 					=> $product->post->post_modified,
								'downloadable' 				=> $product->is_downloadable(),
								'package_dimensions' 		=> $product->get_length() .'x' . $product->get_width() . 'x'. $product->get_height(),
								'dimension_unit' 			=> get_option( 'woocommerce_dimension_unit' ),
								'weight' 					=> $product->get_weight(),
								'weight_unit' 				=> get_option( 'woocommerce_weight_unit' ),

								'product_image_urls' 		=> $images,
								'variants'					=> $variants
								);

		return ($_product);
	}

	function _categories($id=0) {
		global $wpdb;
		$sql = "SELECT wp_terms.term_id, wp_terms.name
				FROM wp_terms
				LEFT JOIN wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id
				WHERE wp_term_taxonomy.taxonomy = 'product_cat'
 				  AND wp_term_taxonomy.parent =" . $id;

		$results  = $wpdb->get_results($sql);
		$children = array();

		if( count($results) > 0) {

			# It has children, let's get them.
			foreach ($results as $key => $result) {
				# Add the child to the list of children, and get its subchildren
				$children[$result->term_id]['id']   	= $result->term_id;
				$children[$result->term_id]['title'] 	= array (get_locale() => $result->name);
				$children[$result->term_id]['children'] = _categories($result->term_id);
			}
		}

		return $children;
	}

    function reOrderArray($array) {
        if(!is_array($array)) {
             return $array;
        }
        $count = 0;
        $result = array();
        foreach($array as $k => $v) {
            if(is_integer_value($k)) {
               $result[$count] = reOrderArray($v);
               ++$count;
            } else {
              $result[$k] = reOrderArray($v);
            }
        }
        return $result;
    }

    function is_integer_value($value) {
        if(!is_int($value)) {
            if(is_string($value) && preg_match("/^-?\d+$/i",$value)) {
                return true;
            }
            return false;
        }
        return true;
    }

    function prettyPrint( $json )  {
        $result = '';
        $level = 0;
        $in_quotes = false;
        $in_escape = false;
        $ends_line_level = NULL;
        $json_length = strlen( $json );

        for( $i = 0; $i < $json_length; $i++ ) {
            $char = $json[$i];
            $new_line_level = NULL;
            $post = "";
            if( $ends_line_level !== NULL ) {
                $new_line_level = $ends_line_level;
                $ends_line_level = NULL;
            }
            if ( $in_escape ) {
                $in_escape = false;
            } else if( $char === '"' ) {
                $in_quotes = !$in_quotes;
            } else if( ! $in_quotes ) {
                switch( $char ) {
                    case '}': case ']':
                        $level--;
                        $ends_line_level = NULL;
                        $new_line_level = $level;
                        break;

                    case '{': case '[':
                        $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ": case "\t": case "\n": case "\r":
                        $char = "";
                        $ends_line_level = $new_line_level;
                        $new_line_level = NULL;
                        break;
                }
            } else if ( $char === '\\' ) {
                $in_escape = true;
            }
            if( $new_line_level !== NULL ) {
                $result .= "\n".str_repeat( "\t", $new_line_level );
            }
            $result .= $char.$post;
        }

        return $result;
    }
?>
