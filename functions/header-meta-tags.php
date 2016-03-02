<?php

	// Queue up our hook function
	add_action( 'wp_head' , 'sw_add_header_meta' , 1 );

/*****************************************************************
*                                                                *
*          AN EXCERPT FUNCTION							         *
*                                                                *
******************************************************************/

	// A function to process the excerpts for descriptions		
	function sw_get_excerpt_by_id($post_id){
		
		// Check if the post has an excerpt
		if(has_excerpt()):
			$the_excerpt = get_the_excerpt();
			
		// If not, let's create an excerpt
		else:
			$the_post = get_post($post_id); //Gets post ID
			$the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
		endif;
		
		$excerpt_length = 100; //Sets excerpt length by word count
		$the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
		
		$the_excerpt = str_replace(']]>', ']]&gt;', $the_excerpt);
		$the_excerpt = strip_tags($the_excerpt);
		$excerpt_length = apply_filters('excerpt_length', 100);
		$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
		$words = preg_split("/[\n\r\t ]+/", $the_excerpt, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
	
		if(count($words) > $excerpt_length) :
			array_pop($words);
			// array_push($words, '…');
			$the_excerpt = implode(' ', $words);
		endif;
		
		$the_excerpt = preg_replace( "/\r|\n/", "", $the_excerpt );
	
		return $the_excerpt;
	}
/*****************************************************************
*                                                                *
*          Curly Quote Converter						         *
*                                                                *
******************************************************************/
	function convert_smart_quotes($content) {
		 $content = str_replace('"', '\'', $content);
		 $content = str_replace('&#8220;', '\'', $content);
		 $content = str_replace('&#8221;', '\'', $content);
		 $content = str_replace('&#8216;', '\'', $content);
		 $content = str_replace('&#8217;', '\'', $content);
		 return $content;
	}
/*****************************************************************
*                                                                *
*          Easy Hook Remover							         *
*                                                                *
******************************************************************/
	function sw_remove_filter($hook_name = '', $method_name = '', $priority = 0 ) {
		global $wp_filter;
		
		// Take only filters on right hook name and priority
		if ( !isset($wp_filter[$hook_name][$priority]) || !is_array($wp_filter[$hook_name][$priority]) )
			return false;
		
		// Loop on filters registered
		foreach( (array) $wp_filter[$hook_name][$priority] as $unique_id => $filter_array ) {
			// Test if filter is an array ! (always for class/method)
			if ( isset($filter_array['function']) && is_array($filter_array['function']) ) {
				// Test if object is a class and method is equal to param !
				if ( is_object($filter_array['function'][0]) && get_class($filter_array['function'][0]) && $filter_array['function'][1] == $method_name ) {
					unset($wp_filter[$hook_name][$priority][$unique_id]);
				}
			}
			
		}
		
		return false;
	}

/*****************************************************************
*                                                                *
*          HEADER META DATA								         *
*                                                                *
******************************************************************/

	// This is the hook function we're adding the header
	function sw_add_header_meta() {
		
		$info['postID'] = get_the_ID();
			
		// Cache some resource for fewer queries on subsequent page loads
		if(sw_is_cache_fresh($info['postID']) == false):
		
			// Check if an image ID has been provided
			$info['imageID'] = get_post_meta( $info['postID'] , 'nc_ogImage' , true );
			if($info['imageID']):
				$info['imageURL'] = wp_get_attachment_url( $info['imageID'] );
				delete_post_meta($info['postID'],'sw_open_graph_image_url');
				update_post_meta($info['postID'],'sw_open_graph_image_url',$info['imageURL']);
			else:
				$info['imageURL'] = wp_get_attachment_url( get_post_thumbnail_id( $info['postID'] ) );
				delete_post_meta($info['postID'],'sw_open_thumbnail_url');
				update_post_meta($info['postID'],'sw_open_thumbnail_url' , $info['imageURL']);
				delete_post_meta($info['postID'],'sw_open_graph_image_url');
			endif;

			// Cache the Twitter Handle
			$user_twitter_handle 	= get_the_author_meta( 'sw_twitter' , sw_get_author($info['postID']));
			if($user_twitter_handle):
				delete_post_meta($info['postID'],'sw_twitter_username');
				update_post_meta($info['postID'],'sw_twitter_username',$user_twitter_handle);
			else:
				delete_post_meta($info['postID'],'sw_twitter_username');
			endif;
		
		else:
		
			// Check if we have a cached Open Graph Image URL
			$info['imageURL'] = get_post_meta( $info['postID'] , 'sw_open_graph_image_url' , true );
			
			// If not, let's check to see if we have an ID to generate one
			if(!$info['imageURL']):
				
				// Check for an Open Graph Image ID
				$info['imageID'] = get_post_meta( $info['postID'] , 'nc_ogImage' , true );
				if($info['imageID']):
				
					// If we find one, let's convert it to a link and cache it for next time
					$info['imageURL'] = wp_get_attachment_url( $info['imageID'] );
					delete_post_meta($info['postID'],'sw_open_graph_image_url');
					update_post_meta($info['postID'],'sw_open_graph_image_url',$info['imageURL']);
					
				else:
				
					// If we don't find one, let's save the URL of the thumbnail in case we need it
					$thumbnail_image = get_post_meta($info['postID'],'sw_open_thumbnail_url' , true);
				endif;
			endif;
			
			
			$user_twitter_handle = get_post_meta( $info['postID'] , 'sw_twitter_username' , true );
						
		endif;			
			
		// Create the image Open Graph Meta Tag
		$info['postID'] 				= get_the_ID();
		$info['title'] 					= htmlspecialchars( get_post_meta( $info['postID'] , 'nc_ogTitle' , true ) );
		$info['description'] 			= htmlspecialchars( get_post_meta( $info['postID'] , 'nc_ogDescription' , true ) );
		$info['sw_fb_author'] 			= htmlspecialchars( get_post_meta( $info['postID'] , 'sw_fb_author' , true ) );
		$info['sw_user_options'] 		= sw_get_user_options();
		$info['user_twitter_handle'] 	= $user_twitter_handle;
		$info['header_output']			= '';
		
		$info = apply_filters( 'sw_meta_tags' , $info );

		if($info['header_output']):
			echo PHP_EOL .'<!-- Open Graph Meta Tags & Twitter Card generated by Social Warfare v'.SW_VERSION.' http://warfareplugins.com -->';
			echo $info['header_output'];
			echo PHP_EOL .'<!-- Open Graph Meta Tags & Twitter Card generated by Social Warfare v'.SW_VERSION.' http://warfareplugins.com -->'. PHP_EOL . PHP_EOL;
		endif;
	}

/*****************************************************************
*                                                                *
*          Queue Up our Open Graph Hooks				         *
*                                                                *
******************************************************************/
			
			// Queue up our hook function
			add_filter( 'sw_meta_tags' , 'sw_open_graph_tags' , 1 );
			add_filter( 'sw_meta_tags' , 'sw_add_twitter_card' , 2 );
			add_filter( 'sw_meta_tags' , 'sw_frame_buster' , 3 );
			add_filter( 'sw_meta_tags' , 'sw_output_custom_color' , 4 );
			add_filter( 'sw_meta_tags' , 'sw_output_font_css' , 5 );
			add_filter( 'sw_meta_tags' , 'sw_output_cache_trigger' , 6 );
			add_action( 'admin_head'   , 'sw_output_font_css' , 10);

			// Disable Simple Podcast Press Open Graph tags
			if ( is_plugin_active( 'simple-podcast-press/simple-podcast-press.php' ) ) {
				global $ob_wp_simplepodcastpress;
				remove_action( 'wp_head' , array( $ob_wp_simplepodcastpress , 'spp_open_graph') , 1);
			}
			
/*****************************************************************
*                                                                *
*   Open Graph Tags										         *
*                                                                *
* 	Dev Notes: If the user specifies an Open Graph tag,			 *
*	we're going to develop a complete set of tags. Order		 *
*	of preference for each tag is as follows:					 *
*	1. Did they fill out our open graph field?					 *
*	2. Did they fill out Yoast's social field?					 *
*	3. Did they fill out Yoast's SEO field?						 *
*	4. We'll just auto-generate the field from the post.		 *
******************************************************************/

			function sw_open_graph_tags($info) {
			
				// We only modify the Open Graph tags on single blog post pages
				if(is_singular()):
					
					// If Yoast Open Graph is activated, we only output Open Graph tags if the user has filled out at least one field
					// Then we'll work along with Yoast to make sure all fields get filled properly
					if(defined('WPSEO_VERSION')):
						global $wpseo_og;
						$yoast_og_setting = has_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ));
					else:
						$yoast_og_setting = false;
					endif;
						
					if(
						(isset($info['title']) && $info['title']) || 
						(isset($info['description']) && $info['description']) || 
						(isset($info['imageURL']) && $info['imageURL']) ||
						!$yoast_og_setting
					):
					
						/*****************************************************************
						*                                                                *
						*     YOAST SEO: It rocks, so let's coordinate with it	         *
						*                                                                *
						******************************************************************/
					
						// Check if Yoast Exists so we can coordinate output with their plugin accordingly
						if (defined('WPSEO_VERSION')):

							// Collect their Social Descriptions as backups if they're not defined in ours
							$yoast_og_title 		= get_post_meta( $info['postID'] , '_yoast_wpseo_opengraph-title' , true );
							$yoast_og_description 	= get_post_meta( $info['postID'] , '_yoast_wpseo_opengraph-description' , true );
							$yoast_og_image 		= get_post_meta( $info['postID'] , '_yoast_wpseo_opengraph-image' , true );

							// Collect their SEO fields as 3rd string backups in case we need them
							$yoast_seo_title		= get_post_meta( $info['postID'] , '_yoast_wpseo_title' , true );
							$yoast_seo_description	= get_post_meta( $info['postID'] , '_yoast_wpseo_metadesc' , true );

							// Cancel their output if ours have been defined so we don't have two sets of tags
							global $wpseo_og;
							remove_action( 'wpseo_head', array( $wpseo_og, 'opengraph' ), 30 );
							
							// Fetch the WPSEO_SOCIAL Values
							$wpseo_social = get_option( 'wpseo_social' );
							
						endif;					
						
						// Add all our Open Graph Tags to the Return Header Output
						$info['header_output'] .= PHP_EOL .'<meta property="og:type" content="article" /> ';

						/*****************************************************************
						*                                                                *
						*     JETPACK: If ours are enabled, disable theirs		         *
						*                                                                *
						******************************************************************/
						
						if ( class_exists( 'JetPack' ) ) :
							add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
							add_filter( 'jetpack_enable_open_graph', '__return_false', 99 );
						endif;
						
						/*****************************************************************
						*                                                                *
						*     OPEN GRAPH TITLE									         *
						*                                                                *
						******************************************************************/
						
						// Open Graph Title: Create an open graph title meta tag
						if($info['title']):
							
							// If the user defined an social media title, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:title" content="'.$info['title'].'" />';
							
						elseif(isset($yoast_og_title) && $yoast_og_title):	
						
							// If the user defined an title over in Yoast, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:title" content="'.$yoast_og_title.'" />';
						
						elseif(isset($yoast_seo_title) && $yoast_seo_title):
						
							// If the user defined an title over in Yoast, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:title" content="'.$yoast_seo_title.'" />';
						
						else:
						
							// If nothing else is defined, let's use the post title
							$info['header_output'] .= PHP_EOL .'<meta property="og:title" content="'.convert_smart_quotes(htmlspecialchars_decode(get_the_title())).'" />';
							
						endif;
						
						/*****************************************************************
						*                                                                *
						*     OPEN GRAPH DESCRIPTION							         *
						*                                                                *
						******************************************************************/						
						
						// Open Graph Description: Create an open graph description meta tag
						if($info['description']):
							
							// If the user defined an social media description, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:description" content="'.$info['description'].'" />';
							
						elseif(isset($yoast_og_description) && $yoast_og_description):	
						
							// If the user defined an description over in Yoast, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:description" content="'.$yoast_og_description.'" />';
						
						elseif(isset($yoast_seo_description) && $yoast_seo_description):
						
							// If the user defined an description over in Yoast, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:description" content="'.$yoast_seo_description.'" />';
						
						else:
						
							// If nothing else is defined, let's use the post excerpt
							$info['header_output'] .= PHP_EOL .'<meta property="og:description" content="'.convert_smart_quotes(htmlspecialchars_decode(sw_get_excerpt_by_id($info['postID']))).'" />';
							
						endif;
						
						/*****************************************************************
						*                                                                *
						*     OPEN GRAPH IMAGE									         *
						*                                                                *
						******************************************************************/

						// Open Graph Image: Create an open graph image meta tag
						if($info['imageURL']):
							
							// If the user defined an image, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:image" content="'.$info['imageURL'].'" />';
							
						elseif(isset($yoast_og_image) && $yoast_og_image):	
						
							// If the user defined an image over in Yoast, let's use it.
							$info['header_output'] .= PHP_EOL .'<meta property="og:image" content="'.$yoast_og_image.'" />';
						
						else:
						
							// If nothing else is defined, let's use the post Thumbnail as long as we have the URL cached
							$og_image = get_post_meta( $info['postID'] , 'sw_open_thumbnail_url' , true );
							if($og_image):
								$info['header_output'] .= PHP_EOL .'<meta property="og:image" content="'.$og_image.'" />';
							endif;

						endif;
						
						/*****************************************************************
						*                                                                *
						*     OPEN GRAPH URL & Site Name						         *
						*                                                                *
						******************************************************************/

						$info['header_output'] .= PHP_EOL .'<meta property="og:url" content="'.get_permalink().'" />';
						$info['header_output'] .= PHP_EOL .'<meta property="og:site_name" content="'.get_bloginfo('name').'" />';
						
						/*****************************************************************
						*                                                                *
						*     OPEN GRAPH AUTHOR									         *
						*                                                                *
						******************************************************************/

						// Add the Facebook Author URL
						if( get_the_author_meta ( 'sw_fb_author' , sw_get_author($info['postID'])) ):
						
							// Output the Facebook Author URL
							$facebook_author = get_the_author_meta ( 'sw_fb_author' , sw_get_author($info['postID']));
							$info['header_output'] .= PHP_EOL .'<meta property="article:author" content="'.$facebook_author.'" />';
						
						elseif( get_the_author_meta ( 'facebook' , sw_get_author($info['postID'])) && defined('WPSEO_VERSION')):

							// Output the Facebook Author URL
							$facebook_author = get_the_author_meta ( 'facebook' , sw_get_author($info['postID']));
							$info['header_output'] .= PHP_EOL .'<meta property="article:author" content="'.$facebook_author.'" />';
						
						endif;
						
						/*****************************************************************
						*                                                                *
						*     OPEN GRAPH PUBLISHER								         *
						*                                                                *
						******************************************************************/
						
						// If they have a Facebook Publisher URL in our settings...
						if(isset($info['sw_user_options']['facebookPublisherUrl']) && $info['sw_user_options']['facebookPublisherUrl'] != ''):
						
							// Output the Publisher URL
							$info['header_output'] .= PHP_EOL .'<meta property="article:publisher" content="'.$info['sw_user_options']['facebookPublisherUrl'].'" />';
						
						// If they have a Facebook Publisher URL in Yoast's settings...
						elseif(isset($wpseo_social) && isset($wpseo_social['facebook_site']) && $wpseo_social['facebook_site'] != ''):
						
							// Output the Publisher URL
							$info['header_output'] .= PHP_EOL .'<meta property="article:publisher" content="'.$wpseo_social['facebook_site'].'" />';	
						endif;
						
						$info['header_output'] .= PHP_EOL .'<meta property="article:published_time" content="'.get_post_time('c').'" />';
						$info['header_output'] .= PHP_EOL .'<meta property="article:modified_time" content="'.get_post_modified_time('c').'" />';
						$info['header_output'] .= PHP_EOL .'<meta property="og:updated_time" content="'.get_post_modified_time('c').'" />';
						
						/*****************************************************************
						*                                                                *
						*     OPEN GRAPH APP ID									         *
						*                                                                *
						******************************************************************/
						
						// If the Facebook APP ID is in our settings...
						if(isset($info['sw_user_options']['facebookAppID']) && $info['sw_user_options']['facebookAppID'] != ''):
							
							// Output the Facebook APP ID
							$info['header_output'] .= PHP_EOL .'<meta property="fb:app_id" content="'.$info['sw_user_options']['facebookAppID'].'" />';
						
						// If the Facebook APP ID is set in Yoast's settings...
						elseif(isset($wpseo_social) && isset($wpseo_social['fbadminapp']) && $wpseo_social['fbadminapp'] != ''):
						
							// Output the Facebook APP ID
							$info['header_output'] .= PHP_EOL .'<meta property="fb:app_id" content="'.$wpseo_social['fbadminapp'].'" />';	
						
						else:
						
							// Output the Facebook APP ID
							$info['header_output'] .= PHP_EOL .'<meta property="fb:app_id" content="529576650555031" />';
						
						endif;

					endif;
				endif;
				
				// Return the variable containing our information for the meta tags
				return $info;
				
			}

