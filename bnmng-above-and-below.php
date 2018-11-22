<?php
/**
Plugin Name: Above and Below
Plugin URL: http://aboveandbelow.bnmng.com
Description: Add text to displayed above and below post content
Version: 1.1
Author: Benjamin Goldberg
Author URI: https://bnmng.com
Text Domain: bnmng-above-and-below
Licence: GPL2
 */

/**
 * Adds text above and below post content based on options
 *
 * @param string $content The content of the post to have text added.
 *
 * @return string $content The content with the text added.
 */
function bnmng_above_and_below( $content ) {

	$post             = get_post();
	$option_name      = 'bnmng_above_and_below';
	$options          = get_option( $option_name );
	$add_to_beginning = '';
	$add_to_end       = '';

	foreach ( $options['instances'] as $option ) {

		if ( 1 === $option['singular'] && ! is_singular() ) {
			continue;
		} elseif ( 2 === $option['singular'] && is_singular() ) {
			continue;
		}

		if ( ! ( $post->post_type === $option['post_type'] ) ) {
			continue;
		}

		$taxonomy_names = get_post_taxonomies( $post );

		foreach ( $taxonomy_names as $taxonomy_name ) {
			$opt_term_ids = $option['taxonomies'][ $taxonomy_name ];
			if ( count( $opt_term_ids ) ) {
				$post_terms = get_the_terms( $post, $taxonomy_name );
				if ( ! count( $post_terms ) ) {
					continue;
				} else {
					$post_term_ids = array_column( $post_terms, 'term_id' );
					foreach ( $opt_term_ids as $opt_term_id ) {
						if ( ! in_array( $opt_term_id, $post_term_ids, true ) ) {
							continue 3;
						}
					}
				}
			}
		}

		if ( post_type_supports( $post->post_type, 'author' ) ) {
			if ( $option['author'] > 0 ) {
				if ( ! ( get_the_author_meta( 'ID' ) === $option['author'] ) ) {
					continue;
				}
			}
		}

		$add_to_beginning .= ( stripslashes( $option['at_beginning'] ) );
		$add_to_end        = ( stripslashes( $option['at_end'] ) ) . $add_to_end;
		if ( $option['wpautop'] ) {
			$add_to_beginning = wpautop( $add_to_beginning );
			$add_to_end       = wpautop( $add_to_end );
		}
	}
	$content = $add_to_beginning . $content . $add_to_end;
	return $content;
}

add_filter( 'the_content', 'bnmng_above_and_below' );

/**
 * Add the options page using functin bnmng_above_and_below_options
 */
function bnmng_above_and_below_menu() {
		add_options_page( 'Above and Below Options', 'Above and Below', 'manage_options', 'bnmng-above-and-below', 'bnmng_above_and_below_options' );
}
add_action( 'admin_menu', 'bnmng_above_and_below_menu' );

/**
 * Calls the functions to save and display options
 *
 */
function bnmng_above_and_below_options() {

	bnmng_above_and_below_save_options();
	bnmng_above_and_below_display_options();

}

