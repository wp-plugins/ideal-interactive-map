<?php
/*
Plugin Name: Ideal Interactive Maps
Plugin URI: http://idealwebgeek.com
Description: Interactive and Informative map
Version: 0.1
*/
define("PLUGINURL", dirname(__FILE__) );
require_once( dirname(__FILE__) ."/metaboxes/meta_box.php");

class ideal_interactive_map{
	function __construct(){
	
        $this->options = get_option( 'cso_map_page' );
        
		add_action("init", array(&$this, "register_postType"));
		add_shortcode("iwg_maps", array($this, "shortcode"));
		add_action("wp_ajax_mapdata", array($this, "json_mapdata"), 20);
		add_action("wp_ajax_nopriv_mapdata", array($this, "json_mapdata"), 20);
		add_action("wp_ajax_mapsubpage", array($this, "ajax_mapsubpage"), 20);
		add_action("wp_ajax_nopriv_mapsubpage", array($this, "ajax_mapsubpage"), 20);
		//remove_filter( 'the_content', 'wpautop' );
		add_action( 'wp_enqueue_scripts', array($this, "header") );	
		add_action("wp_footer", array($this, "footer"), 20);
		
		if( is_admin() ){
	        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
	        add_action( 'admin_init', array( $this, 'page_init' ) );
        }
			
	}
	
	function header(){
			$page =  isset( $this->options['page_id'] ) ? $this->options['page_id'] : 0 ;

			if(!is_page($page) && $page == 0) return;
			
			$version = "1";
			wp_enqueue_script( 'jquery-mousewheel', plugins_url( 'src/jquery.mousewheel.js' , __FILE__ ) , array( 'jquery' ), '20140319', true );
			wp_enqueue_script( 'jquery-scrollbar', plugins_url( 'src/perfect-scrollbar.js' , __FILE__ ), array( ), '20140319', true );
			
			wp_enqueue_script( 'ammap', plugins_url( 'ammap/ammap.js' , __FILE__ ), array("jquery"), '20140319', true );
			wp_enqueue_script( 'ammap-world', plugins_url( 'ammap/maps/js/worldLow.js' , __FILE__ ), array("jquery"), '20140319', true);
			wp_enqueue_script( 'jquery-ui-core');
			wp_enqueue_script( 'ammap-light', plugins_url( 'ammap/themes/light.js' , __FILE__ ), array("jquery"), '20140319', true);
			wp_enqueue_script('jquery-ui-tabs');
			
		//	wp_enqueue_style( 'Sans-narrow', 'http://fonts.googleapis.com/css?family=PT+Sans+Narrow:400,700' , array(), '20140319', true );
			wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css', false, '20140319', false);
			wp_enqueue_style( 'scrollbar', plugins_url( 'src/perfect-scrollbar.css' , __FILE__ ));
			wp_enqueue_style( 'ammap-style', plugins_url( 'src/style.css' , __FILE__ ));
			wp_enqueue_style( 'phoca-flags', plugins_url( 'src/phoca-flags.css' , __FILE__ ), false, '20140319', false);
			
	}
	
	function shortcode(){
		$close = plugins_url( 'img/close.png' , __FILE__ );
		$return = <<<xxx
    	<div id="map-container">
        <div id="mapdiv" style="width: 100%; background-color:#EEEEEE; height: 500px;"></div>
        <div id="desc_overlay" class="animated">
	        <h2 class="pop_desc_title"></h2><span id="ico_close"><img src="$close" align="middle" /></span>
	        <div id="overlay-content"></p>
	        </div>
        </div>
    	</div>
    	<div id="mapreadmore">
    	
    	</div> 
xxx;
    	
		return $return;	
	}
	