/*****************************************************************
*                                                                *
*   TWITTER CARDS		 							             *
*                                                                *
*	Dev Notes: If the user has Twitter cards turned on, we		 *
*	need to generate them, but we also like Yoast so we'll		 *
*	pay attention to their settings as well. Here's the order	 *
*	of preference for each field:								 *
*	1. Did the user fill out the Social Media field?			 *
*	2. Did the user fill out the Yoast Twitter Field?			 *
*	3. Did the user fill out the Yoast SEO field?				 *
*	4. We'll auto generate something logical from the post.		 *
*																 *
******************************************************************/

			function sw_add_twitter_card($info) {
				if(is_singular()):
					// Check if Twitter Cards are Activated
					if($info['sw_user_options']['sw_twitter_card']):
					
						/*****************************************************************
						*                                                                *
						*     YOAST SEO: It rocks, so let's coordinate with it	         *
						*                                                                *
						******************************************************************/
					
						// Check if Yoast Exists so we can coordinate output with their plugin accordingly
						if (defined('WPSEO_VERSION')):

							// Collect their Social Descriptions as backups if they're not defined in ours
							$yoast_twitter_title 		= get_post_meta( $info['postID'] , '_yoast_wpseo_twitter-title' , true );
							$yoast_twitter_description 	= get_post_meta( $info['postID'] , '_yoast_wpseo_twitter-description' , true );
							$yoast_twitter_image 		= get_post_meta( $info['postID'] , '_yoast_wpseo_twitter-image' , true );
						
							// Collect their SEO fields as 3rd string backups in case we need them
							$yoast_seo_title			= get_post_meta( $info['postID'] , '_yoast_wpseo_title' , true );
							$yoast_seo_description		= get_post_meta( $info['postID'] , '_yoast_wpseo_metadesc' , true );
						
							// Cancel their output if ours have been defined so we don't have two sets of tags
							remove_action( 'wpseo_head' , array( 'WPSEO_Twitter' , 'get_instance' ) , 40 );
						
						endif;	
						
						/*****************************************************************
						*                                                                *
						*     JET PACK: If ours are activated, disable theirs	         *
						*                                                                *
						******************************************************************/
						
						if ( class_exists( 'JetPack' ) ) :
						
							add_filter( 'jetpack_disable_twitter_cards', '__return_true', 99 );
							
						endif;				

						/*****************************************************************
						*                                                                *
						*     TWITTER TITLE										         *
						*                                                                *
						******************************************************************/
						
						// If the user defined a Social Media title, use it, otherwise check for Yoast's
						if(!$info['title'] && isset($yoast_twitter_title) && $yoast_twitter_title):
						
							$info['title'] = $yoast_twitter_title;
						
						// If not title has been defined, let's check the SEO description as a 3rd string option
						elseif(!$info['title'] && isset($yoast_seo_title) && $yoast_seo_title):
						
							$info['title'] = $yoast_seo_title;
							
						// If not title has been defined, let's use the post title
						elseif(!$info['title']):
						
							$info['title'] = convert_smart_quotes(htmlspecialchars_decode( get_the_title() ));
							
						endif;
		
						/*****************************************************************
						*                                                                *
						*     TWITTER DESCRIPTION								         *
						*                                                                *
						******************************************************************/
						
						// Open Graph Description
						if(!$info['description'] && isset($yoast_twitter_description) && $yoast_twitter_description):
						
							$info['description'] = $yoast_twitter_description;
						
						// If not title has been defined, let's check the SEO description as a 3rd string option
						elseif(!$info['description'] && isset($yoast_seo_description) && $yoast_seo_description):
						
							$info['description'] = $yoast_seo_description;
						
						// If not, then let's use the excerpt
						elseif(!$info['description']):
						
							$info['description'] = convert_smart_quotes(htmlspecialchars_decode( sw_get_excerpt_by_id( $info['postID'] )) );
						
						endif;
	
						/*****************************************************************
						*                                                                *
						*     TWITTER IMAGE								         *
						*                                                                *
						******************************************************************/
											
						// Open Graph Description
						if(!$info['imageURL'] && isset($yoast_twitter_image) && $yoast_twitter_image):
						
							$info['imageURL'] = $yoast_twitter_image;
						
						else:
						
						// If nothing else is defined, let's use the post Thumbnail as long as we have the URL cached
							$twitter_image = get_post_meta( $info['postID'] , 'sw_open_thumbnail_url' , true );
							if($twitter_image):
								$info['imageURL'] = $twitter_image;
							endif;
												
						endif;

						/*****************************************************************
						*                                                                *
						*     PUT IT ALL TOGETHER						         		 *
						*                                                                *
						******************************************************************/
						
						// Check if we have everything we need for a large image summary card
						if($info['imageURL']):
							$info['header_output'] .= PHP_EOL .'<meta name="twitter:card" content="summary_large_image">';
							$info['header_output'] .= PHP_EOL .'<meta name="twitter:title" content="'.$info['title'].'">';
							$info['header_output'] .= PHP_EOL .'<meta name="twitter:description" content="'.$info['description'].'">';
							$info['header_output'] .= PHP_EOL .'<meta name="twitter:image" content="'.$info['imageURL'].'">';
							if($info['sw_user_options']['twitterID']):
								$info['header_output'] .= PHP_EOL .'<meta name="twitter:site" content="@'.$info['sw_user_options']['twitterID'].'">';
							endif;
							if($info['user_twitter_handle']):
								$info['header_output'] .= PHP_EOL .'<meta name="twitter:creator" content="@'.str_replace('@','',$info['user_twitter_handle']).'">';
							endif;
							
						// Otherwise create a small summary card
						else:
							$info['header_output'] .= PHP_EOL .'<meta name="twitter:card" content="summary">';
							$info['header_output'] .= PHP_EOL .'<meta name="twitter:title" content="'.str_replace('"','\'',$info['title']).'">';
							$info['header_output'] .= PHP_EOL .'<meta name="twitter:description" content="'.str_replace('"','\'',$info['description']).'">';
							if($info['sw_user_options']['twitterID']):
								$info['header_output'] .= PHP_EOL .'<meta name="twitter:site" content="@'.$info['sw_user_options']['twitterID'].'">';
							endif;
							if($info['user_twitter_handle']):
								$info['header_output'] .= PHP_EOL .'<meta name="twitter:creator" content="@'.str_replace('@','',$info['user_twitter_handle']).'">';
							endif;
						endif;
							
					endif;
				endif;
				return $info;
			}

