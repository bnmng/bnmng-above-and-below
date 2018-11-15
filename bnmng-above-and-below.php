<?php
/*
Plugin Name: Above and Below
Plugin URL: http://aboveandbelow.bnmng.com
Description: Add text to displayed above and below post content
Version 1.0
Author: Benjamin Goldberg
Author URI: https://bnmng.com
Text Domain: bnmng-above-and-below
Licence: GPL2
*/

/*
 * Adds text above and below post content based on options
 * 
 * @param string $content The content of the post to have text added.
 *
 * @return string $content The content with the text added. 
*/

function bnmng_above_and_below( $content ) {

	$post = get_post();

	$option_name = 'bnmng_above_and_below';
	$options = get_option( $option_name );
	$add_to_beginning = '';
	$add_to_end = '';
	$qty_instances = count( $options['instances'] );
	for ( $each_instance = 0; $each_instance < $qty_instances; $each_instance++ ) {
		if ( $options['instances'][ $each_instance ]['singular'] == 1 && ! is_singular() ) {
			continue;
		} elseif ( $options['instances'][ $each_instance ]['singular'] == 2 && is_singular() ) {
			continue;
		}

		if ( ! ( $post->post_type == $options['instances'][ $each_instance ]['post_type'] ) ) {
			continue;
		}

		$taxonomy_names = get_post_taxonomies( $post );
		foreach ( $taxonomy_names as $taxonomy_name ) {
			$opt_term_ids = $options['instances'][ $each_instance ]['taxonomies'][ $taxonomy_name ];
			if ( count( $opt_term_ids ) ) {
				$post_terms = get_the_terms( $post, $taxonomy_name );
				if ( ! count( $post_terms ) ) {
					continue;
				} else {
					$post_term_ids = array_column( $post_terms, 'term_id' );
					foreach ( $opt_term_ids as $opt_term_id ) {
						if ( ! in_array( $opt_term_id, $post_term_ids ) ) {
							continue 3;
						}
					}
				}
			}
		}

		if ( post_type_supports( $post->post_type, 'author' ) ) {
			if ( $options['instances'][ $each_instance ]['author'] > 0 ) {
				if ( ! ( get_the_author_meta( 'ID' ) == $options['instances'][ $each_instance ]['author'] ) ) {
					continue;
				}
			}
		}

		$add_to_beginning .= ( stripslashes( $options['instances'][ $each_instance ]['at_beginning'] ) );
		$add_to_end = ( stripslashes( $options['instances'][ $each_instance ]['at_end'] ) ) . $add_to_end;
		if( $options['instances'][ $each_instance ]['wpautop'] ) {
			$add_to_beginning = wpautop( $add_to_beginning );
			$add_to_end = wpautop( $add_to_end );
		}

	}
	$content = $add_to_beginning . $content . $add_to_end;
	return $content;
}

add_filter( 'the_content', 'bnmng_above_and_below' );

function bnmng_above_and_below_menu() {
		add_options_page( 'Above and Below Options', 'Above and Below', 'manage_options', 'bnmng-above-and-below', 'bnmng_above_and_below_options' );
}
add_action( 'admin_menu', 'bnmng_above_and_below_menu' );