	function json_mapdata(){
	
       $targetSVG = "M9,0C4.029,0,0,4.029,0,9s4.029,9,9,9s9-4.029,9-9S13.971,0,9,0z M9,15.93 c-3.83,0-6.93-3.1-6.93-6.93S5.17,2.07,9,2.07s6.93,3.1,6.93,6.93S12.83,15.93,9,15.93 M12.5,9c0,1.933-1.567,3.5-3.5,3.5S5.5,10.933,5.5,9S7.067,5.5,9,5.5 S12.5,7.067,12.5,9z";
        $countries = $this->mappoints();
        
        $args = array("post_type" => "map_maps", "posts_per_page"=> -1, "post_status" => "publish" );
        $the_query = new WP_Query( $args );

		$images_array = array();
		$areas = array();
			
		// The Loop
		if ( $the_query->have_posts() ) {

			while ( $the_query->have_posts() ) { $the_query->the_post();
				$id = get_the_ID();
				$country_code = get_post_meta($id, "map_country", true);
				if($country_code && isset($countries[$country_code])){
				
					
					$areas[] = array(
											"id"=>$country_code,
											"groupId"=>$country_code
									);
									
					$images_array[] = array(
								            "svgPath" => $targetSVG,
								            "zoomLevel"=> 2,
								            "scale"=> .7,
								            "color"=> "#234589",
								            "title"=> $countries[$country_code][0],
								            "latitude"=> $countries[$country_code][1],
								            "longitude"=> $countries[$country_code][2],
								            "groupId"=> $country_code,
								            "custom"=> array(
								            	"desc"=> '<div class="phoca-flagbox"><span class="phoca-flag '. strtolower($country_code) .'"></span></div>' . get_the_content()
								            )
										);
				}
			}
		} 

        	
	echo json_encode(
					array(
						"map"=>"worldLow",
						"zoomOnDoubleClick"=>false,
						"areas"=> $areas,
						"images" => $images_array
				)
			);
		exit;
	}
	function footer(){
	
		$page =  isset( $this->options['page_id'] ) ? $this->options['page_id'] : 0 ;
		
		if(!is_page($page) && $page == 0) return;
			
			
	 $image = plugins_url( 'ammap/images/' , __FILE__ );
	 
	 $jsonparse = admin_url('admin-ajax.php?action=mapdata');
	 $mapsubpageurl = admin_url('admin-ajax.php?action=mapsubpage');
		echo <<<xxx
        <script type="text/javascript">
   	     	var map;
			var mapDataProvider = {
			        map: "worldLow",
			        zoomOnDoubleClick: false
			    };
			
			AmCharts.ready(function() {
				map = new AmCharts.AmMap();
				map.type = "map";
				map.theme = "light";
				map.pathToImages = "$image";
				map.centerMap = true;
				map.getAreasFromMap = true;
				map.mouseWheelZoomEnabled = true;
				map.dataProvider = {};
				map.imagesSettings = {
									    rollOverScale: 2,
									    selectedScale: 2,
								        zoomX: 2,
								        color: "#15a892"
									};
				map.zoomControl = {
				        buttonFillColor: "#70A7D1",
				        gridHeight: 50 };
				map.selectedObject = { color: "#15a892"},
				
			    map.areasSettings = {
			        rollOverColor: "#15a892",
			        color: "#5296cb",
			        unlistedAreasColor: "#81C1DF",
			        selectedColor: "#15a892",
					rollOverAlpha: 0.8
			    };
				
				
				jQuery.getJSON( "{$jsonparse}", function(xconsole){
					map.dataProvider = xconsole;
									    
				map.write("mapdiv");
				});
				
			    map.addListener("clickMapObject", function (event) {
			    	console.log(event);
			    	onregionClick(event);
			    });
			    
			    map.addListener("homeButtonClicked", function (event) {
				     jQuery("#ico_close").click();
			    });
			    
			});
			
			function zoomOut () {
			    map.zoomOut();
			    
			}
			
		    function centerMap () {
			  //  map.zoomToLongLat(map.zoomLevel(), map.initialZoomLongitude, map.initialZoomLatitude, true);
			}
			
			function l(s){
				console.log(s);
			}
			
		    jQuery(document).ready(function ($) {
		    	/** Scrollbar*/
		        jQuery('#overlay-content').perfectScrollbar({suppressScrollX: true});
		        jQuery("#ico_close").on("click",function(){
	       		     
			        var map_overlay = jQuery("#desc_overlay");
						map_overlay.removeClass("fadeInDown");
						map_overlay.addClass("fadeOutDown");
						
					  zoomOut();
					  map_overlay.delay( 1200 ).css({"display": 'none'});
					  jQuery("#mapreadmore").html('');
	       		});
		      });

			  
		     function onregionClick(country){
				 var map_overlay = jQuery("#desc_overlay");
				 var obj = country.mapObject;
				jQuery("#mapreadmore").html('');
				jQuery(".pop_desc_title").html(obj.title);
				jQuery("#overlay-content").html(obj.custom.desc);
				
						map_overlay.removeClass("fadeOutDown");
						map_overlay.delay( 800 ).css({"display": 'block'});
						map_overlay.addClass("fadeInDown");
						map_overlay.drags();
					
				    jQuery(".a_readmore").on( "click", function() {
				    	var pid = jQuery(this).attr("data-pid");
				    	jQuery.get(
								    '{$mapsubpageurl}&pid='+pid, 
									    function(response){
									    	jQuery("#mapreadmore").html(response);
									    	jQuery( "#maptabs" ).tabs();
									    }
								);

						jQuery('html, body').animate({
					        scrollTop: jQuery( "#mapreadmore" ).offset().top - 30
					    }, 1000);
					    return false;
					});
					
				}
				
			(function($) {
				
				//Draggable
			    jQuery.fn.drags = function(opt) {
			
			        opt = $.extend({handle:"",cursor:"move"}, opt);
			
			        if(opt.handle === "") {
			            var el = this;
			        } else {
			            var el = this.find(opt.handle);
			        }
			
			        return el.css('cursor', opt.cursor).on("mousedown", function(e) {
			           
			            if(opt.handle === "") {
			                var drag = jQuery(this).addClass('draggable');
			            } else {
			                var drag = jQuery(this).addClass('active-handle').parent().addClass('draggable');
			            }
			            
			            var z_idx = drag.css('z-index'),
			                drg_h = drag.outerHeight(),
			                drg_w = drag.outerWidth(),
			                pos_y = drag.offset().top + drg_h - e.pageY,
			                pos_x = drag.offset().left + drg_w - e.pageX;
			            drag.css('z-index', 1000).parents().on("mousemove", function(e) {
			                jQuery('.draggable').offset({
			                    top:e.pageY + pos_y - drg_h,
			                    left:e.pageX + pos_x - drg_w
			                }).on("mouseup", function() {
			                    jQuery(this).removeClass('draggable').css('z-index', z_idx);
			                });
			            });
			            e.preventDefault(); // disable selection
			        }).on("mouseup", function() {
			            if(opt.handle === "") {
			                jQuery(this).removeClass('draggable');
			            } else {
			                jQuery(this).removeClass('active-handle').parent().removeClass('draggable');
			            }
			        });
			
			    }
			})(jQuery);
        </script>
xxx;
	}
	