/*****************************************************************
*                                                                *
*          Frame Buster 							             *
*                                                                *
******************************************************************/

		function sw_frame_buster($info) {
			if($info['sw_user_options']['sniplyBuster'] == true):
				$info['header_output'] .= PHP_EOL.'<script type="text/javascript">function parentIsEvil() { var html = null; try { var doc = top.location.pathname; } catch(err){ }; if(typeof doc === "undefined") { return true } else { return false }; }; if (parentIsEvil()) { top.location = self.location.href; };var url = "'.get_permalink().'";if(url.indexOf("stfi.re") != -1) { var canonical = ""; var links = document.getElementsByTagName("link"); for (var i = 0; i < links.length; i ++) { if (links[i].getAttribute("rel") === "canonical") { canonical = links[i].getAttribute("href")}}; canonical = canonical.replace("?sfr=1", "");top.location = canonical; console.log(canonical);};</script>';
			endif;
			return $info;
		}
 
/*****************************************************************
*                                                                *
*          CUSTOM COLORS 							             *
*                                                                *
******************************************************************/
	
		function sw_output_custom_color($info) {
			if($info['sw_user_options']['dColorSet'] == 'customColor' || $info['sw_user_options']['iColorSet'] == 'customColor' || $info['sw_user_options']['oColorSet'] == 'customColor'):
				$info['header_output'] .= PHP_EOL.'<style type="text/css">.nc_socialPanel.sw_d_customColor a, html body .nc_socialPanel.sw_i_customColor .nc_tweetContainer:hover a, body .nc_socialPanel.sw_o_customColor:hover a {color:white} .nc_socialPanel.sw_d_customColor .nc_tweetContainer, html body .nc_socialPanel.sw_i_customColor .nc_tweetContainer:hover, body .nc_socialPanel.sw_o_customColor:hover .nc_tweetContainer {background-color:'.$info['sw_user_options']['customColor'].';border:1px solid '.$info['sw_user_options']['customColor'].';} </style>';
			endif;
			
			if($info['sw_user_options']['dColorSet'] == 'ccOutlines' || $info['sw_user_options']['iColorSet'] == 'ccOutlines' || $info['sw_user_options']['oColorSet'] == 'ccOutlines'):
				$info['header_output'] .= PHP_EOL.'<style type="text/css">.nc_socialPanel.sw_d_ccOutlines a, html body .nc_socialPanel.sw_i_ccOutlines .nc_tweetContainer:hover a, body .nc_socialPanel.sw_o_ccOutlines:hover a { color:'.$info['sw_user_options']['customColor'].'; }
.nc_socialPanel.sw_d_ccOutlines .nc_tweetContainer, html body .nc_socialPanel.sw_i_ccOutlines .nc_tweetContainer:hover, body .nc_socialPanel.sw_o_ccOutlines:hover .nc_tweetContainer { background:transparent; border:1px solid '.$info['sw_user_options']['customColor'].'; } </style>';
				
			endif;
			return $info;			
		}