function bnmng_above_and_below_save_options() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$option_name = 'bnmng_above_and_below';
	$taxonomies  = array();
	$all_authors = get_users();

	bnmng_echo ( $_POST );

	if ( ! empty( $_POST ) && check_admin_referer( 'bnmng_above_and_below', 'bnmng_above_and_below_nonce' ) ) {
		$options = [];
		$save_instances_lap = 0;

		foreach( $_POST[ $option_name ]['instances'] as &$posted_instance ) {

			$posted_post_type = sanitize_key( wp_unslash( $posted_instance['post_type'] ) );
			if( $posted_instance['post_type'] === $posted_post_type ) {

				$options['instances'][ $save_instances_lap ]['post_type'] = $posted_post_type;

				if ( isset( $posted_instance['taxonomies'] ) ) {
					foreach ( $posted_instance['taxonomies'] as $posted_taxonomy_key => $posted_taxonomy_values ) {
						$sanitized_taxonomy_key = sanitize_key( $posted_taxonomy_key );
						foreach( $posted_taxonomy_values as $term ) {
							$options['instances'][ $save_instances_lap ]['taxonomies'][ $sanitized_taxonomy_key ][] = intval( $term );
						}
					}
				}

				if ( isset( $posted_instance['author'] ) ) {
					$options['instances'][ $save_instances_lap ]['author'] = intval( $posted_instance['author'] );
				}

				if ( isset( $posted_instance['singular'] ) ) {
					$options['instances'][ $save_instances_lap ]['singular'] = intval( $posted_instance['singular'] );
				}

				if ( isset( $posted_instance['at_beginning'] ) ) {
					$options['instances'][ $save_instances_lap ]['at_beginning'] = wp_kses_post( $posted_instance['at_beginning'] ); 
				}

				if ( isset( $posted_instance['at_end'] ) ) {
					$options['instances'][ $save_instances_lap ]['at_end'] = wp_kses_post( $posted_instance['at_end'] );
				}

				if ( isset( $posted_instance['wpautop'] ) && $posted_instance['wpautop'] ) {
					$options['instances'][ $save_instances_lap ]['wpautop'] = "on";
				}

				$save_instances_lap++;
			}
		}

		if ( $_POST[ $option_name ]['new_instance_post_type'] ) {

			$new_instance_post_type = '';
			if ( '+' === $_POST[ $option_name ]['new_instance_post_type'] ) {
				if( isset( $_POST[ $option_name ]['new_post_type'] ) && $_POST[ $option_name ]['new_post_type'] > '' ) {
					$new_instance_post_type = sanitize_key( $_POST[ $option_name ]['new_post_type'] );
				}
			} else {
				$new_instance_post_type = sanitize_key( $_POST[ $option_name ]['new_instance_post_type'] );
			}
			if ( $new_instance_post_type > '' ) {
				$options['instances'][ $save_instances_lap ]['post_type']=$new_instance_post_type;
				if ( post_type_supports( $new_instance_post_type, 'author' ) ) {
					$options['instances'][ $save_instances_lap ]['author'] = 0;
				}
				$options['instances'][ $save_instances_lap ]['singular'] = '1';
				$options['instances'][ $save_instances_lap ]['at_beginning'] = '';
				$options['instances'][ $save_instances_lap ]['at_end'] = '';
			}
		}
		update_option( $option_name . '_' . wp_get_current_user()->user_login, $options );
		update_option( $option_name,  $options );
	}
}