/*
 * Displays options for adding text above and below post content
*/
function bnmng_above_and_below_options() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$intro = '<p>';
	$intro .= __( 'This plugin adds text to the beginning and end of a post when the post is displayed. It does not alter the post in the database. ', 'bnmng-above-and-below' );
	$intro .= '</p>' . "\n";
	$intro .= '<p>';
	$intro .= __( 'HTML and shortcodes may be used.  Tags opened above post content can be closed below. ', 'bnmng-above-and-below' );
	$intro .= ' </p>';

	$option_name = 'bnmng_above_and_below';
	$instance_label = 'Instance %1$d';
	$show_help_label = __( 'Show Help' , 'bnmng-above-and-below' );
	$move_label = __( 'Move or Delete', 'bnmng-above-and-below' );
	$move_help = '';
	$move_options = [
		'nothing' => __( "Don't move or delete", 'bnmng-above-and-below' ),
		'up'      => __( 'Move this instance up', 'bnmng-above-and-below' ),
		'down'    => __( 'Move this instance down', 'bnmng-above-and-below' ),
		'delete'  => __( 'Delete this instance', 'bnmng-above-and-below' ),
	];
	$post_type_label = __( 'Post Type', 'bnmng-above-and-below' );
	$post_type_help = __( 'To add a new instance, select the appropriate post type and click "Save Changes"  ', 'bnmng-above-and-below' );
	$post_type_help .= __( 'This plugin should work as expected for posts and pages, and may work for some types of posts that you add. ', 'bnmng-above-and-below' );
	$singular_label = __( 'Singular/List View', 'bnmng-above-and-below' );
	$singular_help = __( 'Whether to display the added text while the post is in singular view only, list view only, or either.  ', 'bnmng-above-and-below' );
	$singular_help .= __( "This option won't be relavant for all post types.  ", 'bnmng-above-and-below' );
	$singular_options = [
		['value' => '1', 'name' => __( 'Single Only', 'bnmng-above-and-below' ) ],
		['value' => '2', 'name' => __( 'List Only', 'bnmng-above-and-below' ) ],
		['value' => '0', 'name' => __( 'Single or List', 'bnmng-above-and-below' ) ],
	];
	$taxonomies_label = '%1$s';
	$taxonomies_help = __( 'The %1$s of posts to have the text added. ', 'bnmng-above-and-below' );
	$taxonomies_help .= __( 'If more than one of the %1$s is selected, the post will have to be in <em>all</em> of the %1$s, <em>not any</em> of the %1$s for this instance to take effect.  ', 'bnmng-above-and-below' );
	$author_label = __( 'Author', 'bnmng-above-and-below' );
	$author_help = __( 'The author of posts to have the text added.', 'bnmng-above-and-below' );
	$above_label = __( 'Add Above Post Content', 'bnmng-above-and-below' );
	$above_help = __( 'The text to add above the content.  Tags opened above content can be closed below.', 'bnmng-above-and-below' );
	$below_label = __( 'Add Below Post Content', 'bnmng-above-and-below' );
	$below_help = __( 'The text to add at below the content. ', 'bnmng-above-and-below' );
	$wpautop_label = __( 'Auto &lt;p>', 'bnmng-above-and-below' );
	$wpautop_help = __( "Add paragraphs and breaks based on end-of-lines", 'bnmng-above-and-below' );
	$new_instance_label = __( 'Add a new instance', 'bnmng-above-and-below' );
	$new_instance_help = __( 'To add a new instance, check this box and click "Save Changes".  ', 'bnmng-above-and-below' );
	$new_instance_help = __( 'To add for a post type other than "post", select the post type below before clicking "Save Changes".  ', 'bnmng-above-and-below' );
	$new_instance_help = __( 'To add for a post type not in the list, select "New Post Type: " and type in the name of the new post type. ', 'bnmng-above-and-below' );
	$controlname_pat = $option_name . '[instances][%1$d][%2$s]';
	$controlid_pat = $option_name . '_%1$d_%2$s';
	$multicontrolname_pat = $option_name . '[instances][%1$d][%2$s][%3$s][]';
	$multicontrolid_pat = $option_name . '_%1$d_%2$s_%3$s';
	$global_controlname_pat = $option_name . '[%1$s]';
	$global_controlid_pat = $option_name . '_%1$s';

	$available_post_types=['post', 'page'];
	$taxonomies = array();

	$all_authors = get_users();

	$each_instance = 0;

	echo "\n";
	echo '<div class = "wrap">', "\n";
	echo '  <div class="bnmng-above-and-below-intro">', $intro, '</div>', "\n";
	echo '  <form method = "POST" action="">', "\n";

	if ( isset( $_POST['submit'] ) ) {
		$options = [];
		$swaps = [];
		$qty_post_instances = count( $_POST[ $option_name ]['instances'] );
		for ( $each_post_instance = 0; $each_post_instance < $qty_post_instances; $each_post_instance++ ) {
			$save_instance = true;
			if ( 'delete' == $_POST[ $option_name ]['instances'][ $each_post_instance ]['move'] ) {
				$save_instance = false;
			}
			if ( $save_instance ) {

				if ( 'down' == $_POST[ $option_name ]['instances'][ $each_post_instance ]['move']  || 'up' == $_POST[ $option_name ]['instances'][ $each_post_instance + 1]['move'] ) {
					$swaps[ $each_instance ] = 1;
					$swaps[ $each_instance + 1] = -1;
				}

				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['post_type'] = sanitize_key( $_POST[ $option_name ]['instances'][ $each_post_instance ]['post_type'] );
				if ( ! in_array( $options['instances'][ $each_instance + $swaps[ $each_instance ] ]['post_type'], $available_post_types ) ) {
					$available_post_types[] = $options['instances'][ $each_instance + $swaps[ $each_instance ] ]['post_type'];
				}
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['taxonomies'] = $_POST[ $option_name ]['instances'][ $each_post_instance ]['taxonomies'];
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['author'] = $_POST[ $option_name ]['instances'][ $each_post_instance ]['author'];
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['singular'] = $_POST[ $option_name ]['instances'][ $each_post_instance ]['singular'];
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['at_beginning'] = $_POST[ $option_name ]['instances'][ $each_post_instance ]['at_beginning'];
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['at_end'] = $_POST[ $option_name ]['instances'][ $each_post_instance ]['at_end'];
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['wpautop'] = $_POST[ $option_name ]['instances'][ $each_post_instance ]['wpautop'];
				$each_instance++;
			}
		}
		if ( $_POST[ $option_name ]['new_instance_post_type'] ) {
			$save_instance = true;
			if ( '+' == $_POST[ $option_name ]['new_instance_post_type'] ) {
				$new_instance_post_type = $_POST[ $option_name ]['new_post_type'];
			} else {
				$new_instance_post_type = $_POST[ $option_name ]['new_instance_post_type'];
			}
			if ( ! ( '' < $new_instance_post_type && sanitize_key( $new_instance_post_type ) == $new_instance_post_type ) ) {
				$save_instance = false;
			}
			if ( $save_instance ) {
				$options['instances'][ $each_instance ]['post_type']=$new_instance_post_type;
				if ( ! in_array( $options['instances'][ $each_instance ]['post_type'], $available_post_types ) ) {
					$available_post_types[] = $options['instances'][ $each_instance ]['post_type'];
				}
				if ( post_type_supports( $new_instance_post_type, 'author' ) ) {
					$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['author'] = 0;
				}
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['singular'] = '1';
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['at_beginning'] = '';
				$options['instances'][ $each_instance + $swaps[ $each_instance ] ]['at_end'] = '';
			}
		}
		update_option( $option_name,  $options );
	}
	$options = get_option( $option_name );

	$qty_instances = count( $options['instances'] ) ;

	if ( 1 == $qty_instances ) {
		$move_label = 'Delete';
	}

	for ( $each_instance = 0; $each_instance < $qty_instances; $each_instance++ ) {
		echo '    <div id="div_instance_', $each_instance, '">', "\n";
		echo '      <table class="form-table bnmng-above-and-below">', "\n";
		echo '        <tr>', "\n";
		echo '          <th colspan="2">', sprintf( $instance_label, ( $each_instance + 1 ) ), '</th>', "\n";
		echo '        </tr>', "\n";

		echo '        <tr>', "\n";
		echo '          <th>', $move_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <table class="form-table bnmng-above-and-below">', "\n";
		echo '               <tr>', "\n";
		echo '                 <td>none</td>', "\n";
		echo '                 <td>', "\n";
		echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_none' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="" checked="checked" >', "\n";
		echo '                 </td>', "\n";
		echo '                 <td>', $move_options[ 'nothing' ] , '</td>', "\n";
		echo '               </tr>', "\n";

		if ( $each_instance > 0 ) {
			echo '               <tr>', "\n";
			echo '                 <td>up</td>', "\n";
			echo '                 <td>', "\n";
			echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_up' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="up">', "\n";
			echo '                 </td>', "\n";
			echo '                 <td>', $move_options[ 'up' ], '</td>', "\n";
			echo '               </tr>', "\n";
		}

		if ( $each_instance < ( $qty_instances - 1 ) ) {
			echo '               <tr>', "\n";
			echo '                 <td>down</td>', "\n";
			echo '                 <td>', "\n";
			echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_down' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="down">', "\n";
			echo '                 </td>', "\n";
			echo '                 <td>', $move_options[ 'down' ], '</td>', "\n";
			echo '               </tr>', "\n";
		}

		echo '               <tr>', "\n";
		echo '                 <td>delete</td>', "\n";
		echo '                 <td>', "\n";
		echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_delete' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="delete">', "\n";
		echo '                 </td>', "\n";
		echo '                 <td>', $move_options[ 'delete' ], '</td>', "\n";
		echo '               </tr>', "\n";


		echo '             </table>', "\n";

		echo '            <div class="bnmng-above-and-below-help">', $move_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";

		echo '        <tr>', "\n";
		echo '          <th>', $post_type_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>';
		echo '    		  ', $options['instances'][ $each_instance ]['post_type'], "\n";
		echo '    		  ', '<input type="hidden" id="', sprintf( $controlid_pat, $each_instance, 'post_type' ), '" name="', sprintf( $controlname_pat, $each_instance, 'post_type' ), '" value="', $options['instances'][ $each_instance ]['post_type'], '">',  "\n";
		echo '            </div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";

		echo '        <tr>', "\n";
		echo '          <th>', $singular_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>', "\n";
		echo '              <select id="', sprintf( $controlid_pat, $each_instance, 'singular' ),  '" name="', sprintf( $controlname_pat, $each_instance, 'singular' ), '">', "\n";
		foreach( $singular_options AS $singular_option ) {
			echo '                <option value="' . $singular_option['value'] . '"';
			if( $singular_option['value']  == $options['instances'][ $each_instance ]['singular'] ) {
				echo ' selected="selected" ';
			}
			echo ' >', $singular_option['name'], '</option>', "\n";
		}
		echo '              </select>', "\n";
		echo '            </div>', "\n";
		echo '            <div class="bnmng-above-and-below-help">', $singular_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";

		if ( ! isset( $taxonomies[ $options['instances'][ $each_instance ]['post_type'] ] ) ) {
			$taxonomies[ $options['instances'][ $each_instance ]['post_type'] ] = get_object_taxonomies( $options['instances'][ $each_instance ]['post_type'], 'objects' ) ;
			foreach ( $taxonomies[ $options['instances'][ $each_instance ]['post_type'] ] as $taxonomy ) {
				$terms[ $taxonomy->name ]  = bnmng_assign_taxonomy_lineage( get_terms( $taxonomy->name, array( 'hide_empty'=>0 ) ) );
			}
		}
		foreach ( $taxonomies[ $options['instances'][ $each_instance ]['post_type'] ] AS $taxonomy ) {
			if ( count( $terms[ $taxonomy->name ] ) ) {
				echo '        <tr>', "\n";
				echo '          <th>', $taxonomy->label, '</th>', "\n";
				echo '          <td>', "\n";
				echo '            <div>', "\n";
				echo '              <select id="', sprintf( $multicontrolid_pat, $each_instance, 'taxonomies',  $taxonomy->name ), '" name="', sprintf( $multicontrolname_pat, $each_instance, 'taxonomies',  $taxonomy->name ), '" multiple="multiple" size="',  min( count( $terms[ $taxonomy->name ] ), 3 ),  '">', "\n";
				foreach ( $terms[ $taxonomy->name ] as $term ) {
					echo '                <option value="', $term->term_id , '"';
					if( in_array( $term->term_id, $options['instances'][ $each_instance ]['taxonomies'][ $taxonomy->name ] ) ) {
						echo ' selected="selected" ';
					}
					echo '>', $term->prefix . $term->name, '</option>', "\n";;
				}
				echo '              </select>', "\n";
				echo '            </div>', "\n";
				echo '            <div class="bnmng-above-and-below-help">', sprintf( $taxonomies_help, $taxonomy->label ), '</div>', "\n";
				echo '          </td>', "\n";
				echo '        </tr>', "\n";
			}
		}

		echo '        <tr>', "\n";
		echo '          <th>', $author_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>', "\n";
		echo '              <select id="', sprintf( $controlid_pat, $each_instance, 'author' ), '" name="', sprintf( $controlname_pat, $each_instance, 'author' ), '" >', "\n";
		echo '                <option value="0"';
		if ( 0 == $options['instances'][ $each_instance ]['author'] ) {
			echo ' selected="selected" ';
		}
		echo '>[any author]</option>', "\n";
		foreach ( $all_authors as $author ) {
			echo '              <option value="', $author->ID, '"';
			if ( $author->ID == $options['instances'][ $each_instance ]['author'] ) {
				echo '    selected="selected"';
			}
			echo '    >', $author->display_name, '</option>', "\n";
		}
		echo '              </select>', "\n";
		echo '            </div>', "\n";
		echo '            <div class="bnmng-above-and-below-help">', $author_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";

		echo '        <tr>', "\n";
		echo '          <th>', $above_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>', "\n";
		echo '              <textarea id="', sprintf( $controlid_pat, $each_instance, 'at_beginning' ), '" name="', sprintf( $controlname_pat, $each_instance, 'at_beginning' ), '">', stripslashes( $options['instances'][ $each_instance ]['at_beginning'] ), '</textarea>', "\n";
		echo '             </div>', "\n";
		echo '            <div class="bnmng-above-and-below-help">', $above_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";

		echo '        <tr>', "\n";
		echo '          <th>', $below_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>', "\n";
		echo '              <textarea id="', sprintf( $controlid_pat, $each_instance, 'at_end' ), '" name="', sprintf( $controlname_pat, $each_instance, 'at_end' ), '">', stripslashes( $options['instances'][ $each_instance ]['at_end'] ), '</textarea>', "\n";
		echo '            </div>', "\n";
		echo '            <div class="bnmng-above-and-below-help">', $below_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";

		echo '        <tr>', "\n";
		echo '          <th>', $wpautop_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>', "\n";
		echo '              <input type="checkbox" id="', sprintf( $controlid_pat, $each_instance, 'wpautop' ), '" name="', sprintf( $controlname_pat, $each_instance, 'wpautop' ), '" ' ;
		if( $options['instances'][ $each_instance ]['wpautop'] ) {
			echo ' checked="checked" ';
		}
		echo '>', "\n";
		echo '            </div>', "\n";
		echo '            <div class="bnmng-above-and-below-help">', $wpautop_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";


		echo '      </table>', "\n";
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
	if ( $qty_instances > 0 ) {
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
		if ( ! ( 0 < $qty_instances ) && 'post' == $post_type ) {
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
	document.getElementById( "bnmng_above_and_below_new_post_type").addEventListener( "keydown", function() {
		if ( document.getElementById( "bnmng_above_and_below_new_post_type" ).value > "" ) {
			document.getElementById( "bnmng_above_and_below_new_instance_post_type_new" ).checked=true;
		}
	} );
</script>
	<?php
}

/*
 * Order terms of a taxonomy by lineage and assign a prefix field
 * of dashes ( or other characters if chosen ) to provide indentation.

 * @param array $terms The terms including in the taxonomy
 *
 * @param string $placeholder the string to be used to build the prefix, which
 * can be used to indent the name of the term under the name of its parent
*/
if ( ! function_exists( 'bnmng_assign_taxonomy_lineage' ) ) {
function bnmng_assign_taxonomy_lineage( $terms, $placeholder = '-') {

	$count_terms = count( $terms );	
	$new_terms = [];

	$count_terms = count( $terms );
	for ( $each_term = 0; $each_term < $count_terms; $each_term++ ) {
		$is_top  = false;
		if ( 0 == $terms[ $each_term ]->parent ) {
			$is_top = true;
		} else {
			$is_top = true;
			for ( $each_maybe_parent = 0; $each_maybe_parent < count ( $terms ); $each_maybe_parent++ ) {
				if ( $terms[ $each_maybe_parent ]->term_id == $terms[ $each_term ]->parent ) {
					$is_top = false;
					break;
				}
			}
			for ( $each_maybe_parent = 0; $each_maybe_parent < count ( $new_terms ); $each_maybe_parent++ ) {
				if ( $new_terms[ $each_maybe_parent ]->term_id == $terms[ $each_term ]->parent ) {
					$is_top = false;
					break;
				}
			}
		}
		if ( $is_top ) {
			$terms[ $each_term ]->prefix = '';
			$new_terms[] = $terms[ $each_term ];
			unset( $terms[ $each_term ] );
		}
	}
	
	while ( count( $terms ) ) {
		for( $each_new_term = 0; $each_new_term < count( $new_terms ); $each_new_term++ ) {

			for( $each_term = 0; $each_term < count( $terms ); $each_term++ ) {
				if( $terms[ $each_term ]->parent == $new_terms[ $each_new_term ]->term_id ) {
					
					$terms[ $each_term ]->prefix = $new_terms[ $each_new_term ]->prefix . $placeholder;
					array_splice( $new_terms, $each_new_term + 1 , 0, [ $terms[ $each_term ] ] );
					unset( $terms[ $each_term ] );
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
function bnmng_echo ( $output, $line='' ) {
	echo '<pre>';
	if ( $line > '' ) {
		echo $line, ': ';
	}
	echo $output,  "\n", '</pre>';
}
}