/*****************************************************************
*                                                                *
*          CACHE REBUILD TRIGGER					             *
*                                                                *
******************************************************************/	
function sw_output_cache_trigger($info) {
	if(is_singular() && sw_is_cache_fresh( get_the_ID() , true ) == false && $info['sw_user_options']['cacheMethod'] != 'legacy'):
		$url = get_permalink();
		if(strpos($url, '?') === false) { $url = $url.'?sw_cache=rebuild'; } else { $url = $url.'&sw_cache=rebuild'; };
		$info['header_output'] .= PHP_EOL.'<script type="text/javascript">document.addEventListener("DOMContentLoaded", function(event) { jQuery.get("'. $url .'"); });</script>';
	endif;
	return $info;
}
		
/*****************************************************************
*                                                                *
*          ICON FONT CSS							             *
*                                                                *
******************************************************************/	
function sw_output_font_css($info=array()) {
	if(is_admin()):
	
		// Echo it if we're using the Admin Head Hook
		echo '<style>@font-face {font-family: "sw-icon-font";src:url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.eot");src:url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.eot?#iefix") format("embedded-opentype"),url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.woff") format("woff"),
    url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.ttf") format("truetype"),url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.svg#1445203416") format("svg");font-weight: normal;font-style: normal;}</style>';
	else:
	
		// Add it to our array if we're using the frontend Head Hook
		$info['header_output'] .= PHP_EOL.'<style>@font-face {font-family: "sw-icon-font";src:url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.eot");src:url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.eot?#iefix") format("embedded-opentype"),url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.woff") format("woff"), url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.ttf") format("truetype"),url("'.SW_PLUGIN_DIR.'/fonts/sw-icon-font.svg#1445203416") format("svg");font-weight: normal;font-style: normal;}</style>';

		return $info;
	endif;
}