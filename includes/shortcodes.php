<?php
/**
 * Shortcode Functions
 *
 * @author Kirk Lundblade
 * @since 1.0.0
 */

require_once dirname( __FILE__ ) . '/category-queue.php';
require_once dirname( __FILE__ ) . '/queue-tabs.php';
require_once dirname( __FILE__ ) . '/map-pins-render.php';

//[bannerstrip ]
function insert_bannerstrip( $atts ) {
	$suri    = get_stylesheet_directory_uri();
	$bgimage = $suri . '/static/images/riches-header-yellow2.png';
	$a       = shortcode_atts(
		array(
			'bgimg'   => $bgimage,
			'number'  => 3,
			'faicon1' => 'fas fa-database',
			'faicon2' => 'fas fa-hammer',
			'faicon3' => 'fas fa-archive',
			'faicon4' => 'fas fa-database',
			'faicon5' => 'fas fa-hammer',
			'faicon6' => 'fas fa-archive',
		),
		$atts
	);
	$banner = "</div>
               <div class='media-background-container'>
                   <img class='media-background object-fit-cover' srcset='{$bgimage}' alt data-object-fit='cover'>
                   <div class='container my-5 fact-grid-wrap'>
                       <div class='row fact-grid'>
                           <a href='https://bendingtowardjustice.cah.ucf.edu/' class='col-sm-6 col-lg-4 fact-block'>
                               <aside>
                               <h2>Major Projects</h2>
                                   <span class='fas fa-hammer fact-header fact-header-lg fact-header-icon img-fluid'></span>
                                   <div class='fact-details'>
                                       <p><strong>View</strong> our current primary project, the Bending Toward Justice digital exhibit space.</p>
                                   </div>
                               </aside>
                           </a>
                           <a href='https://richesmi.cah.ucf.edu/omeka/' class='col-sm-6 col-lg-4 fact-block'>
                               <aside>
                               <h2>Digital Archive</h2>
                               <span class='fas fa-archive fact-header fact-header-lg fact-header-icon img-fluid'></span>
                                   <div class='fact-details'>
                                       <p><strong>Browse</strong> our collections, exhibits, and other curated materials in our Omeka repository.</p>
                                   </div>
                               </aside>
                           </a>
						    <a href='https://riches.cah.ucf.edu/?page_id=2570' class='col-sm-6 col-lg-4 fact-block'>
                               <aside>
                                   <h2>Database</h2>
                                   <span class='fas fa-database fact-header fact-header-lg fact-header-icon img-fluid'></span>
                                   <div class='fact-details'>
                                       <p><strong>Discover</strong> the planned evolution of our Mosaic Interface tool.</p>
                                   </div>
                               </aside>
                           </a>
                       </div>
                   </div>
               </div>
               <div class='container mt-4 mt-sm-5 mb-5 pb-sm-4' >
                        ";

	return $banner;
}

add_shortcode( 'bannerstrip', 'insert_bannerstrip' );

/**
 * [riches_category_row category="slug" label="Display Name" posts_per_page="3" reduced="1"]
 *
 * reduced="1" (or true/yes/on): omit the category title row; show a compact archive link after the cards.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function riches_category_row( $atts ) {
	$a = shortcode_atts(
		array(
			'category'       => '',
			'label'          => '',
			'posts_per_page' => 3,
			'reduced'        => '',
		),
		$atts,
		'riches_category_row'
	);

	$slug = sanitize_key( $a['category'] );
	if ( ! $slug ) {
		return '';
	}

	$reduced = riches_shortcode_reduced_flag( $a['reduced'] );

	return riches_render_category_queue(
		array(
			'category'          => $slug,
			'label'             => (string) $a['label'],
			'posts_per_page'    => absint( $a['posts_per_page'] ),
			'show_heading'      => ! $reduced,
			'reduced'           => $reduced,
			'wrap_collections'  => true,
			'include_container' => true,
		)
	);
}

add_shortcode( 'riches_category_row', 'riches_category_row' );

/**
 * [riches_map slug="community" height="420px" full_width="1"]
 *
 * Embeds a RICHES Leaflet map configured under RICHES Maps in the admin.
 * Use slug= (or map= / id=) matching the map post’s URL slug.
 * full_width="1" (or true/yes/on): edge-to-edge viewport width; no bottom margin on the map block.
 * In the block editor: add a Shortcode block and paste the code from the map edit screen.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function riches_map_shortcode_handler( $atts ) {
	return riches_map_shortcode( $atts );
}

add_shortcode( 'riches_map', 'riches_map_shortcode_handler' );