function bnmng_above_and_below_display_options() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$option_name      = 'bnmng_above_and_below';

	$intro  = '<p>';
	$intro .= __( 'This plugin adds text to the beginning and end of a post when the post is displayed. It does not alter the post in the database. ', 'bnmng-above-and-below' );
	$intro .= '</p>' . "\n";
	$intro .= '<p>';
	$intro .= __( 'HTML and shortcodes may be used.  Tags opened above post content can be closed below. ', 'bnmng-above-and-below' );
	$intro .= ' </p>';

	$instance_label   = 'Instance %1$d';
	$show_help_label  = __( 'Show Help', 'bnmng-above-and-below' );
	$up_label         = __( 'Up', 'bnmng-above-and-below' );
	$down_label       = __( 'Down', 'bnmng-above-and-below' );
	$delete_label     = __( 'Delete', 'bnmng-above-and-below' );
	$post_type_label  = __( 'Post Type', 'bnmng-above-and-below' );
	$post_type_help   = __( 'To add a new instance, select the appropriate post type and click "Save Changes"  ', 'bnmng-above-and-below' );
	$post_type_help  .= __( 'This plugin should work as expected for posts and pages, and may work for some types of posts that you add. ', 'bnmng-above-and-below' );
	$singular_label   = __( 'Singular/List View', 'bnmng-above-and-below' );
	$singular_help    = __( 'Whether to display the added text while the post is in singular view only, list view only, or either.  ', 'bnmng-above-and-below' );
	$singular_help   .= __( "This option won't be relavant for all post types.  ", 'bnmng-above-and-below' );
	$singular_options = [
		[
			'value' => '1',
			'name'  => __( 'Single Only', 'bnmng-above-and-below' ),
		],
		[
			'value' => '2',
			'name'  => __( 'List Only', 'bnmng-above-and-below' ),
		],
		[
			'value' => '0',
			'name'  => __( 'Single or List', 'bnmng-above-and-below' ),
		],
	];
	$taxonomies_label       = '%1$s';
	$taxonomies_help        = __( 'The %1$s of posts to have the text added. ', 'bnmng-above-and-below' );
	$taxonomies_help       .= __( 'If more than one of the %1$s is selected, the post will have to be in <em>all</em> of the %1$s, <em>not any</em> of the %1$s for this instance to take effect.  ', 'bnmng-above-and-below' );
	$author_label           = __( 'Author', 'bnmng-above-and-below' );
	$author_help            = __( 'The author of posts to have the text added.', 'bnmng-above-and-below' );
	$above_label            = __( 'Add Above Post Content', 'bnmng-above-and-below' );
	$above_help             = __( 'The text to add above the content.  Tags opened above content can be closed below.', 'bnmng-above-and-below' );
	$below_label            = __( 'Add Below Post Content', 'bnmng-above-and-below' );
	$below_help             = __( 'The text to add at below the content. ', 'bnmng-above-and-below' );
	$wpautop_label          = __( 'Auto &lt;p>', 'bnmng-above-and-below' );
	$wpautop_help           = __( 'Add paragraphs and breaks based on end-of-lines', 'bnmng-above-and-below' );
	$new_instance_label     = __( 'Add a new instance', 'bnmng-above-and-below' );
	$new_instance_help      = __( 'To add a new instance, check this box and click "Save Changes".  ', 'bnmng-above-and-below' );
	$new_instance_help      = __( 'To add for a post type other than "post", select the post type below before clicking "Save Changes".  ', 'bnmng-above-and-below' );
	$new_instance_help      = __( 'To add for a post type not in the list, select "New Post Type: " and type in the name of the new post type. ', 'bnmng-above-and-below' );
	$controlname_pat        = $option_name . '[instances][%1$d][%2$s]';
	$controlid_pat          = $option_name . '_%1$d_%2$s';
	$multicontrolname_pat   = $option_name . '[instances][%1$d][%2$s][%3$s][]';
	$multicontrolid_pat     = $option_name . '_%1$d_%2$s_%3$s';
	$global_controlname_pat = $option_name . '[%1$s]';
	$global_controlid_pat   = $option_name . '_%1$s';

	$available_post_types = [ 'post', 'page' ];
	$taxonomies           = array();
	$all_authors          = get_users();

	echo "\n";
	echo '<div class = "wrap">', "\n";
	echo '  <div class="bnmng-above-and-below-intro">', $intro, '</div>', "\n";
	echo '  <form method = "POST" action="">', "\n";
	echo '   ', wp_nonce_field( 'bnmng_above_and_below', 'bnmng_above_and_below_nonce', true, false ), "\n";

	$options = get_option( $option_name );

	$saved_instances_sum = count( $options['instances'] ) ;

	for ( $saved_instances_lap = 0; $saved_instances_lap < $saved_instances_sum; $saved_instances_lap++ ) {
		if ( ! in_array( $options['instances'][ $saved_instances_lap ]['post_type'], $available_post_types, true ) ) {
			$available_post_types[] = $options['instances'][ $saved_instances_lap ]['post_type'];
		}
		echo '    <div class="instance_wrapper">', "\n";
		echo '      <div class="instance_header">', "\n";
		echo '       <div class="instance_label">', "\n";
		echo '         ', sprintf( $instance_label, ( $saved_instances_lap + 1 ) ), "\n";
		echo '       </div>', "\n";
		echo '       <div class="instance_buttons">', "\n";
		if ( $saved_instances_lap > 0 ) {
			echo '        <button type="button" name="move_up" data-key="' . $saved_instances_lap . '" >', $up_label, '</button>', "\n";
		}
		if ( $saved_instances_lap < ( $saved_instances_sum - 1 ) ) {
			echo '        <button type="button" name="move_down" data-key="' . $saved_instances_lap . '" >', $down_label, '</button>', "\n";
		}
		echo '        <button type="button" name="delete" data-key="' . $saved_instances_lap . '" >', $delete_label, '</button>', "\n";
		echo '      </div>', "\n";
		echo '      <div id="div_instance_', $saved_instances_lap, '">', "\n";
		echo '        <table class="form-table bnmng-above-and-below">', "\n";
		echo '          <tr>', "\n";
		echo '            <th>', $post_type_label, '</th>', "\n";
		echo '            <td>', "\n";
		echo '              <div>';
		echo '      		  ', $options['instances'][ $saved_instances_lap ]['post_type'], "\n";
		echo '      		  ', '<input type="hidden" id="', sprintf( $controlid_pat, $saved_instances_lap, 'post_type' ), '" name="', sprintf( $controlname_pat, $saved_instances_lap, 'post_type' ), '" value="', $options['instances'][ $saved_instances_lap ]['post_type'], '">',  "\n";
		echo '              </div>', "\n";
		echo '            </td>', "\n";
		echo '          </tr>', "\n";

		echo '          <tr>', "\n";
		echo '            <th>', $singular_label, '</th>', "\n";
		echo '            <td>', "\n";
		echo '              <div>', "\n";
		echo '                <select id="', sprintf( $controlid_pat, $saved_instances_lap, 'singular' ),  '" name="', sprintf( $controlname_pat, $saved_instances_lap, 'singular' ), '">', "\n";
		foreach ( $singular_options AS $singular_option ) {
			echo '                  <option value="' . $singular_option['value'] . '"';
			if ( $singular_option['  value']  === $options['instances'][ $saved_instances_lap ]['singular'] ) {
				echo '   selected="selected" ';
			}
			echo '   >', $singular_option['name'], '</option>', "\n";
		}
		echo '                </select>', "\n";
		echo '              </div>', "\n";
		echo '              <div class="bnmng-above-and-below-help">', $singular_help, '</div>', "\n";
		echo '            </td>', "\n";
		echo '          </tr>', "\n";

		if ( ! isset( $taxonomies[ $options['instances'][ $saved_instances_lap ]['post_type'] ] ) ) {
			$taxonomies[ $options['instances'][ $saved_instances_lap ]['post_type'] ] = get_object_taxonomies( $options['instances'][ $saved_instances_lap ]['post_type'], 'objects' ) ;
			foreach ( $taxonomies[ $options['instances'][ $saved_instances_lap ]['post_type'] ] as $taxonomy ) {
				$terms[ $taxonomy->name ]  = bnmng_assign_taxonomy_lineage( get_terms( [ 'taxonomy' => $taxonomy->name, 'hide_empty' => 0 ] ) );
			}
		}
		foreach ( $taxonomies[ $options['instances'][ $saved_instances_lap ]['post_type'] ] AS $taxonomy ) {
			if ( count( $terms[ $taxonomy->name ] ) ) {
				echo '          <tr>', "\n";
				echo '            <th>', $taxonomy->label, '</th>', "\n";
				echo '            <td>', "\n";
				echo '              <div>', "\n";
				echo '                <select id="', sprintf( $multicontrolid_pat, $saved_instances_lap, 'taxonomies',  $taxonomy->name ), '" name="', sprintf( $multicontrolname_pat, $saved_instances_lap, 'taxonomies',  $taxonomy->name ), '" multiple="multiple" size="',  min( count( $terms[ $taxonomy->name ] ), 3 ),  '">', "\n";
				foreach ( $terms[ $taxonomy->name ] as $term ) {
					echo '                  <option value="', $term->term_id , '"';
					if ( in_array( $term->term_id, $options['instances'][ $saved_instances_lap ]['taxonomies'][ $taxonomy->name ], true ) ) {
						echo '   selected="selected" ';
					}
					echo '  >', $term->prefix . $term->name, '</option>', "\n";;
				}
				echo '                </select>', "\n";
				echo '              </div>', "\n";
				echo '              <div class="bnmng-above-and-below-help">', sprintf( $taxonomies_help, $taxonomy->label ), '</div>', "\n";
				echo '            </td>', "\n";
				echo '          </tr>', "\n";
			}
		}

		echo '          <tr>', "\n";
		echo '            <th>', $author_label, '</th>', "\n";
		echo '            <td>', "\n";
		echo '              <div>', "\n";
		echo '                <select id="', sprintf( $controlid_pat, $saved_instances_lap, 'author' ), '" name="', sprintf( $controlname_pat, $saved_instances_lap, 'author' ), '" >', "\n";
		echo '                  <option value="0"';
		if ( 0 === $options['instances'][ $saved_instances_lap ]['author'] ) {
			echo '   selected="selected" ';
		}
		echo '  >[any author]</option>', "\n";
		foreach ( $all_authors as $author ) {
			echo '                <option value="', $author->ID, '"';
			if ( $author->ID === $options['instances'][ $saved_instances_lap ]['author'] ) {
				echo '      selected="selected"';
			}
			echo '      >', $author->display_name, '</option>', "\n";
		}
		echo '                </select>', "\n";
		echo '              </div>', "\n";
		echo '              <div class="bnmng-above-and-below-help">', $author_help, '</div>', "\n";
		echo '            </td>', "\n";
		echo '          </tr>', "\n";

		echo '          <tr>', "\n";
		echo '            <th>', $above_label, '</th>', "\n";
		echo '            <td>', "\n";
		echo '              <div>', "\n";
		echo '                <textarea id="', sprintf( $controlid_pat, $saved_instances_lap, 'at_beginning' ), '" name="', sprintf( $controlname_pat, $saved_instances_lap, 'at_beginning' ), '">', stripslashes( $options['instances'][ $saved_instances_lap ]['at_beginning'] ), '</textarea>', "\n";
		echo '               </div>', "\n";
		echo '              <div class="bnmng-above-and-below-help">', $above_help, '</div>', "\n";
		echo '            </td>', "\n";
		echo '          </tr>', "\n";

		echo '          <tr>', "\n";
		echo '            <th>', $below_label, '</th>', "\n";
		echo '            <td>', "\n";
		echo '              <div>', "\n";
		echo '                <textarea id="', sprintf( $controlid_pat, $saved_instances_lap, 'at_end' ), '" name="', sprintf( $controlname_pat, $saved_instances_lap, 'at_end' ), '">', stripslashes( $options['instances'][ $saved_instances_lap ]['at_end'] ), '</textarea>', "\n";
		echo '              </div>', "\n";
		echo '              <div class="bnmng-above-and-below-help">', $below_help, '</div>', "\n";
		echo '            </td>', "\n";
		echo '          </tr>', "\n";

		echo '          <tr>', "\n";
		echo '            <th>', $wpautop_label, '</th>', "\n";
		echo '            <td>', "\n";
		echo '              <div>', "\n";
		echo '                <input type="checkbox" id="', sprintf( $controlid_pat, $saved_instances_lap, 'wpautop' ), '" name="', sprintf( $controlname_pat, $saved_instances_lap, 'wpautop' ), '" ' ;
		if ( $options['instances'][ $saved_instances_lap ]['wpautop'] ) {
			echo '   checked="checked" ';
		}
		echo '  >', "\n";
		echo '              </div>', "\n";
		echo '              <div class="bnmng-above-and-below-help">', $wpautop_help, '</div>', "\n";
		echo '            </td>', "\n";
		echo '          </tr>', "\n";


		echo '        </table>', "\n";
		echo '      </div>', "\n";
		echo '    </div>', "\n";
	}

	/*   'new instance' form */
	echo '    <div id="div_add_instance">', "\n";
	echo '      <table class="form-table bnmng-above-and-below">', "\n";
	echo '       <tr>', "\n";
	echo '         <th>Add a New Instance</th>', "\n";
	echo '         <td>', "\n";
	echo '           <table class="form-table bnmng-above-and-below">', "\n";
	echo '             <tr>', "\n";
	echo '               <td>none</td>', "\n";
	echo '               <td>', "\n";
	echo '                  <input type="radio" id="', sprintf( $global_controlid_pat, 'new_instance_post_type_none' ), '" name="', sprintf( $global_controlname_pat, 'new_instance_post_type' ), '" value=""';
	if ( $saved_instances_lap > 0 ) {
		echo ' checked="checked" ';
	}
	echo '>', "\n";
	echo '               </td>', "\n";
	echo '               <td>', __( "Don't add", 'bnmng-above-and-below' ), '</td>', "\n";
	echo '             </tr>', "\n";
	foreach ( $available_post_types as $post_type ) {
		echo '             <tr>', "\n";
		echo '               <td>', $post_type, '</td>', "\n";
		echo '               <td>', "\n";
		echo '                 <input type="radio" id="', sprintf( $global_controlid_pat, 'new_instance_post_type_', $post_type ), '" name="' . sprintf( $global_controlname_pat, 'new_instance_post_type' ), '" value="', $post_type,  '"';
		if ( ! ( 0 < $saved_instances_lap ) && 'post' === $post_type) {
			echo ' checked="checked" ';
		}
		echo '>', "\n";
		echo '               </td>', "\n";
		echo '               <td></td>',  "\n";
		echo '             </tr>', "\n";
	}
	echo '             <tr>', "\n";
	echo '               <td>new</td>', "\n";
	echo '               <td>', "\n";
	echo '                 <input type="radio" id="', sprintf( $global_controlid_pat, 'new_instance_post_type_new' ), '" name="', sprintf( $global_controlname_pat, 'new_instance_post_type' ), '" value="+">', "\n";
	echo '               </td>', "\n";
	echo '               <td>', "\n";
	echo '                 <input id="' . sprintf( $global_controlid_pat, 'new_post_type' ), '" name="', sprintf( $global_controlname_pat, 'new_post_type' ), '">', "\n";
	echo '               </td>', "\n";
	echo '             </tr>', "\n";
	echo '           </table>', "\n";
	echo '           <div class="bnmng-above-and-below-help">', $post_type_help, '</div>', "\n";
	echo '         </td>', "\n";
	echo '       </tr>', "\n";
	echo '      </table>', "\n";
	echo '    </div>', "\n";

	echo '    <div id="div_submit">', "\n";
	echo '      ', get_submit_button(), "\n";
	echo '    </div>', "\n";
	echo '  </form>', "\n";
	echo '</div>', "\n";
}


