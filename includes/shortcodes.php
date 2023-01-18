<?php
/**
 * Shortcode Functions
 **/

/**
 * 
 * 
 *
 * @author Kirk Lundblade
 * @since 1.0.0
 **/





//[bannerstrip ]
 function insert_bannerstrip($atts){
     $suri = get_stylesheet_directory_uri();
     $bgimage=$suri . '/static/images/riches-header-yellow2.png';
     $a = shortcode_atts( array(
        'bgimg' => $bgimage,
        'number' => 3,
        'faicon1' => 'fas fa-database',
        'faicon2' => 'fas fa-hammer',
        'faicon3' => 'fas fa-archive',
        'faicon4' => 'fas fa-database',
        'faicon5' => 'fas fa-hammer',
        'faicon6' => 'fas fa-archive',
    ), $atts );
     $banner = "</div>
                <div class='media-background-container'>
                    <img class='media-background object-fit-cover' srcset='{$bgimage}' alt data-object-fit='cover'>
                    <div class='container my-5 fact-grid-wrap'>
                        <div class='row fact-grid'>
                            <a href='https://richesmi.cah.ucf.edu/' class='col-sm-6 col-lg-4 fact-block'>
                                <aside>
                                    <h2>Database</h2>
                                    <span class='fas fa-database fact-header fact-header-lg fact-header-icon img-fluid'></span>
                                    <div class='fact-details'>
                                        <p><strong>Search</strong> RICHES Mosaic Interface for historical media collected in and around Florida</p>
                                    </div>
                                </aside>
                            </a>
                            <a href='https://riches.cah.ucf.edu/?page_id=1005' class='col-sm-6 col-lg-4 fact-block'>
                                <aside>
                                <h2>Projects</h2>
                                    <span class='fas fa-hammer fact-header fact-header-lg fact-header-icon img-fluid'></span>
                                    <div class='fact-details'>
                                        <p><strong>View</strong> the projects we're supporting or have completed</p>
                                    </div>
                                </aside>
                            </a>
                            <a href='https://riches.cah.ucf.edu/?page_id=907' class='col-sm-6 col-lg-4 fact-block'>
                                <aside>
                                <h2>Collections</h2>
                                <span class='fas fa-archive fact-header fact-header-lg fact-header-icon img-fluid'></span>
                                    <div class='fact-details'>
                                        <p><strong>Browse</strong> our recent collections for the MI database</p>
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

 add_shortcode('bannerstrip', 'insert_bannerstrip');
?>