	function ajax_mapsubpage(){
		
		
		$id = wp_filter_kses($_REQUEST['pid']);
		
		$field = get_field("subpages", $id);
		if($field){
			
			foreach( $field as $key => $row )
			{
				$title .= '<li><a href="#tabs-'. $key .'">'. $row['title'] .'</a></li>';
				$content .= '<div id="tabs-'. $key .'">'. $row['content'] .'</div>';
			}
		
		echo '<div id="maptabs">
				  <ul class="nav-tabs">'. $title .'</ul>
				  '. $content .'
				</div>';
		}
		exit;	
	}
	
	function register_postType(){
			$labels = array(
			'name'                => 'Manage Maps',
			'singular_name'       => 'Manage Map',
			'menu_name'           => 'Manage Maps',
			'parent_item_colon'   => 'Parent Item:',
			'all_items'           => 'All Items',
			'view_item'           => 'View Item',
			'add_new_item'        => 'Add New Item',
			'add_new'             => 'Add New',
			'edit_item'           => 'Edit Item',
			'update_item'         => 'Update Item',
			'search_items'        => 'Search Item',
			'not_found'           => 'Not found',
			'not_found_in_trash'  => 'Not found in Trash',
		);
		$args = array(
			'label'               => 'interactive_map',
			'description'         => 'Interactive Map',
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'map_maps', $args );
		
	
		$prefix = 'map_';
		
		$countryoption = array();
		foreach($this->mappoints() as $index => $cname){
			$countryoption[$index] = array("label" => $cname[0], "value" => $index);
		}
			asort($countryoption);
		$fields = array(
				array(
					'label'	=> 'Country',
					'desc'	=> 'Select Country Name.',
					'id'	=> $prefix.'country',
					'type'	=> 'select',
					'options' => $countryoption
				)
			);
		new custom_add_meta_box( 'map_box', 'Map Properties', $fields, array("map_maps"), true );
	}
	