add_action( 'admin_head-settings_page_bnmng-above-and-below', 'bnmng_admin_above_and_below_style' );

function bnmng_admin_above_and_below_style() {
	?>
<style type="text/css">
	table.bnmng-above-and-below {
		border: 1px solid black;
	}
	table.bnmng-above-and-below th {
		padding-left: 1em;
	}
	table.bnmng-above-and-below table {
		border: none;
	}
	table.bnmng-above-and-below table.bnmng-above-and-below td {
		padding: 0 1px .5px 1px;
	}
	table.bnmng-above-and-below textarea {
		width:100%;
	}
</style>
	<?php
}
add_action( 'admin_footer-settings_page_bnmng-above-and-below', 'bnmng_admin_above_and_below_script' );

function bnmng_admin_above_and_below_script() {
	?>
<script type="text/javascript">
	function move( direction, key ) {

		direction = parseInt( direction );
		key = parseInt( key );
		
		var tempDiv = document.createElement("div");

		var thisDiv = document.getElementById( "div_instance_" + key );
		var theseChildren = thisDiv.children;
		for ( var i = 0; i < theseChildren.length; i++ ) {
			if ( theseChildren[ i ].hasAttribute( "name" ) ) {
				theseChildren[ i ].name = theseChildren[ i ].name.replace( "[" + key + "]", "[" + ( key + direction ) + "]" );
			}
			var theseDescendents = theseChildren[ i ].querySelectorAll("*");
			for ( j = 0; j < theseDescendents.length; j++ ) {
				if ( theseDescendents[ j ].hasAttribute( "name" ) ) {
					theseDescendents[ j ].name = theseDescendents[ j ].name.replace( "[" + key + "]", "[" + ( key + direction ) + "]" );
				}
			}
			tempDiv.appendChild( theseChildren[ i ] );
		}

		var thatDiv = document.getElementById( "div_instance_" + ( key + direction ) );
		var thoseChildren = thatDiv.children;
		for ( var i = 0; i < thoseChildren.length; i++ ) {
			if ( thoseChildren[ i ].hasAttribute( "name" ) ) {
				thoseChildren[ i ].name = thoseChildren[ i ].name.replace( "[" + ( key + direction ) + "]", "[" + key + "]" );
			}
			var thoseDescendents = thoseChildren[ i ].querySelectorAll("*");
			for ( j = 0; j < thoseDescendents.length; j++ ) {
				if ( thoseDescendents[ j ].hasAttribute( "name" ) ) {
					thoseDescendents[ j ].name = thoseDescendents[ j ].name.replace( "[" + ( key + direction ) + "]", "[" + key + "]" );
				}
			}
			thisDiv.appendChild( thoseChildren[ i ] );
		}

		var theseChildren = tempDiv.children;
		for ( var i = 0; i < theseChildren.length; i++ ) {
			thatDiv.appendChild( theseChildren[ i ] );
		}

	}
	function deleteInstance( key ) {

		var eachKey = parseInt( key );

		var thisDiv = document.getElementById( "div_instance_" + key );
		var thatDiv = document.getElementById( "div_instance_" + ( key + 1 ) );

		while ( thatDiv !== null ) {
			theseChildren = thisDiv.children;
			for ( var i = 0; i < theseChildren.length; i++ ) {
				theseChildren[ i ].remove();
			}
			var thoseChildren = thatDiv.children;
			for ( var i = 0; i < thoseChildren.length; i++ ) {
				if ( thoseChildren[ i ].hasAttribute( "name" ) ) {
					thoseChildren[ i ].name = thoseChildren[ i ].name.replace( "[" + ( key + 1 ) + "]", "[" + key + "]" );
				}
				var thoseDescendents = thoseChildren[ i ].querySelectorAll("*");
				for ( j = 0; j < thoseDescendents.length; j++ ) {
					if ( thoseDescendents[ j ].hasAttribute( "name" ) ) {
						thoseDescendents[ j ].name = thoseDescendents[ j ].name.replace( "[" + ( key + 1 ) + "]", "[" + key + "]" );
					}
				}
				thisDiv.appendChild( thoseChildren[ i ] );
			}
			key++;
		}
		var downmovers = document.getElementsByName("move_down");
		if( 0 < downmovers.length ) {
			 downmovers[ downmovers.length - 1 ].remove();
		}
		
		thisDiv.parentNode.remove();
	}

	var upmovers = document.getElementsByName("move_up");
	for ( var i = 0; i < upmovers.length; i++ ) {
		upmovers[ i ].addEventListener( "click", function( event ) {
				move( -1, this.dataset.key );
		} );
	}
	var downmovers = document.getElementsByName("move_down");
	for ( var i = 0; i < downmovers.length; i++ ) {
		downmovers[ i ].addEventListener( "click", function( event ) {
				move( 1, this.dataset.key );
		} );
	}

	var deleters = document.getElementsByName("delete");
	for ( var i = 0; i < deleters.length; i++ ) {
		deleters[ i ].addEventListener( "click", function( event ) {
				deleteInstance( this.dataset.key );
		} );
	}

	function checkNewPostTypeOption() {
		if ( document.getElementById( "bnmng_above_and_below_new_post_type" ).value > "" ) {
			document.getElementById( "bnmng_above_and_below_new_instance_post_type_new" ).checked=true;
		}
	}

	document.getElementById( "bnmng_above_and_below_new_post_type").addEventListener( "click", function() {
		document.getElementById( "bnmng_above_and_below_new_instance_post_type_new" ).checked=true;
	} ) ; 

	document.getElementById( "bnmng_above_and_below_new_post_type").addEventListener( "keydown", function() { 
		document.getElementById( "bnmng_above_and_below_new_instance_post_type_new" ).checked=true;
	} ) ;
</script>
	<?php
}

