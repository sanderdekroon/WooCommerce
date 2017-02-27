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

/*  // todo
        www.store.com/index.php?api_key=xxxxx&language=en_US&identifier=products&product_id=10
        www.store.com/index.php?api_key=xxxxx&language=en_US&identifier=products&category_id=x
        www.store.com/index.php?api_key=xxxxx&language=en_US&identifier=tax
        www.store.com/index.php?api_key=xxxxx&language=en_US&identifier=categories
        www.store.com/index.php?api_key=xxxxx&language=en_US&identifier=stock&product_id=10
        www.store.nl/index.php?api_key=xxxxx&identifier=stores
        www.store.com/index.php?api_key=xxxxx&language=en_US&identifier=shipping&countrycode=NL
*/

        if ( $params['identifier'] == 'test')
            return (array ('Feed wordt correct aangeroepen'));


        // no API-Key provided
        if (!isset ($params['api_key']))
            die('no API-Key provided');

        // Invalid API-Key
        if ($params['api_key'] != get_option('multisafepay_api_key'))
            die('Invalid API-Key');


        // no Language provided
        if (!isset($params['language']))
            die('no Language provided');

        // no identifier provided
        if (!isset ($params['identifier']))
            die(' no identifier provided');


        if ( $params['identifier'] == 'products'  && isset($params['product_id']) && $params['product_id'] != '')
            return (productById($params));

        if ( $params['identifier'] == 'products'  && isset($params['category_id']) && $params['category_id'] != '' )
            return ($this->productByCategory($params));

        if ( $params['identifier'] == 'tax')
            return ($this->tax($params));

        if ( $params['identifier'] == 'category')
            return ($this->categories($params));

        if ( $params['identifier'] == 'stock'  && isset($params['product_id']) && $params['product_id'] != '' )
            return ($this->stock($params));

        if ( $params['identifier'] == 'stores')
            return ($this->stores($params));

        if ( $params['identifier'] == 'shipping')
            return ($this->shipping($params));

        return (array ('No results available'));
    }




    function productById ($params){

        $_pf = new WC_Product_Factory();

        $IDs = array ($params['product_id']);
        foreach ($IDs as $id) {

            $_product = $_pf->get_product($id);
//          print_r ($_product);

            $meta = get_post_meta($params['product_id']);
//          print_r ($meta);


            $tmp_product["product_id"]                  = $_product->get_id();
            $tmp_product["parent_product_id"]           = $_product->get_parent();
            $tmp_product["product_name"]                = $_product->get_title();
            $tmp_product["brand"]                       = null;
            $tmp_product["sku_number"]                  = $_product->get_sku();


            $categories = strip_tags( $_product->get_categories());
            list ($primary_category, $secondary_category) = explode(", ", $categories);

            $tmp_product["primary_category"]            = array (   "en_US" => $primary_category,
                                                                    "fr_FR" => $primary_category,
                                                                    "de_DE" => $primary_category);

            $tmp_product["secondary_category"]          = array (   "en_US" => $secondary_category,
                                                                    "fr_FR" => $secondary_category,
                                                                    "de_DE" => $secondary_category);

            $tmp_product["product_url"]                 = $_product->post->guid;
            $tmp_product["short_product_description"]   = array (   "en_US" => $_product->post->post_excerpt,
                                                                    "fr_FR" => $_product->post->post_excerpt,
                                                                    "de_DE" => $_product->post->post_excerpt
                                                                );

            $tmp_product["long_product_description"]    = array (   "en_US" => $_product->post->post_content,
                                                                    "fr_FR" => $_product->post->post_content,
                                                                    "de_DE" => $_product->post->post_content
                                                                );
            $tmp_product["sale_price"]                  = $_product->get_regular_price();
            $tmp_product["retail_price"]                = $_product->get_regular_price();


//          if ($_product->get_tax_status() == 'taxable')
//              $tmp_product["tax_id"]                  = $_product->get_tax_class() ? $_product->get_tax_class() : 'Standard';

            $tmp_product["gtin"]                        = null;
            $tmp_product["mpn"]                         = null;
            $tmp_product["unique_identifier"]           = false;
            $tmp_product["stock"]                       = $_product->get_stock_quantity();

//          $tmp_product["metadata"]                    = '';
//          $tmp_product["attributes"]                  = '';
//          $tmp_product["options"]                     = '' ;

            $tmp_product["created"]                     = $_product->post->post_date;
            $tmp_product["updated"]                     = $_product->post->post_modified;
            $tmp_product["downloadable"]                = $_product->is_downloadable();

            $tmp_product["package_dimensions"]          = $_product->get_length() .'x' . $_product->get_width() . 'x'. $_product->get_height();
            $tmp_product["dimension_unit"]              = get_option( 'woocommerce_dimension_unit' );

            $tmp_product["weight"]                      = $_product->get_weight();
            $tmp_product["weight_unit"]                 = get_option( 'woocommerce_weight_unit' );


            // Main image
            $tmp_product["product_image_urls"] = array();
            $image = wp_get_attachment_image_src( get_post_thumbnail_id( $_product->get_id() ), 'single-post-thumbnail' );
            if ($image) {
                $main_img = reset ($image);
                array_push ($tmp_product["product_image_urls"], array ( "url" => $main_img, "main"  => true));
            }

            // other images
            $attachment_ids = $_product->get_gallery_attachment_ids();
            foreach( $attachment_ids as $attachment_id )
            {
                $img = wp_get_attachment_url( $attachment_id );
                if ($img != $main_img)
                    array_push ($tmp_product["product_image_urls"], array ( "url" => $img, "main"  => false));
            }





            $available_variations = $_product->get_available_variations();

            print_r ($available_variations);
            $variants = array();
            foreach ($available_variations as $variation) {

                $_variant["product_id"]         = $variation['variation_id'];
                $_variant["sku_number"]         = $variation['sku'];
                $_variant["gtin"]               = false;
                $_variant["unique_identifier"]  = false;
                $_variant["product_image_urls"] = $tmp_product["product_image_urls"];
                $_variant["stock"]              = $variation['max_qty'];
                $_variant["sale_price"]         = $variation['display_regular_price'];
                $_variant["retail_price"]       = $variation['display_regular_price'];

                $_variant["attributes"]  = array();
                foreach ($variation['attributes'] as $key => $attr){

                    $key = str_replace ('attribute_pa_', '', $key);
                    $_variant["attributes"][$key] = array ('en_US' => array ('label' => $key, 'value' => $attr),
                                                           'fr_FR' => array ('label' => $key, 'value' => $attr),
                                                           'de_DE' => array ('label' => $key, 'value' => $attr),);
                }


                array_push ($variants, $_variant);
            }
            $tmp_product["variants"] = $variants;

//            print_r ($variants);




        }
        return ($tmp_product);
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