	function mappoints($code = ""){
			// format country, latitude, longitude
			$countries = array(
					 'FI' => array("Finland", 64.00, 26.00),
					 'GH' => array("Ghana", 8.00, -2.00),
					 'BD' => array('Bangladesh', 23.70, 90.40),
					 'CM' => array('Cameroon', 6.00, 12.00),
					 'CN' => array('China', 35.00, 105.00), 
					 'CU' => array('Cuba', 21.50, -80.00), 
					 'GM' => array( "country" =>'Gambia', 13.50,  -15.50), 
					 'KE' => array('Kenya', 1.00, 38.00), 
					 'MZ' => array('Mozambique', -18.25, 35.00), 
					 'NA' => array('Namibia', -22.00, 17.00), 
					 'VC' => array('St Vincent and the Grenadines', 13.25 , -61.2 ), 
					 'TZ' => array('Tanzania, United Rep of', -6.00,  35.00), 
					 'VN' => array('Viet Nam', 16.00, 106.00),
					 'ZA' => array('South Africa', -30.00, 26.00), 
					 'AF' => array('Afghanistan', 33.00, 65.00), 
					 'MY' => array('Malaysia', 4.22, 101.97), 
					 'TG' => array('Togo', 8.00, 1.17), 
					 'KR' => array('Korea, Rep of (South)', 37.00, 127.50), 
					 'ID' => array('Indonesia', -5.00, 120.00), 
					 'SS' => array('South Sudan', 4.85, 31.6), 
					 'PH' => array('Philippines', 13.00, 122.00), 
					 'BN' => array('Brunei Darussalam', 4.50, 114.67), 
					 'KH' => array('Cambodia', 13.00, 105.00), 
					 'LA' => array('Lao People\'s Dem Rep', 18.00, 105.00), 
					 'MM' => array('Myanmar (Burma)', 22.00, 98.00), 
					 'SG' => array('Singapore', 1.37, 103.80 ), 
					 'TH' => array('Thailand', 15.00, 100.00), 
					 'CG' => array('Congo, Rep of', -1.00, 15.00), 
					 'UG' => array('Uganda', 2.00, 33.00), 
					 'KZ' => array('Kazakhstan', 48.00, 68.00 ), 
					 'JP' => array('Japan', 36.00, 138.00), 
					 'AE' => array('United Arab Emirates', 24.00, 54.00), 
					 'US' => array('United States of America', 38.00, -98.00), 
					 'AL' => array('Albania', 41.00, 20.00), 
					 'AR' => array('Argentina', -34.00, -64.00), 
					 'AU' => array('Australia', -25.00, 135.00), 
					 'AT' => array('Austria', 47.33, 13.33), 
					 'BO' => array('Bolivia, Plurinational State of', -17.00, -65.00), 
					 'BJ' => array('Benin', 9.50, 2.25), 
					 'BW' => array('Botswana', -22.00, 24.00), 
					 'BR' => array('Brazil', -10.00, -55.00), 
					 'BG' => array('Bulgaria', 43.00, 25.00), 
					 'CA' => array('Canada', 60.00, -96.00), 
					 'CF' => array('Central African Republic', 7.00, 21.00), 
					 'CL' => array('Chile', -30.00, -71.00), 
					 'CO' => array('Colombia', 4.00, -72.00), 
					 'HR' => array('Croatia', 45.17, 15.50), 
					 'CZ' => array('Czech Republic', 49.75, 15.00), 
					 'DK' => array('Denmark', 56.00, 10.00), 
					 'DO' => array('Dominican Republic', 19.00, -70.67 ), 
					 'EC' => array('Ecuador', -2.00, -77.50), 
					 'EG' => array('Egypt', 27.00, 30.00), 
					 'SV' => array('El Salvador', 13.83, -88.92), 
					 'FR' => array('France', 46.00, 2.00), 
					 'DE' => array('Germany', 51.50, 10.50), 
					 'GR' => array('Greece',  39.00, 22.00), 
					 'GT' => array('Guatemala', 15.50, -90.25), 
					 'HN' => array('Honduras', 15.00, -86.50), 
					 'HU' => array('Hungary', 47.00, 20.00), 
					 'IS' => array('Iceland', 65.00, -18.00), 
					 'IN' => array('India', 20.00, 77.00), 
					 'IQ' => array('Iraq', 33.00, 44.00), 
					 'IE' => array('Ireland', 53.00, -8.00), 
					 'IL' => array('Israel', 31.50, 34.75), 
					 'IT' => array('Italy', 42.83, 12.83), 
					 'JM' => array('Jamaica', 18.25, -77.50), 
					 'JO' => array('Jordan', 31.00, 36.00), 
					 'KW' => array('Kuwait', 9.50, 47.75), 
					 'LT' => array('Lithuania', 56.00, 24.00), 
					 'MW' => array('Malawi', -13.50, 34.00), 
					 'MA' => array('Morocco', 32.00, -5.00), 
					 'RU' => array('Russian Federation', 60.00, 47.00), 
					 'NP' => array('Nepal', 28.00, 84.00), 
					 'NL' => array('Netherlands', 52.50, 5.75), 
					 'NZ' => array('New Zealand', -42.00, 174.00), 
					 'NI' => array('Nicaragua', 13.00, -85.00), 
					 'NE' => array('Niger', 16.00, 8.00), 
					 'NG' => array('Nigeria', 10.00, 8.00), 
					 'NO' => array('Norway', 62.00, 10.00), 
					 'PK' => array('Pakistan', 30.00, 70.00), 
					 'PA' => array('Panama', 9.00, -80.00), 
					 'PG' => array('Papua New Guinea', -6.00, 147.00), 
					 'PY' => array('Paraguay', -23.00, -58.00), 
					 'PE' => array('Peru', -10.00, -76.00), 
					 'PL' => array('Poland', 52.00, 20.00), 
					 'PT' => array('Portugal', 39.50, -8.00), 
					 'SY' => array('Syria, Arab Rep', 35.00, 38.00), 
					 'RO' => array('Romania', 46.00, 25.00), 
					 'RW' => array('Rwanda', -2.00, 30.00), 
					 'SA' => array('Saudi Arabia', 25.00, 45.00), 
					 'SN' => array('Senegal', 14.00, -14.00), 
					 'RS' => array('Serbia', 43.80, 21.00), 
					 'SK' => array('Slovakia', 48.67, 19.50), 
					 'VE' => array('Venezuela, Bolivarian Rep of', 8.00, -66.00), 
					 'SI' => array('Slovenia', 46.25, 15.17), 
					 'ES' => array('Spain', 40.00, -4.00), 
					 'SZ' => array('Swaziland', -26.50, 31.50 ), 
					 'SE' => array('Sweden', 62.00, 15.00), 
					 'CH' => array('Switzerland', 47.00, 8.00), 
					 'TJ' => array('Tajikistan', 39.00, 71.00), 
					 'TT' => array('Trinidad and Tobago', 10.66	-61.00), 
					 'TN' => array('Tunisia', 34.00, 9.00), 
					 'TR' => array('Turkey', 39.00, 35.00), 
					 'UA' => array('Ukraine', 49.00, 32.00), 
					 'GB' => array('United Kingdom', 54.00, -4.50), 
					 'UY' => array('Uruguay', -33.00, -56.00), 
					 'YE' => array('Yemen', 15.50, 47.50), 
					 'ZM' => array('Zambia', -15.00, 30.00), 
					 'ZW ' => array('Zimbabwe', -19.00, 29.00), 
					 'EH' => array('Western Sahara', 23.00, -14.00), 
					 'BF' => array('Burkina Faso', 13.00, -2.00), 
					 'ET' => array('Ethiopia', 8.00, 39.00), 
					 'AM' => array('Armenia', 40.00, 45.00), 
					 'AZ' => array('Azerbaijan', 40.50, 47.50), 
					 'BY' => array('Belarus', 53.00, 28.00), 
					 'EE' => array('Estonia', 59.00, 26.00), 
					 'GE' => array('Georgia', 42.00, 43.50), 
					 'AO' => array('Angola', -12.50, 18.50), 
					 'BI' => array('Burundi', -3.50, 30.00), 
					 'TD' => array('Chad', 15.00, 19.00), 
					 'SL' => array('Sierra Leone', 8.50, -11.50), 
					 'SD' => array('Sudan', 15.00, 30.00), 
					 'BT' => array('Bhutan', 27.50, 90.50), 
					 'FM' => array('Micronesia, Federated States of', 6.916, 158.25), 
					 'IR' => array('Iran, Islamic Rep of', 32.00, 53.00), 
					 'WF' => array('Wallis and Futuna', -14.00, -177.00 ), 
					 'GI' => array('Gibraltar', 36.13, -5.35), 
					 'MX' => array('Mexico', 23.00, -102.00), 
					 'BE' => array('Belgium', 50.83, 4.00), 
					 'KG' => array('Kyrgyzstan', 41.00, 75.00), 
					 'MN' => array('Mongolia', 46.00, 105.00), 
					 'UZ' => array('Uzbekistan', 41.00, 64.00), 
					 'GN' => array('Guinea', 11.00, -10.00)
				 );

		return (!empty($code) && isset($countries[$code])) ? $countries[$code] : $countries;

	}
	
	
    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Interactive Map Settings', 
            'manage_options', 
            'map-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        ?>
        <div class="wrap">         
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'map_map_page_group' );   
                do_settings_sections( 'map-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'map_map_page_group', // Option group
            'map_map_page', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Interactive Map Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'map-setting-admin' // Page
        );  

        add_settings_field(
            'page_id', // ID
            'Page ID', // Title 
            array( $this, 'id_number_callback' ), // Callback
            'map-setting-admin', // Page
            'setting_section_id' // Section           
        );      

    }
    
    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['page_id'] ) )
            $new_input['page_id'] = absint( $input['page_id'] );
		return $new_input;
	}
	
    /** 
     * Get the settings option array and print one of its values
     */
    public function id_number_callback()
    {
        printf(
            '<input type="text" id="page_id" name="map_map_page[page_id]" value="%s" />',
            isset( $this->options['page_id'] ) ? esc_attr( $this->options['page_id']) : ''
        );
    }
}
$ideal_interactive_map = new ideal_interactive_map();