/*
 * Order terms of a taxonomy by lineage and assign a prefix field
 * of dashes ( or other characters if chosen ) to provide indentation.
 *
 * @param array $terms The terms including in the taxonomy
 *
 * @param string $placeholder the string to be used to build the prefix, which
 * can be used to indent the name of the term under the name of its parent
 *
 * @return array A copy of the terms in order by lineage, and each term with an added field "prefix"
 *
*/
if ( ! function_exists( 'bnmng_assign_taxonomy_lineage' ) ) {
function bnmng_assign_taxonomy_lineage( $terms, $placeholder = '-' ) {

	$terms = array_values( $terms );
	$count_terms = count( $terms );	
	$new_terms = [];

	$count_terms = count( $terms );
	for ( $term_lap = 0; $term_lap < $count_terms; $term_lap++ ) {
		$is_top  = false;
		if ( 0 === $terms[ $term_lap ]->parent ) {
			$is_top = true;
		} else {
			$is_top = true;
			for ( $maybe_parent_lap = 0; $maybe_parent_lap < count ( $terms ); $maybe_parent_lap++ ) {
				if ( $terms[ $maybe_parent_lap ]->term_id === $terms[ $term_lap ]->parent ) {
					$is_top = false;
					break;
				}
			}
			for ( $maybe_parent_lap = 0; $maybe_parent_lap < count ( $new_terms ); $maybe_parent_lap++ ) {
				if ( $new_terms[ $maybe_parent_lap ]->term_id === $terms[ $term_lap ]->parent ) {
					$is_top = false;
					break;
				}
			}
		}
		if ( $is_top ) {
			$terms[ $term_lap ]->prefix = '';
			$new_terms[] = $terms[ $term_lap ];
			unset( $terms[ $term_lap ] );
		}
	}

	while ( count( $terms ) ) {
		for ( $new_term_lap = 0; $new_term_lap < count( $new_terms ); $new_term_lap++ ) {

			for ( $term_lap = 0; $term_lap < count( $terms ); $term_lap++ ) {
				if ( $terms[ $term_lap ]->parent === $new_terms[ $new_term_lap ]->term_id ) {
					
					$terms[ $term_lap ]->prefix = $new_terms[ $new_term_lap ]->prefix . $placeholder;
					array_splice( $new_terms, $new_term_lap + 1 , 0, [ $terms[ $term_lap ] ] );
					unset( $terms[ $term_lap ] );
					$terms = array_values( $terms );
				}
			}
		}
	}	
	return ( $new_terms );
}
}

/*This is just for troubleshooting*/
if ( ! function_exists( 'bnmng_echo' ) ) {
function bnmng_echo ( ) {
	echo '<pre>';
	$args = func_get_args();
	foreach( $args as $arg ) {
		if( is_array( $arg ) ) {
			print_r( $arg );
		} else {
			echo( $arg );
		}
	}
	echo "\n", '</pre>';
}
}
