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

	if( is_singular() ) {
		bnmng_echo ( "singular");
	}
	if( is_archive() ) {
		bnmng_echo ( "archive");
	}
	if( in_the_loop() ) {
		bnmng_echo ( "in the loop" );
	}

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

		$taxonomies = get_post_taxonomies( $post );
		foreach ( $taxonomies as $taxonomy ) {
			$opt_terms = $options['instances'][ $each_instance ]['taxonomies'][ $taxonomy ];
			if ( count( $opt_terms ) ) {
				$terms = get_the_terms( $post, $taxonomy );
				if ( count( $terms ) ) {
					$term_ids = array_column( $terms, 'term_id' );
					foreach ( $opt_terms as $opt_term ) {
						if ( ! in_array( $opt_term, $term_ids ) ) {
							continue 2;
						}
					}
				} else {
					continue;
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

	$intro = '';
	$intro .= '<p>This plugin adds text to the beginning and end of a post when the post is displayed. ';
	$intro .= 'It does not alter the post in the database.</p>';
	$intro .= '<p>HTML and shortcodes may be used.  Tags opened in the beginning can be closed at the end. ';
	$intro .= 'End of post content is added in reverse order for proper nesting. </p>';

	$option_name = 'bnmng_above_and_below';
	$text_domain = 'bnmng-above-and-below';
	$instance_label = 'Instance %1$d';
	$move_label = 'Move or Delete';
	$move_help = '';
	$post_type_label = 'Post Type';
	$post_type_help = 'To add a new instance, select the appropriate post type and click "Save Changes"  ';
	$post_type_help .= 'This plugin should work as expected for posts and pages, and may work for some types of posts that you add. ';
	$singular_label = 'Singular/List View';
	$singular_help = 'Whether to display the added text while the post is in singular view only, list view only, or either.  ';
	$singular_help .= 'This option won\'t be relavant for all post types.  ';
	$singular_options = [
		['value' => '1', 'name' => 'Single Only'],
		['value' => '2', 'name' => 'List Only'],
		['value' => '0', 'name' => 'Single or List'],
	];
	$taxonomies_label = '%1$s';
	$taxonomies_help = 'The %1$s of posts to have the text added.  If more than one is selected, text will be added <em>only</em> to posts of <em>all</em> selected %1$s.';
	$author_label = 'Author';
	$author_help = 'The author of posts to have the text added.';
	$beginning_label = 'Add to Beginning of Post';
	$beginning_help = 'The text to add at the beginning of the post.  If you use HTML, ensure the valididy of your code.  Tags can be closed at the end of post';
	$end_label = 'Add to End of Post';
	$end_help = 'The text to add at the end of the post.  If you use HTML, ensure the valididy of your code.';
	$new_instance_label = 'Add a new instance';
	$new_instance_help = 'To add a new instance, check this box and click "Save Changes".  ';
	$new_instance_help = 'To add for a post type other than "post", select the post type below before clicking "Save Changes".  ';
	$new_instance_help = 'To add for a post type not in the list, select "New Post Type: " and type in the name of the new post type. ';
	$controlname_pat = $option_name . '[instances][%1$d][%2$s]';
	$controlid_pat = $option_name . '_%1$d_%2$s';
	$global_controlname_pat = $option_name . '[%1$s]';
	$global_controlid_pat = $option_name . '_%1$s';

	$available_post_types=['post', 'page'];
	$taxonomies = array();

	$all_authors = get_users();

	$each_instance = 0;

	echo "\n";
	echo '<div class = "wrap">', "\n";
	echo '  <div class="', $text_domain, '-into">', $intro, '</div>', "\n";
	echo '  <form method = "POST" action="?page=', $text_domain, '">', "\n";

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
		echo '      <table class="form-table ', $text_domain, '">', "\n";
		echo '        <tr>', "\n";
		echo '          <th colspan="2">', sprintf( $instance_label, ( $each_instance + 1 ) ), '</th>', "\n";
		echo '        </tr>', "\n";
		echo '        <tr>', "\n";
		echo '          <th>', $move_label, '</th>', "\n";
		echo '          <td>', "\n";

		echo '            <table class="form-table ', $text_domain, '">', "\n";

		echo '               <tr>', "\n";
		echo '                 <td>none</td>', "\n";
		echo '                 <td>', "\n";
		echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_none' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="" checked="checked" >', "\n";
		echo '                 </td>', "\n";
		echo '                 <td>Don\'t move or delete this instance</td>', "\n";
		echo '               </tr>', "\n";

		if ( $each_instance > 0 ) {
			echo '               <tr>', "\n";
			echo '                 <td>up</td>', "\n";
			echo '                 <td>', "\n";
			echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_up' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="up">', "\n";
			echo '                 </td>', "\n";
			echo '                 <td>Move this instance up</td>', "\n";
			echo '               </tr>', "\n";
		}

		if ( $each_instance < ( $qty_instances - 1 ) ) {
			echo '               <tr>', "\n";
			echo '                 <td>down</td>', "\n";
			echo '                 <td>', "\n";
			echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_down' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="down">', "\n";
			echo '                 </td>', "\n";
			echo '                 <td>Move this instance down</td>', "\n";
			echo '               </tr>', "\n";
		}

		echo '               <tr>', "\n";
		echo '                 <td>delete</td>', "\n";
		echo '                 <td>', "\n";
		echo '                    <input type="radio" id="', sprintf( $controlid_pat, $each_instance, 'move_delete' ), '" name="', sprintf( $controlname_pat, $each_instance, 'move' ), '" value="delete">', "\n";
		echo '                 </td>', "\n";
		echo '                 <td>Delete this instance</td>', "\n";
		echo '               </tr>', "\n";


		echo '             </table>', "\n";

		echo '            <div class="', $text_domain, '-help">', $move_help, '</div>', "\n";
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
		echo '            <div class="', $text_domain, '-help">', $singular_help, '</div>', "\n";
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
				echo '              <select multiple="multiple" size="',  min( count( $terms[ $taxonomy->name ] ), 3 ),  '">', "\n";
				foreach ( $terms[ $taxonomy->name ] as $term ) {
					echo '                <option value="', $term->term_id , '">', $term->prefix . $term->name, '</option>', "\n";;
				}
				echo '              </select>', "\n";
				echo '            </div>', "\n";
				echo '            <div class="', $text_domain, '-help">', sprintf( $taxonomies_help, $taxonomy->label ), '</div>', "\n";
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
		echo '            <div class="', $text_domain, '-help">', $author_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";

		echo '        <tr>', "\n";
		echo '          <th>', $beginning_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>', "\n";
		echo '              <textarea id="', sprintf( $controlid_pat, $each_instance, 'at_beginning' ), '" name="', sprintf( $controlname_pat, $each_instance, 'at_beginning' ), '">', stripslashes( $options['instances'][ $each_instance ]['at_beginning'] ), '</textarea>', "\n";
		echo '             </div>', "\n";
		echo '            <div class="', $text_domain, '-help">', $beginning_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";
		echo '        <tr>', "\n";
		echo '          <th>', $end_label, '</th>', "\n";
		echo '          <td>', "\n";
		echo '            <div>', "\n";
		echo '              <textarea id="', sprintf( $controlid_pat, $each_instance, 'at_end' ), '" name="', sprintf( $controlname_pat, $each_instance, 'at_end' ), '">', stripslashes( $options['instances'][ $each_instance ]['at_end'] ), '</textarea>', "\n";
		echo '            </div>', "\n";
		echo '            <div class="', $text_domain, '-help">', $end_help, '</div>', "\n";
		echo '          </td>', "\n";
		echo '        </tr>', "\n";
		echo '      </table>', "\n";
		echo '    </div>', "\n";
	}

	/*   'new instance' form */
	echo '    <div id="div_add_instance">', "\n";
	echo '      <table class="form-table ', $text_domain, '">', "\n";
	echo '       <tr>', "\n";
	echo '         <th>Add a New Instance</th>', "\n";
	echo '         <td>', "\n";
	echo '           <table class="form-table ', $text_domain, '">', "\n";
	echo '             <tr>', "\n";
	echo '               <td>none</td>', "\n";
	echo '               <td>', "\n";
	echo '                  <input type="radio" id="', sprintf( $global_controlid_pat, 'new_instance_post_type_none' ), '" name="', sprintf( $global_controlname_pat, 'new_instance_post_type' ), '" value=""';
	if ( $qty_instances > 0 ) {
		echo ' checked="checked" ';
	}
	echo '>', "\n";
	echo '               </td>', "\n";
	echo '               <td>(Don\'t add)</td>', "\n";
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
	echo '           <div class="', $text_domain, '-help">', $post_type_help, '</div>', "\n";
	echo '         </td>', "\n";
	echo '       </tr>', "\n";
	echo '      </table>', "\n";
	echo '    </div>', "\n";

	echo '    <div id="submit">', "\n";
	echo '      ', get_submit_button(), "\n";
	echo '    </div>', "\n";
	echo '  </form>', "\n";
	echo '</div>', "\n";
}
add_action( 'admin_head-settings_page_bnmng-above-and-below', 'bnmng_admin_above_and_below_style' );

function bnmng_admin_above_and_below_style() {
		$text_domain = 'bnmng-above-and-below';
		$style='
	table.' . $text_domain . ' {
		border: 1px solid black;
	}
	table.' . $text_domain . ' th {
		padding-left: 1em;
	}
	table.' . $text_domain . ' table {
		border: none;
	}
	table.' . $text_domain . ' table.' . $text_domain . ' td {
		padding: 0 1px .5px 1px;
	}
	table.' . $text_domain . ' textarea {
		width:100%;
	}
';
		echo '<style>' . $style . '</style>';
}
add_action( 'admin_footer-settings_page_bnmng-above-and-below', 'bnmng_admin_above_and_below_script' );

function bnmng_admin_above_and_below_script() {
		$option_name = 'bnmng_above_and_below';
		$script='
	document.getElementById( "' . $option_name . '_new_post_type").addEventListener("keydown", function() {
		if ( document.getElementById( "' . $option_name . '_new_post_type" ).value > "" ) {
			document.getElementById( "' . $option_name . '_new_instance_post_type_new" ).checked=true;
		}
	} );
';
		echo '<script type="text/javascript">' . $script . '</script>';

}

/*
 * Order terms of a taxonomy by lineage and assign a prefix field
 * of dashes ( or other characters if chosen ) to provide indentation.
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
function bnmng_echo ( $verb, $line='' ) {
	echo '<pre>';
	if ( $line > '' ) {
		echo $line;
	}
	echo $verb,  "\n", '</pre>';
}
}
