<?php

namespace SktThemes;

if ( ! class_exists( '\SktThemes\PageTemplatesDirectory' ) ) {
	class PageTemplatesDirectory {

		/**
		 * @var PageTemplatesDirectory
		 */

		protected static $instance = null;

		/**
		 * The version of this library
		 * @var string
		 */
		public static $version = '1.0.0';

		/**
		 * Holds the module slug.
		 *
		 * @since   1.0.0
		 * @access  protected
		 * @var     string $slug The module slug.
		 */
		protected $slug = 'templates-directory';

		protected $source_url;

		/**
		 * Defines the library behaviour
		 */
		protected function init() {
			add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
			add_action( 'rest_api_init', array( $this, 'register_endpoints_gutenberg' ) );
			
			//Add dashboard menu page.
			add_action( 'admin_menu', array( $this, 'add_menu_page' ), 100 );
			//Add rewrite endpoint.
			add_action( 'init', array( $this, 'demo_listing_register' ) );
			//Add template redirect.
			add_action( 'template_redirect', array( $this, 'demo_listing' ) );
			//Enqueue admin scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_template_dir_scripts' ) );
			
			add_action( 'admin_enqueue_scripts', array( $this, 'gutenberg_enqueue_template_dir_scripts' ) );
			
			// Get the full-width pages feature
			add_action( 'init', array( $this, 'load_full_width_page_templates' ), 11 );
			// Remove the blank template from the page template selector
			// Filter to add fetched.
			add_filter( 'template_directory_templates_list', array( $this, 'filter_templates' ), 99 );
			
			add_filter( 'gutenberg_template_directory_templates_list', array( $this, 'gutenberg_filter_templates' ), 99 );
		}

		/**
		 * Enqueue the scripts for the dashboard page of the
		 */
		public function enqueue_template_dir_scripts() {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_directory' ) {
				if ( $current_screen->id === 'skt-templates_page_skt_template_directory' ) {
					$plugin_slug = 'sktb';
				}  
				$script_handle = $this->slug . '-script';
				wp_enqueue_script( 'plugin-install' );
				wp_enqueue_script( 'updates' );
				wp_register_script( $script_handle, plugin_dir_url( $this->get_dir() ) . $this->slug . '/js/script.js', array( 'jquery' ), $this::$version );
				wp_localize_script( $script_handle, 'importer_endpoint',
					array(
						'url'                 => $this->get_endpoint_url( '/import_elementor' ),
						'plugin_slug'         => $plugin_slug,
						'fetch_templates_url' => $this->get_endpoint_url( '/fetch_templates' ),
						'nonce'               => wp_create_nonce( 'wp_rest' ),
					) );
				wp_enqueue_script( $script_handle );
				wp_enqueue_style( $this->slug . '-style', plugin_dir_url( $this->get_dir() ) . $this->slug . '/css/admin.css', array(), $this::$version );
			}
		}
		
		
		
		public function gutenberg_enqueue_template_dir_scripts() {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_gutenberg' ) {
				if ( $current_screen->id === 'skt-templates_page_skt_template_gutenberg' ) {
					$plugin_slug = 'sktb';
				}  
				$script_handle = $this->slug . '-script';
				wp_enqueue_script( 'plugin-install' );
				wp_enqueue_script( 'updates' );
				wp_register_script( $script_handle, plugin_dir_url( $this->get_dir() ) . $this->slug . '/js/script-gutenberg.js', array( 'jquery' ), $this::$version );
				wp_localize_script( $script_handle, 'importer_gutenberg_endpoint',
					array(
						'url'                 => $this->get_endpoint_url( '/import_gutenberg' ),
						'plugin_slug'         => $plugin_slug,
						'fetch_templates_url' => $this->get_endpoint_url( '/fetch_templates' ),
						'nonce'               => wp_create_nonce( 'wp_rest' ),
					) );
				wp_enqueue_script( $script_handle );
				wp_enqueue_style( $this->slug . '-style', plugin_dir_url( $this->get_dir() ) . $this->slug . '/css/admin.css', array(), $this::$version );
			}
		}		

		/**
		 *
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		public function get_endpoint_url( $path = '' ) {
			return rest_url( $this->slug . $path );
		}

		/**
		 * Register Rest endpoint for requests.
		 */
		public function register_endpoints() {
			register_rest_route( $this->slug, '/import_elementor', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_elementor' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
			register_rest_route( $this->slug, '/fetch_templates', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'fetch_templates' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
		}
		
		
		public function register_endpoints_gutenberg() {
			register_rest_route( $this->slug, '/import_gutenberg', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_gutenberg' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
			register_rest_route( $this->slug, '/fetch_templates', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'fetch_templates' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
		}		

		/**
		 * Function to fetch templates.
		 *
		 * @return array|bool|\WP_Error
		 */
		public function fetch_templates( \WP_REST_Request $request ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$params = $request->get_params();
		}

		public function filter_templates( $templates ) {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_directory' ) {
				$fetched = get_option( 'sktb_synced_templates' );
			} else {
				$fetched = get_option( 'sizzify_synced_templates' );
			}
			if ( empty( $fetched ) ) {
				return $templates;
			}
			if ( ! is_array( $fetched ) ) {
				return $templates;
			}
			$new_templates = array_merge( $templates, $fetched['templates'] );

			return $new_templates;
		}
		
		
		public function gutenberg_filter_templates( $templates ) {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_gutenberg' ) {
				$fetched = get_option( 'sktb_synced_templates' );
			} else {
				$fetched = get_option( 'sizzify_synced_templates' );
			}
			if ( empty( $fetched ) ) {
				return $templates;
			}
			if ( ! is_array( $fetched ) ) {
				return $templates;
			}
			$new_templates = array_merge( $templates, $fetched['templates'] );

			return $new_templates;
		}		
		
		
		public function gutenberg_templates_list() {
			$defaults_if_empty = array(
				'title'            => __( 'A new SKT Templates', 'skt-templates' ),
				'description'      => __( 'Awesome SKT Templates', 'skt-templates' ),
				'import_file'      => '',
				'required_plugins' => array( 'skt-blocks' => array( 'title' => __( 'SKT Blocks – Gutenberg based Page Builder', 'skt-templates' ) ) ),
			);
			
			$gutenberg_templates_list = array(
			'gbrenovate-gutenberg'              => array(
					'title'       => __( 'GB Renovate', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/home-improvement-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/gb-renovate'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/gb-renovate/gb-renovate.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/gb-renovate/gb-renovate.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, renovate, interior designs, designs, kitchen appliances, Whole Home Makeovers, Crowdsourcing Platform, Furniture Re-Upholsterer, E-decorating Service, Home Window Dresser, Resale Sites, Home Accessories Decorator, Designer Rooms, Home Decor Services, Makers And Manufacturers, home decor, interior construction, home decorating, decoration, decor, furnishing articles, interior equipment, internal design, interior set-up, interior fit-out, remodeling, overhaul, improvement, reconstruction, betterment, modernization, redo, new look, refashion, redecoration, repair, revamp, restore, rehabilitation, retreading, refitting, renovation, retouch' ),
			),				
			'gbextreme-gutenberg'              => array(
					'title'       => __( 'GB Extreme', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/adventure-tours-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/gb-extreme/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/gb-extreme/gb-extreme.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/gb-extreme/gb-extreme.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, adventure, mountain, biking, hiking, extreme sports, tours, travel, exploit, escapade, event, stunt, quest, happening, trip, venture' ),
			),			
			'gbbarter-gutenberg'              => array(
					'title'       => __( 'GB Barter', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-shopping-ecommerce-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/gb-barter/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/barter/barter.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/gb-barter/gb-barter.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, barter, eCommerce, WooCommerce, shop, shopping, sales, selling, online store, digital payment, PayPal, storefront, b2b, b2c' ),
				),
				'gbposterity-gutenberg'              => array(
					'title'       => __( 'GB Posterity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity/posterity.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/gb-posterity/gb-posterity.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, posteriy, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),	
				'gbposteritydark-gutenberg'              => array(
					'title'       => __( 'GB Posterity Dark', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity-dark/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity-dark/posterity-dark.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/gb-posterity-dark/gb-posterity-dark.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, posteriy, posteriydark, dark, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),		
				'gbnature-gutenberg'              => array(
					'title'       => __( 'GB Nature', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbnature/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbnature/free-gbnature.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbnature/gb-nature.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, atmosphere, environmental, climate, nature, world, ecology, science, surrounding, natural world, surround, locality, neighborhood, psychology, scenery, sphere, scene, nature, spot, mother nature, wildlife, ecosystem, work, area, place, god gift, globe, environmental organizations, non profit, NGO, charity, donations, clean, fresh, good looking, greenery, green color, house, landscape, creation, flora, locus, air, planet, healing, circumambience' ),
				),
				'gbhotel-gutenberg'              => array(
					'title'       => __( 'GB Hotel', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbhotel/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbhotel/gb-hotel.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbhotel/gb-hotel.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, Motels, accommodation, Motel accommodation, Hostels, backpackers , Apartments, Bed & Breakfasts, Holiday Homes, Homestays, Holiday Parks, Campgrounds, Farmstays, Luxury Lodges, Boutiques, Lodges, houses, pavilions, stays, gatehouse, hall, club, reside, rent rooms, inhabits, cottage, retreat, main building, clubhouse, hostelry, stays, lodging, pubs, traveler, service, hospices, room, hoteles, guests, facilities, hotel staff, location, hospitality, hotel management, catering, hostelries, roadhouses, bars, resort, canal, innkeeper, hotel accommodation, reservations, hotel business, place, in hotels, settlements, schools, establishments, institutions, properties, farmhouses' ),
				),
				'gbcharity-gutenberg'              => array(
					'title'       => __( 'GB Charity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbcharity/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbcharity/gb-charity.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbcharity/gb-charity.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, kindness, kindliness, compassion, feeling, goodwill, generosity, gentleness, charitableness, tolerance, mercy, humanitarianism, understanding, kindliness, liberality,nurture, relief, generosity, help, leniency, allowance, kindliness, favor, selflessness, unselfishness, love, kindheartedness, support, tenderness, goodness, donation, charitable foundation, offering, indulgence, kindliness, fund, assistance, benefaction, contribution, generosity, brotherly love, caring, clemency, concern, pity, sympathy, benignity, empathy, welfare, charities, gift, aid, help, grace' ),
				),
				'gbfitness-gutenberg'              => array(
					'title'       => __( 'GB Fitness', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbfitness/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbfitness/gb-fitness.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbfitness/gb-fitness.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, health, fitness, coach, well-being, good physical condition, healthiness, fitness, physical fitness, haleness, good trim, good shape, fine fettle, good kilter, robustness, strength, vigour, soundness, discipline, yoga, meditation, reiki, healing, weight loss, pilates, stretching, relaxation, workout, mental, gymnasium, theater, action, arena, gymnastics, exercise, health club, fitness room, health spa, work out, weight room, working out, sports hall, welfare centre, fitness club, wellness area, workout room, spa, high school, sport club, athletic club, fitness studio, health farm, establishment, gym membership, junior high, sports club, health-care centre, exercise room, training room, fitness suite, health centre, beauty centre, my gym, country club, fite, gym class, medical clinic, med centre, free clinic, medical facilities, dispensary, health posts, healing center, health care facility, medical station, health care establishment, health establishment, medical establishment, centre de santé, medical centres, medical, hospital, polyclinic, healthcare facilities, treatment centre, medical institutions, health care institution, health units' ),
				),
				'gbconstruction-gutenberg'              => array(
					'title'       => __( 'GB Construction', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbconstruction/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbconstruction/gb-construction.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbconstruction/gb-construction.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, inventor, originator, founder, maker, mastermind, engineer, builder, planner, designer, patron, originator, initiator, entrepreneur, deviser, author, director, manufacturer, designers, artificer, artist, person, agent, innovator, constructor, architecture, draftsman, planner, designer, progenitor, director, producer, planner, craftsmen, peacemaker, agent, artisan, producer, maker, generator, fabricator, craftsperson, structure, design, organizer, architectural, pioneer, founding father, author, brains, originators, instigators, implementer, contractor, contriver, real estate developer, building contractor, design engineer, property developer, brick layer, land developer, establisher, handyman, maintenance, decor, laborer, land consulting, roofing, artist, portfolio, profile, roofttop, repair, real estate, colorful, adornments, cenery, surroundings, home decor, color scheme, embellishment, garnish, furnishings, interior decorations, interiors, set design, scenography, flourish, design, redecorating, decorative style, ornaments, environments, designs, interior construction, painting, trimming, interior decorating, decoration, emblazonry, home decorating' ),
				),
				);
				
				foreach ( $gutenberg_templates_list as $template => $properties ) {
				$gutenberg_templates_list[ $template ] = wp_parse_args( $properties, $defaults_if_empty );
			}

			return apply_filters( 'gutenberg_template_directory_templates_list', $gutenberg_templates_list );
		}

		/**
		 * The templates list.
		 *
		 * @return array
		 */
		public function templates_list() {
			$defaults_if_empty = array(
				'title'            => __( 'A new SKT Templates', 'skt-templates' ),
				'description'      => __( 'Awesome SKT Templates', 'skt-templates' ),
				'import_file'      => '',
				'required_plugins' => array( 'elementor' => array( 'title' => __( 'Elementor Page Builder', 'skt-templates' ) ) ),
			);

			$templates_list = array(
				'posterity-elementor'              => array(
					'title'       => __( 'Posterity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity/posterity.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/posterity/posterity.json'),
					'keywords'    => __( ' posteriy, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),
				'posteritydark-elementor'              => array(
					'title'       => __( 'Posterity Dark', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity-dark/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity-dark/posterity-dark.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/posterity-dark/posterity-dark.json'),
					'keywords'    => __( ' posteriy, posteriydark, dark, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),			
				'sanitization-elementor'              => array(
					'title'       => __( 'Sanitization', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/sanitization/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/sanitization/sanitization.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/sanitization/sanitization.json'),
					'keywords'    => __( ' wash, health, fitness, stress, relief, disinfectant, depersonalization, refining, remediation, clean-up, dry cleaner, purifying, refinement, impersonalizing, clean, cleanse, wipe, sponge, scrub, mop, rinse, scour, swab, hose down, sanitize, sanitization, disinfect, disinfection, cleaning, decontaminate, antiseptic, sanitary, janitor, lean, freshen, purify, deodorize, deodrant, depurate, depollute, hygiene, residue, sterilise, sterilize, napkin', 'skt-templates' ),
				),						
				'pinminimal-elementor'              => array(
					'title'       => __( 'Pin Minimal', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://www.pinnaclethemes.net/themedemos/pin-minimal-free/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/app/minimal.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/app/minimal.json'),
					'keywords'    => __( ' minimal, minimalistic, white, flat, material, simple, clean, natural', 'skt-templates' ),
				),														
				'handyman-elementor'              => array(
					'title'       => __( 'Handy Man', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/handy/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/handy/handyman.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/handy/handyman.json'),
					'keywords'    => __( ' help, helper, helpmate, home security system, hot tub, spa, lamp repair, handyman, landscaping, lawncare, lockset adjustment, maid service, molding installation, moving, paint removal, painting, patio stone installation, pest control, plumbing repair, porch, remodeling basement, remodeling bathroom, remodeling kitchen, roofing, safety modification, sealing driveway, senior living modification, septic system repair, shelf installation, shelving, skylight installation, soundproofing, sprinkler repair, sprinkler system installation, stain removal, staining furniture, stone work, storage area construction, storage area repair, swapping a toilet, swimming pool maintenance, tiling, trash removal, wall building, water purification, water softening, window cleaning, welding, window installation, window repair, window screen, duty, work, waste removal, welder, repair, adjustment, improvment, overhaul, reconstruction, rehabilitation, maintenance, welding service, alteration, remaking, resetting', 'skt-templates' ),
				),	
				'cctv-elementor'              => array(
					'title'       => __( 'CCTV', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/cctv/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/cctv/cctv.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/cctv/cctv.json'),
					'keywords'    => __( ' home automation requirements, home protection systems, residential protection,, commercial protection, CCTV security systems, individuals security, Security guards,, watching crime, CCTV Cameras, Crime Check, safety equipment stores, spy camera espionage, surveillance systems bureaus, bar-code scanner manufacturers, DVR&#39;s, anti theft equipment, biometric system companies, parking management system, video door phone seller&#39;s, stun gun, dome cameras, IP camera, Bullet IR Night Vision Camera, Special purpose cameras, vision cameras, Dome IR Night vision cameras', 'skt-templates' ),
				),					
				'pvcpipes-elementor'              => array(
					'title'       => __( 'PVC Pipes', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/pvcpipes/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pvcpipes/pvcpipes.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pvcpipes/pvcpipes.json'),
					'keywords'    => __( ' fittings, PVC Pipes, maintenance, pipe cutter, office equipment, tap, maintenance, renovation, plumbing, electrician companies, home repair business, remodeling, plumbing firms, renovation, carpentry, construction businesses, building parts, bathroom accessories, plumbing parts, water pipes, showers, tools, kitchen hardware, bath equipment', 'skt-templates' ),
				),					
				'hometheater-elementor'              => array(
					'title'       => __( 'Home Theater', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/hometheatre/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/hometheatre/home-theater.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/hometheatre/home-theater.json'),
					'keywords'    => __( ' hometheater, home theatre, sound equipments, equipment, tools, apparatus, devices, electronic devices, home decor, lighting, gear, devices, mobile phones, Home appliances, gadgets, Makeovers, decorating, decoration, remodel, refashion, mechanism, add-ons, consumer goods, Washing Machine, Bluetooth speakers, Audio Systems, manufacturer, music entertainment business, DSLR camera, electronics products', 'skt-templates' ),
				),					
				'flowershop-elementor'              => array(
					'title'       => __( 'Flowershop', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/flowershop/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/flowershop/flowershop.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/flowershop/flowershop.json'),
					'keywords'    => __( ' flowershop, market, shop, store, flower shop, food shop, snacks shop, bakery store, artificial flower shop, cake shop, grocery store, shopping, foodstuff, goods, groceries, food market, grocer, foodstore, mall, food retailing, supermarket, mart, greengrocery, edibles, emporium, food product, corner shop, storefront, greengrocer, trade, mart, delicatessen, groceteria, comestible, place, department store, superette, tent, convenience store, provision, vegetable store, eatables, provision shop, victuals, boutique, trade, markets, purchase, supermarket, stock market, bazaars, sales, bazaar, sells, exchanges, businesses, convenience store, auctions, trading, deal, bargain, merchandise, commerce, stock exchange', 'skt-templates' ),
				),					
				'municipality-elementor'              => array(
					'title'       => __( 'Municipality', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/municipality/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/municipality/municipality.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/municipality/municipality.json'),
					'keywords'    => __( ' municipality, community, urban community, urban area, foundation, establishment, local government, city government, town government, policy, municipal government, municipal executive, municipal elections, Municipal law, municipal reform, welfare, district, village, city, town, metropolis, burg, province, non profit organization, NGO, governmental organizations, political jurisdictions, community resources, administrative agency, city club, food inspection, transportation, fire departments', 'skt-templates' ),
				),																		
				'summercamp-elementor'              => array(
					'title'       => __( 'Summer Camp', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/summercamp/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/summercamp/summer-camp.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/summercamp/summer-camp.json'),
					'keywords'    => __( ' summercamp, traveling, trek, happy movement, expedition, cruise, backpack, visit, trips, tour, vacationing, voyage, roaming, action, go, roll, move, journey, saffari, touring, journey, trip, go abroad, peregrinate, riding, journey, motion, movement, change, taking a trip, stay, holidaying, spring break, vacation, furlough, summer vacation, vacancies, vacation time, tour, travel, vacay, breaks, offseason, vacationing, resort, summer vacation, breakdown, weekends, recesses, rests, package, outings, staying, summer holiday, summer break, summer recess, major holidays, high holidays, big holiday, great holiday, long vacations, break, summering, long vacation, summer recreation, holiday, summer activities, summer enjoyment, summer entertainment, summer fun, summer gaiety, summer joviality, summer joy, summer merriment, summer pleasure, summer relax, summer relaxation, summer rest, summertime, entertainment, summertime fun, summertime joy, summertime pleasure, holiday period, summer leisure activities, summer camps, great festivals, large parties, summertime, major festivals, major feasts, spend the summer, festivals, main festivals, spring, midsummer, estate', 'skt-templates' ),
				),							
				'association-elementor'              => array(
					'title'       => __( 'Association', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/association/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/association/association.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/association/association.json'),
					'keywords'    => __( ' association, kindness, kindliness, compassion, feeling, goodwill, generosity, gentleness, charitableness, tolerance, mercy, humanitarianism, understanding, kindliness, liberality,nurture, relief, generosity, help, leniency, allowance, kindliness, favor, selflessness, unselfishness, love, kindheartedness, support, tenderness, goodness, donation, charitable foundation, offering, indulgence, kindliness, fund, assistance, benefaction, contribution, generosity, brotherly love, caring, clemency, concern, pity, sympathy, benignity, empathy, welfare, charities, gift, aid, help, grace', 'skt-templates' ),
				),																							
				'aquarium-elementor'              => array(
					'title'       => __( 'Aquarium', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/aquarium/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/aquarium/aquarium.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/aquarium/aquarium.json'),
					'keywords'    => __( ' aquarium shops, decoration fish dealers, aquarium accessories service providers, public aquariums, fishbowl, aquatic museum, marine exhibit, vivarium, Aquarius, fishery, aquarium park, goldfish, aquapark, menagerie, pond, fish pond, dolphinarium, fish tanks, goldfish bowl, seaquarium, seaworld', 'skt-templates' ),
				),									
				'swimmingpool-elementor'              => array(
					'title'       => __( 'Swimming Pool', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/swimming-pool/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/swimming-pool/swimming-pool.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/swimming-pool/swimming-pool.json'),
					'keywords'    => __( ' swimmingpool, waterfront, seaside, seashore, coastal region, seaboard, foreshore, Swimming pools , Wellness, Vacation Rentals, Tour Guide, Welcome Center, Watersports Rentals, Travel, Consultant, massage services, facial treatments, Transportation, VIP Services, Personalized services, Car rental, restaurants and local event', 'skt-templates' ),
				),								
				'eventplanners-elementor'              => array(
					'title'       => __( 'Event Planners', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/event-planners/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/event-planners/event-planners.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/event-planners/event-planners.json'),
					'keywords'    => __( ' event planners, act, business, function, marriage, banquet, celebration, parties, entertainment, barbecue, bash, social, reception, engagement, birthday, speaker session, Networking sessions, Conferences, seminar, half-day event, Workshops, classes, VIP experiences, Sponsorships,Trade shows, expos, Awards and competitions, Festivals and parties, event marketing, B2C, B2B marketing, meetups, wordcamps, education and training', 'skt-templates' ),
				),									
				'schooluniform-elementor'              => array(
					'title'       => __( 'School Uniform', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/school-uniform/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/school-uniform/school-uniform.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/school-uniform/school-uniform.json'),
					'keywords'    => __( ' uniform, clothing, fashion, apparel stores, luxurious undergarments, boutique, clothing, garments, dress, attire, wardrobe, outfit, apparel, nightgown, fashion boutique, appearance, looks, boutique, girlie, cloth store, fashion store, feminine, clothes, custom tailoring, alteration, handmade, cloths repair, clothier, fashion, custom wear, uniform, retail, store, wholesaler, shop, fashion industry, clothing repair centers, tailoring service companies, tailor house owner, stylist, fashion designer, model, professional tailor, or an online store manager, cutting approaches, stitching methods, boutique rules, bridal collections, formal dress collections, A-line designers, unstitch fabric', 'skt-templates' ),
				),						
				'tailor-elementor'              => array(
					'title'       => __( 'Tailor', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/tailor/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/tailor/tailor.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/tailor/tailor.json'),
					'keywords'    => __( ' tailor, clothing, fashion, apparel stores, luxurious undergarments, boutique, clothing, garments, dress, attire, wardrobe, outfit, apparel, nightgown, fashion boutique, appearance, looks, boutique, girlie, cloth store, fashion store, feminine, clothes, custom tailoring, alteration, handmade, cloths repair, clothier, fashion, custom wear, uniform, retail, store, wholesaler, shop, fashion industry, clothing repair centers, tailoring service companies, tailor house owner, stylist, fashion designer, model, professional tailor, or an online store manager, cutting approaches, stitching methods, boutique rules, bridal collections, formal dress collections, A-line designers, unstitch fabric', 'skt-templates' ),
				),					
				'tatto-elementor'              => array(
					'title'       => __( 'Tatto', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/tatto/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/tatto/tatto.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/tatto/tatto.json'),
					'keywords'    => __( ' tatto, body art, tattoo making, body art, tattoo lettering, body piercing, art, artist, creativity, tattoo shop, tattoo studio, tattoo parlous, salon, makeup artist', 'skt-templates' ),
				),										
				'mountainbiking-elementor'              => array(
					'title'       => __( 'Mountain Biking', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/mountain-biking/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/mountain-biking/mountain-biking.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/mountain-biking/mountain-biking.json'),
					'keywords'    => __( ' mountainbiking, traveling, trek, happy movement, expedition, cruise, backpack, visit, trips, tour, vacationing, voyage, roaming, action, go, roll, move, journey, saffari, touring, journey, trip, go abroad, peregrinate, riding, journey, motion, movement, change, taking a trip, stay, holidaying, spring break, vacation, furlough, summer vacation, vacancies, vacation time, tour, travel, vacay, breaks, offseason, vacationing, resort, summer vacation, breakdown, weekends, recesses, rests, package, outings, staying, summer holiday, summer break, summer recess, major holidays, high holidays, big holiday, great holiday, long vacations, break, summering, long vacation, summer recreation, holiday, summer activities, summer enjoyment, summer entertainment, summer fun, summer gaiety, summer joviality, summer joy, summer merriment, summer pleasure, summer relax, summer relaxation, summer rest, summertime, entertainment, summertime fun, summertime joy, summertime pleasure, holiday period, summer leisure activities, summer camps, great festivals, large parties, summertime, major festivals, major feasts, spend the summer, festivals, main festivals, spring, midsummer, estate', 'skt-templates' ),
				),								
				'repairman-elementor'              => array(
					'title'       => __( 'Repairman', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/repairman/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/repairman/repairman.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/repairman/repairman.json'),
					'keywords'    => __( ' repairman, window installations, Doors fixer, handyman, repair services, remodeling, window and door cleaning services, manufacturers, Aluminum Door manufacturing, Repair Business, UPVC Window, Suppliers, home improvement industry, strategic consultancy, local businesses, Sliding Windows installer', 'skt-templates' ),
				),					
				'lights-elementor'              => array(
					'title'       => __( 'Lights', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/lights/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/lights/lights.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/lights/lights.json'),
					'keywords'    => __( ' lights, lighting company, lighting shop, led lights, led shop, interior accessories, decor items, handmade, ceramics items, chandelier stores, light bulbs retailers, fixtures shops, lamp posts, lighting accessories, designer lamp studio' ),
				),				
				'moviemaker-elementor'              => array(
					'title'       => __( 'Movie Maker', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/moviemaker/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/moviemaker/moviemaker.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/moviemaker/moviemaker.json'),
					'keywords'    => __( ' moviemaker, film producer, stage director, cinematographer, movie director, moviemaker, head master, headteacher, filmmaking, directing, producer, film, moviegoer, cinema, headmaster, stage performer, stage manager, head, directorial, videographer, artistic director, movies, manager, editor, director, director-level, film-makers, casting director, camera operator, pictures, stock footage, achiever, line producer, implementor, scenario writer, superintendent, film fan, theater director, cameraman, movie fan, moving pictures, camera guy, filmmakers, realizer, photographer, cinematographers, camera man, movie theater, president, cinematography, video, moviemakers, film buff, cinema operator, theatrical producer, film set, executive, ribbons, actor, actress, model, modelling, cast, crew, photographer, makeup, artist, makeup artist, hair styler' ),
				),				
				'sktvideography-elementor'              => array(
					'title'       => __( 'SKT Videography', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-videographer-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/skt-videography/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/skt-videography/skt-videography.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/skt-videography/skt-videography.json'),
					'keywords'    => __( ' videography, wedding, engagement, nuptials, matrimony, ring, ceremony, ritual, vows, anniversary, celebration, photography, rites, union, big day, knot, aisle, wive, husband, wife, esposo, esposa, hitched, plunged, gatherings, events, video, reels, youtube, film' ),
				),				
				'bicycleshop-elementor'              => array(
					'title'       => __( 'Bicycleshop', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-cycling-club-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/bicycleshop/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/bicycleshop/bicycleshop.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/bicycleshop/bicycleshop.json'),
					'keywords'    => __( ' bicycleshop, woocommerce, ecommerce, shop, store, sales, shopping, commerce' ),
				),			
				'barter-elementor'              => array(
					'title'       => __( 'Barter', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-shopping-ecommerce-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/barter/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/barter/barter.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/barter/barter.json'),
					'keywords'    => __( ' barter, eCommerce, WooCommerce, shop, shopping, sales, selling, online store, digital payment, PayPal, storefront, b2b, b2c' ),
				),
				'software-elementor'              => array(
					'title'       => __( 'Software', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-software-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/software/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/software/free-software.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/software/free-software.json'),
					'keywords'    => __( ' software, program, freeware, application, operating system, laptop, computer, courseware, productivity, file management' ),
				),
				'bathware-elementor'              => array(
					'title'       => __( 'Bathware', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/bathware/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/bathware/bathware.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/bathware/bathware.json'),
					'keywords'    => __( ' bathware, bathroom fittings, bathroom stores, bathroom accessories, superior bathroom service providers, fashionable rest room designers, units, basins, tap, faucet, washbasin, baths, showers, tiles, bathroom, building interior design, furniture, shower screens, freestanding, bathroom vanity, marble, home improvement firms', 'skt-templates' ),
				),
				'digital-agency-elementor'              => array(
					'title'       => __( 'Digital Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/digital-agency/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/digital-agency/digital-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/digital-agency/digital-agency.json'),
					'keywords'    => __( ' digital-agency, agency, online, digital, consulting, corporate, business, small business, b2b, b2c, financial, investment, portfolio, management, discussion, advice, solicitor, lawyer, attorney, legal, help, SEO, SMO, social', 'skt-templates' ),
				),
				'zym-elementor'              => array(
					'title'       => __( 'Zym', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/zym/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/zym/zym.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/zym/zym.json'),
					'keywords'    => __( ' zym, fitness, yoga, gym, crossfit, studio, health, wellness, wellbeing, care, giving, nursing, body, bodybuilding, sports, athletes, boxing, martial, karate, judo, taekwondo, personal trainer, guide, coach, life skills', 'skt-templates' ),
				),
				'petcare-elementor'              => array(
					'title'       => __( 'Pet Care', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/pet-care/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pet-care/pet-care.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pet-care/pet-care.json'),
					'keywords'    => __( ' pet-care, pets, animals, cats, dogs, vets, veterinary, caring, nursing, peta, charity, donation, fundraiser, pet, horse, equestrian, care, orphan, orphanage, clinic, dog walking, dog grooming, boarding, retreat, pet sitters', 'skt-templates' ),
				),
				'bony-elementor'              => array(
					'title'       => __( 'Bony', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/bony/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/bony/bony.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/bony/bony.json'),
					'keywords'    => __( ' bony, orthopaedic, chriropractor, orthodontist, physiotherapy, therapy, clinic, doctor, nurse, nursing, care, caring, osteopathy, arthritis, body, pain, spine, bone, joint, knee, walk, low, back, posture', 'skt-templates' ),
				),
				'lawzo-elementor'              => array(
					'title'       => __( 'Lawzo', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/lawzo/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/lawzo/lawzo.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/lawzo/lawzo.json'),
					'keywords'    => __( ' lawzo, lawyer, attorney, justice, law, solicitor, general, legal, consultation, advice, help, discussion, corporate, advocate, associate, divorce, civil, lawsuit, barrister, counsel, counsellor, canonist, firm', 'skt-templates' ),
				),
				'launch-elementor'              => array(
					'title'       => __( 'Launch', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/launch/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/launch/launch.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/launch/launch.json'),
					'keywords'    => __( ' launch, folio, leaf sheet, side, recto verso, signature, surface, piece of paper, sheet of paper, flyleaf paper, eBook, book, journal, author, reading, sample, e-book, paperback, hardcover', 'skt-templates' ),
				),
				'shudh-elementor'              => array(
					'title'       => __( 'Shudh', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/shudh/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/shudh/shudh.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/shudh/shudh.json'),
					'keywords'    => __( ' shudh, minimal, minimalism, minimalistic, clean, tidy, art, slight, tiny, little, limited, small, less, least, nominal, minimum, basal, token, lowest', 'skt-templates' ),
				),
				'resume-elementor'              => array(
					'title'       => __( 'Resume', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/resume/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/resume/resume.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/resume/resume.json'),
					'keywords'    => __( ' resume, job, cv, curiculum vitae, online, portfolio, profile, digital, hired, hiring, seeker, candidate, interview, exam, experience, solutions, problems, skills, highlights, life, philosophy, manpower, template, format, word, document', 'skt-templates' ),
				),
				'fitt-elementor'              => array(
					'title'       => __( 'Fitt', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/fitt/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/fitt/fitt.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/fitt/fitt.json'),
					'keywords'    => __( ' fitt, fitness, yoga, gym, crossfit, studio, health, wellness, wellbeing, care, giving, nursing, body, bodybuilding, sports, athletes, boxing, martial, karate, judo, taekwondo, personal trainer, guide, coach, life skills', 'skt-templates' ),
				),
				'theart-elementor'              => array(
					'title'       => __( 'The Art', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/theart/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/theart/theart.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/theart/theart.json'),
					'keywords'    => __( ' theart, Crafts and Handmade Goods, beauty, Advertising, makeover, Graphic Artist, Tattoo Designs, Calligraphy Studio, artist, Art Dealer, Airbrush Artist, Antique', 'skt-templates' ),
				),
				'photodock-elementor'              => array(
					'title'       => __( 'Photodock', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/photodock/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/photodock/photodock.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/photodock/photodock.json'),
					'keywords'    => __( ' photodock, portfolio, creative, report, document, paper, information, details, essay, sketch, figure, portrait, painting, image, descriptive, study, description, depiction, source, account, biography, draft, picture, registry, book, profile, record, communication, register, mark, post, report, file, mark, certificate, journalism, papers, contract, note, catalog, form, text, instructions', 'skt-templates' ),
				),
				'cats-elementor'              => array(
					'title'       => __( 'Cats', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/cats/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/cats/cats.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/cats/cats.json'),
					'keywords'    => __( ' cat, pets, animals, cats, dogs, vets, veterinary, caring, nursing, peta, charity, donation, fundraiser, pet, horse, equestrian, care, orphan, orphanage, clinic, dog walking, dog grooming, boarding, retreat, pet sitters', 'skt-templates' ),
				),
				'events-elementor'              => array(
					'title'       => __( 'Events', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/events/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/events/events.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/events/events.json'),
					'keywords'    => __( ' events, event, management, celebration, ceremony, appearance, holiday, occasion, situation, affair, function, proceeding, meeting, lunch, dinner, meetup, game, match, tournament, bout, contest, result, aftermath, happening, party, DJ, dance', 'skt-templates' ),
				),
				'beautycuts-elementor'              => array(
					'title'       => __( 'Beauty Cuts', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/beautycuts/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/beautycuts/beautycuts.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/beautycuts/beautycuts.json'),
					'keywords'    => __( ' beautycuts, beautiful, artistry, hair,cut, hairscut, hairstyle, wig, elegance, good looks, grace, refinement, style, bloom, exquisiteness, fairness, fascination, glamor, loveliness, polish', 'skt-templates' ),
				),
				'library-elementor'              => array(
					'title'       => __( 'Library', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/library/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/library/library.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/library/library.json'),
					'keywords'    => __( ' library, librarian, book, room, bibliotheca, reference, media, center, excellence, ebook, elearning, learn, magazine, fiction, album, essay, edition, brochure, copy, booklet, pamphlet, paper, paperback, kindle, writing, write, novel, atlas, manual, textbook, bestseller, encyclopedia, opus, periodical, portfolio, reprint, preprint, thesaurus, scroll, record, diary, notebook, notepad, bill', 'skt-templates' ),
				),
				'tutor-elementor'              => array(
					'title'       => __( 'Tutor', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/tutor/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/tutor/tutor.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/tutor/tutor.json'),
					'keywords'    => __( ' educate, tutor, learning, guide, guidance, coach, help, advice, counselling, counsel, lecturer, instruct, instruction, discipline, disciple, direct, mentor, private, tutorial, professor, preceptor, teach, teaching, student, class, classroom, e learning, ebook, student', 'skt-templates' ),
				),
				'welder-elementor'              => array(
					'title'       => __( 'Welder', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/welder/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/welder/welder.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/welder/welder.json'),
					'keywords'    => __( ' welder, repair, adjustment, improvment, overhaul, reconstruction, rehabilitation, maintenance, welding service, alteration, remaking, resetting', 'skt-templates' ),
				),
				'legalexpert-elementor'              => array(
					'title'       => __( 'Legal Expert', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/legalexpert/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/legalexpert/legalexpert.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/legalexpert/legalexpert.json'),
					'keywords'    => __( ' lawyer, attorney, justice, law, solicitor, general, legal, consultation, advice, help, discussion, corporate, advocate, associate, divorce, civil, lawsuit, barrister, counsel, counsellor, canonist, firm', 'skt-templates' ),
				),
				'dairyfarm-elementor'              => array(
					'title'       => __( 'Dairy Farm', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/dairyfarm/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/dairyfarm/dairyfarm.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/dairyfarm/dairyfarm.json'),
					'keywords'    => __( ' bakery, cultivate, raise, grow, farmer, farmhouse, work, agriculture, breeding, cattle, nature, natural, culture, farmland, raising, simple, clean, garden, dairy products, dairy farm, cream, plantation, cheese factory, estate, cowshed, cattle farm', 'skt-templates' ),
				),
				'tea-elementor'              => array(
					'title'       => __( 'Tea', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/tea/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/tea/tea.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/tea/tea.json'),
					'keywords'    => __( ' tea house, cafe, teashop, tearooms, dining, cafeteria, establishment , luncheonette, small restaurant, tea parlor, lunchroom, tea parlour, restaurant, pavilion tea, drink, teatime, brunch, beverage, party, meal, snacks, chocolate, sweet, latte, food, cafe, espresso, decaf, mocha, coffeehouse, diner, burnt umber, joe, brew, caffeine, tawny, cup of coffee, sepia, coffee bean, bean, chestnut, coffee berry, cafés, cappuccino, hazel, cafeteria, deep brown, beverage, beer, coffees, hot, slang, rink, water, high tea, white coffee, coffee shop, coffee bar, restaurant, snack bar, refreshment, relief, stress free, coffee lounge, fresh pot, milk, cafe bar, coffee milk, cheesecake factory, bistro, brasserie, meal, eatery', 'skt-templates' ),
				),
				'grocery-elementor'              => array(
					'title'       => __( 'Grocery', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/grocery/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/grocery/grocery.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/grocery/grocery.json'),
					'keywords'    => __( ' grocery, kirana, store, ecommerce, woocommerce, online, supermarket, market, groceries, food, shopping, buy, discount, coupons, online, basket, cart, groceries, mall', 'skt-templates' ),
				),
				'herbal-elementor'              => array(
					'title'       => __( 'Herbal', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/herbal/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/herbal/herbal.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/herbal/herbal.json'),
					'keywords'    => __( ' nourishment, victuals, nutrient, nutriment, foodstuffs, goodness, beneficial, nurtures, edibles, eatables, vitamins, minerals, food products, chow, feed, grub, food items, mends, nutritional value, nutrimental, wholesomenes, nourishment, diet, groceries, conditioners, nutriments, drugs, solids, agri-foodstuffs, homemade, good stuff, herbs, plant, vegetable, plant-based, herb tea, vegetable, crop, grassy, botanical, floral, herbal medicine, medicinal herbs, grass, verdant, herbaceous, grass up, botanic, medicinal plants, weed, herbage, plant origin, flavorer, herbarium, mossy, grasses, flavourer, garden, vegetative, Phyto therapeutic, fruity, vegetal, planting, medicinal herb, herbal remedies, vegetable origin, flavoring, seasoner, herbal medicinal products, harvest, cultivate, agriculture, agriculture products, Ayurveda, unani, ayurvedic, ayus, acupuncture, homeopathy, naturopathy, yoga, reiki, meditation, chiropractic, allopathic, homeopathic, metaphysical concept, healing method, therapeutic touch, relaxation technique, spiritual healing, medical treatment, artificial insemination, anaesthesia, aromatherapy, Artificial Feeding, acupressure, analgesia', 'skt-templates' ),
				),
				'nutristore-elementor'              => array(
					'title'       => __( 'Nutristore', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/nutristore/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/nutristore/nutristore.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/nutristore/nutristore.json'),
					'keywords'    => __( ' nutristore, nourishment, victuals, nutrient, nutriment, foodstuffs, goodness, beneficial, nurtures, edibles, eatables, vitamins, minerals, food products, chow, feed, grub, food items, mends, nutritional value, nutrimental, wholesomenes, fuel, meat, feeding, keep, nourishment, finger food, nosh, comestible, cuisine, diet, groceries, conditioners, nutriments, drugs, solids, agri-foodstuffs, health, fitness, herbal,', 'skt-templates' ),
				),
				'shopzee-elementor'              => array(
					'title'       => __( 'Shopzee', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/shopzee/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/shopzee/shopzee.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/shopzee/shopzee.json'),
					'keywords'    => __( ' eye wear, specs, goggles, bifocals, shades, exhibition, vision, opera glasses, online store, handcrafted glasses, sun glasses, online optical business, fashion, fancy frames, dealers, lenskart, online eyeglasses, eyewear manufacturers, Traditional Ecommerce Business, B2C, B2B, C2B, C2C, D2C, Business to consumer, Business to business, Consumer to business, Consumer to consumer, Direct to consumer, Wholesaling, Dropshipping, Subscription service, distributing services, delivery services, shippings services, millennial generation, discount shop, discount store, convenience store, corner store, disposals store, grocery store, retail store, thrift store, store detective, liquor store, app store, jewelry store, shoe store, hobby store, cold store, backing store, store card, multiple store, lays store on, army navy store, gun store, cigar store, e store, music store, convenience store, drug store, general store, variety store, dime store, hobby shop, buffer store, big-box store, second-hand store, building supply store, anchor store, computer store, country store, mens store, army-navy store, bags, handbags, ecommerce, e-commerce, shopping, coupon', 'skt-templates' ),
				),
				'beach-elementor'              => array(
					'title'       => __( 'Beach', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/beachresort/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/beachresort/beachresort.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/beachresort/beachresort.json'),
					'keywords'    => __( ' waterfront, seaside, seashore, coastal region, seaboard, foreshore, Swimming pools , Wellness, Vacation Rentals, Tour Guide, Welcome Center, Watersports Rentals, Travel, Consultant, massage services, facial treatments, Transportation, VIP Services, Personalized services, Car rental, restaurants and local event', 'skt-templates' ),
				),
				'activist-lite-elementor'              => array(
					'title'       => __( 'Activism', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-activism-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/activist/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/activist/free-activist.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/activist/free-activist.json'),
					'keywords'    => __( ' ngo, non profit, citizen, old age, senior living, kids, children, red cross, wwf, social, human rights, activists, donation, fundraiser, donate, help, campaign, activism', 'skt-templates' ),
				),									
				'fundraiser-elementor'              => array(
					'title'       => __( 'Fundraiser', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/fundraising-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/fundraiser/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/fundraiser/fundraiser.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/fundraiser/fundraiser.json'),
					'keywords'    => __( ' charity, fundraiser, church, donation, donate, fund, trust, association, foundation, cause, aid, welfare, relief, funding, handouts, gifts, presents, largesse, lease, donations, contributions, grants, endowments, ngo, non profit, organization, non-profit, voluntary, humanitarian, humanity, social, generosity, generous, philanthropy, scholarships, subsidies, subsidy', 'skt-templates' ),
				),																					
				'charityt-elementor'              => array(
					'title'       => __( 'Charity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-charity/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/charity/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/charity/free-charity.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/charity/free-charity.json'),
					'keywords'    => __( ' charity, fundraiser, church, donation, donate, fund, trust, association, foundation, cause, aid, welfare, relief, funding, handouts, gifts, presents, largesse, lease, donations, contributions, grants, endowments, ngo, non profit, organization, non-profit, voluntary, humanitarian, humanity, social, generosity, generous, philanthropy, scholarships, subsidies, subsidy', 'skt-templates' ),
				),					
				'mydog-elementor'              => array(
					'title'       => __( 'My Dog', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-pet-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/mydog/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/mydog/free-mydog.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/mydog/free-mydog.json'),
					'keywords'    => __( ' pet, dog, veterinary, animal, husbandry, livestock, aquarium, cat, fish, mammal, bat, horse, equestrian, friend', 'skt-templates' ),
				),
				'film-elementor'              => array(
					'title'       => __( 'FilmMaker', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-video-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/film/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/film/free-filmmaker.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/film/free-filmmaker.json'),
					'keywords'    => __( ' wedding, engagement, nuptials, matrimony, ring, ceremony, ritual, vows, anniversary, celebration, videography, photography, rites, union, big day, knot, aisle, wive, husband, wife, esposo, esposa, hitched, plunged, gatherings, events, video, reels, youtube, film', 'skt-templates' ),
				),
				'martial-arts-lite-elementor'              => array(
					'title'       => __( 'Martial Arts', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-martial-arts-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/martial-arts/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/martial-arts/free-martial-arts.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/martial-arts/free-martial-arts.json'),
					'keywords'    => __( ' kungfu, fitness, sportsman, running, sports, trainer, yoga, meditation, running, crossfit, taekwondo, karate, boxing, kickboxing, yoga', 'skt-templates' ),
				),
				'babysitter-lite-elementor'              => array(
					'title'       => __( 'BabySitter', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-kids-store-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/baby/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/baby/free-babysitter.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/baby/free-babbysitter.json'),
					'keywords'    => __( ' kids, chools, nursery, kids fashion store, kindergarten, daycare, baby care, nursery, nanny, grandma, babysitting, nursing, toddler', 'skt-templates' ),
				),
				'winery-lite-elementor'              => array(
					'title'       => __( 'Winery', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-liquor-store-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/winery/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/winery/free-winery.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/winery/free-winery.json'),
					'keywords'    => __( ' wine, champagne, alcohol, beverage, drink, liquor, spirits, booze, cocktail, beer, nectar, honey, brewery', 'skt-templates' ),
				),
				'industrial-lite-elementor'              => array(
					'title'       => __( 'Industrial', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-industrial-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/industrial/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/industrial/free-industrial.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/industrial/free-industrial.json'),
					'keywords'    => __( ' industry, factory, manufacturing, production, worker, construction, fabrication, welder, smithy, automation, machine, mechanized, mechanic, business, commerce, trade, union', 'skt-templates' ),
				),
				'free-coffee-elementor'              => array(
					'title'       => __( 'Coffee', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-coffee/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/cuppa/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/cuppa/free-coffee.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/cuppa/free-coffee.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes', 'skt-templates' ),
				),
				'cutsnstyle-lite-elementor'              => array(
					'title'       => __( 'CutsnStyle', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/cutsnstyle-lite/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/haircut/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/haircut/free-haircut.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/haircut/free-haircut.json'),
					'keywords'    => __( ' salon, beauty, nails, manicure, pedicure, parlor, spa, hairdresser, barber, soap, glamour, fashion, grace, charm, looks, style, mud bath, oxygen therapy, aromatherapy, facial, foot, skin care, hair coloring, shampoo, razors, grooming, beard, cosmetology', 'skt-templates' ),
				),
				'buther-lite-elementor'              => array(
					'title'       => __( 'Butcher', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-meat-shop-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/butcher/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/butcher/free-butcher.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/butcher/free-butcher.json'),
					'keywords'    => __( ' butcher, meat, steakhouse, boner, mutton, chicken, fish, slaughter', 'skt-templates' ),
				),
				'architect-lite-elementor'              => array(
					'title'       => __( 'Architect', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-architect-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/architect/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/architect/free-architect.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/architect/free-architect.json'),
					'keywords'    => __( ' architect, interior, construction, contractor, architecture, draughtsman, planner, builder, consultant, fabricator, creator, maker, engineer, mason, craftsman, erector', 'skt-templates' ),
				),
				'free-autocar-elementor'              => array(
					'title'       => __( 'Auto Car', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-car-rental-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/autocar/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/autocar/free-autocar.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/autocar/free-autocar.json'),
					'keywords'    => __( ' transport, lorry, truck, tow, bus, movers, packers, courier, garage, mechanic, car, automobile', 'skt-templates' ),
				),
				'movers-packers-lite-elementor'              => array(
					'title'       => __( 'Movers and Packers', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/movers-packers-lite/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/movers-packers/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/movers-packers/free-movers-packers.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/movers-packers/free-movers-packers.json'),
					'keywords'    => __( ' transport, lorry, truck, tow, bus, movers, packers, courier, garage, mechanic, car, automobile, shifting', 'skt-templates' ),
				),
				'natureone-elementor'              => array(
					'title'       => __( 'NatureOne', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/natureonefree/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/natureone/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/natureone/free-natureone.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/natureone/free-natureone.json'),
					'keywords'    => __( ' nature, green, conservation, solar, eco-friendly, renewable, biofuel electricity, recycle, natural resource, pollution free, water heating, sun, power, geothermal, hydro, wind energy, environment, earth, farm, agriculture', 'skt-templates' ),
				),
				'modeling-lite-elementor'              => array(
					'title'       => __( 'Modeling', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-lifestyle-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/modelling/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/modelling/free-modelling.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/modelling/free-modelling.json'),
					'keywords'    => __( ' model, fashion, style, glamour, mannequin, manikin, mannikin, manakin, clothing, photography, photograph, instagram', 'skt-templates' ),
				),
				'exceptiona-lite-elementor'              => array(
					'title'       => __( 'Exceptiona', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-accounting-firm-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/exceptiona/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/exceptiona/exceptiona-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/exceptiona/exceptiona-lite.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness', 'skt-templates' ),
				),
				'free-parallax-elementor'              => array(
					'title'       => __( 'Parallax Me', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt_parallax_me/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/parallax/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/parallax/free-parallax.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/parallax/free-parallax.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office', 'skt-templates' ),
				),
				'free-build-elementor'              => array(
					'title'       => __( 'Build', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-build-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/build/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/build/free-build.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/build/free-build.json'),
					'keywords'    => __( ' construction, contractor, concrete, cement, fabricator, steel, roofing, flooring, industry, factory, manufacturing, production, worker, fabrication, welder, smithy, automation, machine, mechanized, mechanic, business, commerce, trade, union' ),
				),
				'fitness-lite-elementor'              => array(
					'title'       => __( 'Fitness', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/fitness-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/sktfitness/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/sktfitness/free-sktfitness.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/sktfitness/free-sktfitness.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'restaurant-lite-elementor'              => array(
					'title'       => __( 'Restaurant', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/restaurant-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/restro/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/restro/free-restro.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/restro/free-restro.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'flat-lite-elementor'              => array(
					'title'       => __( 'Flat', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-landing-page-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/flat/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/flat/free-flat.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/flat/free-flat.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, material design' ),
				),
				'juice-shakes-lite-elementor'              => array(
					'title'       => __( 'Juice and Shakes', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-smoothie-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/juice/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/juice/free-juice-shakes.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/juice/free-juice-shakes.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes' ),
				),				
				'organic-lite-elementor'              => array(
					'title'       => __( 'Organic', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-farming-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/organic/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/organic/free-organic.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/organic/free-organic.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'bistro-lite-elementor'              => array(
					'title'       => __( 'Bistro', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-fast-food-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/bistro/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/bistro/free-bistro.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/bistro/free-bistro.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'yogi-lite-elementor'              => array(
					'title'       => __( 'Yogi', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/yogi-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/yogi/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/yogi/free-yogi.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/yogi/free-yogi.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'free-design-agency-elementor'              => array(
					'title'       => __( 'Design Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-design-agency/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/design/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/design/free-design-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/design/free-design-agency.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office' ),
				),
				'construction-lite-elementor'              => array(
					'title'       => __( 'Construction', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/construction-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/construction/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/construction/free-construction.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/construction/free-construction.json'),
					'keywords'    => __( ' construction, contractor, concrete, cement, fabricator, steel, roofing, flooring, industry, factory, manufacturing, production, worker, fabrication, welder, smithy, automation, machine, mechanized, mechanic, business, commerce, trade, union' ),
				),
				'toothy-lite-elementor'              => array(
					'title'       => __( 'Toothy', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-dentist-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/toothy/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/toothy/free-toothy.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/toothy/free-toothy.json'),
					'keywords'    => __( ' medical, dentist, hospital, ward, nurse, doctor, physician, health, mental, physical, dispensary, physiotheraphy, care, nursing, old age, senior living, dental, cardio, orthopaedic, bones, chiropractor' ),
				),
				'itconsultant-lite-elementor'              => array(
					'title'       => __( 'IT Consultant', 'skt-templates' ),
 					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/consultant-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/it-consultant/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/it-consultant/free-itconsultant.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/it-consultant/free-itconsultant.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-onlinecoach-elementor'              => array(
					'title'       => __( 'Online Coach', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-coach-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/online-coach/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/online-coach/free-onlinecoach.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/online-coach/free-onlinecoach.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-sktpathway-elementor'              => array(
					'title'       => __( 'Pathway', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt_pathway/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/pathway/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pathway/free-pathway.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pathway/free-pathway.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-sktblack-elementor'              => array(
					'title'       => __( 'Black', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-black/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/black/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/black/free-black.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/black/free-black.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-sktwhite-elementor'              => array(
					'title'       => __( 'White', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-white/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/white/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/white/free-white.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/white/free-white.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'interior-lite-elementor'              => array(
					'title'       => __( 'Interior', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-interior-wordpress-theme/'),	
					'demo_url'    => esc_url('https://demosktthemes.com/free/interior/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/interior/interior-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/interior/interior-lite.json'),
					'keywords'    => __( ' interior design, furnishing, cushions, flooring, roofing, house works, vase, flower, curtains, furniture, wallpaper, renovation, framing, modular, kitchen, wardrobe, cupboard, unit, TV, fridge, washing machine, home appliances, bedroom, sofa, couch, living room' ),
				),
				'free-simple-elementor'              => array(
					'title'       => __( 'Simple', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-simple-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/simple/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/simple/free-simple.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/simple/free-simple.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-condimentum-elementor'              => array(
					'title'       => __( 'Condimentum', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-multipurpose-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/condimentum/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/condimentum/free-condimentum.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/condimentum/free-condimentum.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'ele-makeup-lite-elementor'              => array(
					'title'       => __( 'Makeup', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-beauty-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/makeup/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/makeup/ele-makeup-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/makeup/ele-makeup-lite.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, attorney' ),
				),
				'ele-attorney-lite-elementor'              => array(
					'title'       => __( 'Attorney', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-law-firm-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/attorney/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/attorney/ele-attorney.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/attorney/ele-attorney.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, attorney' ),
				),
				'poultry-farm-lite-elementor'              => array(
					'title'       => __( 'Poultry Farm', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-poultry-farm-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/poultry-farm/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/poultry-farm/free-poultryfarm.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/poultry-farm/free-poultryfarm.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture, livestock, eggs, chicken, mutton, goat, sheep' ),
				),
				'ele-restaurant-lite-elementor'              => array(
					'title'       => __( 'Restaurant', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-food-blog-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/restaurant/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/restaurant/ele-restaurant-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/restaurant/ele-restaurant-lite.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'ele-luxuryhotel-lite-elementor'              => array(
					'title'       => __( 'Luxury Hotel', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-hotel-booking-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/hotel/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/hotel/free-hotel.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/hotel/free-hotel.json'),
					'keywords'    => __( ' hotel, motel, oyo, resort, vacation, family, trip, travel, b&b, holiday, lodge, accommodation, inn, guest house, hostel, boarding, service apartment, auberge, boatel, pension, bed and breakfast, tavern, dump, lodging, hospitality' ),
				),
				'ele-wedding-lite-elementor'              => array(
					'title'       => __( 'Wedding', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-wedding-planner-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/wedding/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/wedding/ele-wedding-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/wedding/ele-wedding-lite.json'),
					'keywords'    => __( ' wedding, engagement, nuptials, matrimony, ring, ceremony, ritual, vows, anniversary, celebration, videography, photography, rites, union, big day, knot, aisle, wive, husband, wife, esposo, esposa, hitched, plunged' ),
				),
				'ele-fitness-lite-elementor'              => array(
					'title'       => __( 'Fitness', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-workout-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/fitness/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/fitness/ele-fitness.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/fitness/ele-fitness.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'ele-nature-lite-elementor'              => array(
					'title'       => __( 'Nature', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-green-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/nature/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/nature/ele-nature.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/nature/ele-nature.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'ele-ebook-lite-elementor'              => array(
					'title'       => __( 'eBook', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-ebook-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/ebook/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/ebook/ele-book.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/ebook/ele-ebook.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, attorney' ),
				),
				'ele-product-launch-lite-elementor'              => array(
					'title'       => __( 'Product Launch', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-mobile-app-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/app/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/app/ele-app.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/app/ele-app.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'ele-spa-lite-elementor'              => array(
					'title'       => __( 'Spa', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-beauty-salon-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/spa/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/spa/ele-spa.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/spa/ele-spa.json'),
					'keywords'    => __( ' salon, beauty, nails, manicure, pedicure, parlor, spa, hairdresser, barber, soap, glamour, fashion, grace, charm, looks, style, mud bath, oxygen therapy, aromatherapy, facial, foot, skin care, hair coloring, shampoo, razors, grooming, beard, cosmetology' ),
				),
				'ele-store-lite-elementor'              => array(
					'title'       => __( 'Store', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-wordpress-store-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/store/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/store/ele-store.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/store/ele-store.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, store, shop' ),
				),
				'hightech-lite-elementor'              => array(
					'title'       => __( 'High Tech', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-computer-repair-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/hightech/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/hightech/hightech-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/hightech/hightech-lite.json'),
					'keywords'    => __( ' technology, computer, repair, laptop, mobile, phone, digital, online services, help, desktop, mac, windows, apple, iPhone, android, electronic, tablet, maintenance, software, antivirus, IT solutions, training, consulting' ),
				),
				'junkremoval-lite-elementor'              => array(
					'title'       => __( 'Junk Removal', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-waste-management-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/junkremoval/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/junkremoval/junk-removal-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/junkremoval/junkremoval-lite.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'pets-lite-elementor'              => array(
					'title'       => __( 'Pet', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-animal-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/pets/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pets/ele-pets.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pets/ele-pets.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'ele-agency-lite-elementor'              => array(
					'title'       => __( 'Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-marketing-agency-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/agency/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/agency/ele-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/agency/ele-agency.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'ele-yoga-lite-elementor'              => array(
					'title'       => __( 'Yoga', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-yoga-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/yoga/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/yoga/ele-yoga.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/yoga/ele-yoga.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'localbusiness-lite-elementor'              => array(
					'title'       => __( 'Local Business', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-simple-business-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/localbusiness/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/localbusiness/localbusiness-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/localbusiness/localbusiness-lite.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-fashion-elementor'              => array(
					'title'       => __( 'Fashion', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-fashion-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/fashion/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/fashion/free-fashion.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/fashion/free-fashion.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, fashion, model, modelling' ),
				),
				'free-chocolate-elementor'              => array(
					'title'       => __( 'Chocolate', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-chocolate-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/chocolate/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/chocolate/free-chocolate.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/chocolate/free-chocolate.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes' ),
				),
				'icecream-lite-elementor'              => array(
					'title'       => __( 'IceCream', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-icecream-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/icecream/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/icecream/icecream-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/icecream/icecream-lite.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes, ice cream, yogurt' ),
				),
				'catering-lite-elementor'              => array(
					'title'       => __( 'Catering', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-catering-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/catering/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/catering/catering-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/catering/catering-lite.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'plumbing-lite-elementor'              => array(
					'title'       => __( 'Plumbing', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-plumber-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/plumbing/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/plumbing/plumbing-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/plumbing/plumbing-lite.json'),
					'keywords'    => __( ' plumber, electrician, carpenter, craftsman, workshop, garage, painter, renovation, decoration, maid service, cleaning, mechanic, construction, installation, contractor, home remodeling, building, plastering, partitioning, celings, roofing, architecture, interior work, engineering, welding, refurbishment, spare parts, manufacturing, plumbing, fabrication, handyman, painting, production, worker, fabrication, welder, smithy, automation, machine, mechanized' ),
				),
				'recycle-lite-elementor'              => array(
					'title'       => __( 'Recycle', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-environmental-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/recycle/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/recycle/recycle-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/recycle/recycle-lite.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'pottery-lite-elementor'              => array(
					'title'       => __( 'Pottery', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-pottery-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/pottery/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pottery/pottery-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pottery/pottery-lite.json'),
					'keywords'    => __( ' interior design, furnishing, cushions, flooring, roofing, house works, vase, flower, curtains, furniture, wallpaper, renovation, framing, modular, kitchen, wardrobe, cupboard, unit, TV, fridge, washing machine, home appliances, bedroom, sofa, couch, living room' ),
				),
				'actor-lite-elementor'              => array(
					'title'       => __( 'Actor', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-celebrity-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/actor/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/actor/actor-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/actor/actor-lite.json'),
					'keywords'    => __( ' actor, movie, tv shows, actress, model, instagram, fan, following, shows, events, singing, dancing, birthdays, personal, online presence, resume, profile, portfolio' ),
				),
				'marketing-agency-elementor'              => array(
					'title'       => __( 'Marketing Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/marketing-agency/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/marketing-agency/marketing-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/marketing-agency/marketing-agency.json'),
					'keywords'    => __( ' marketing-agency, agency, online, digital, consulting, corporate, business, small business, b2b, b2c, financial, investment, portfolio, management, discussion, advice, solicitor, lawyer, attorney, legal, help, SEO, SMO, social', 'skt-templates' ),
				)  
			);

			foreach ( $templates_list as $template => $properties ) {
				$templates_list[ $template ] = wp_parse_args( $properties, $defaults_if_empty );
			}

			return apply_filters( 'template_directory_templates_list', $templates_list );
		}

		/**
		 * Register endpoint for themes page.
		 */
		public function demo_listing_register() {
			add_rewrite_endpoint( 'sktb_templates', EP_ROOT );
		}

		/**
		 * Return template preview in customizer.
		 *
		 * @return bool|string
		 */
		public function demo_listing() {
			$flag = get_query_var( 'sktb_templates', false );

			if ( $flag !== '' ) {
				return false;
			}
			if ( ! current_user_can( 'customize' ) ) {
				return false;
			}
			if ( ! is_customize_preview() ) {
				return false;
			}

			return $this->render_view( 'template-directory-render-template' );
		}

		/**
		 * Add the 'Template Directory' page to the dashboard menu.
		 */
		public function add_menu_page() {
			$products = apply_filters( 'sktb_template_dir_products', array() );
			foreach ( $products as $product ) {
				add_submenu_page(
					$product['parent_page_slug'], $product['directory_page_title'], __( 'Elementor Templates', 'skt-templates' ), 'manage_options', $product['page_slug'],
					array( $this, 'render_admin_page' )
				);
				
				add_submenu_page(
					$product['parent_page_slug'], $product['directory_page_title'], __( 'Gutenberg Templates', 'skt-templates' ), 'manage_options', $product['gutenberg_page_slug'],
					array( $this, 'gutenberg_render_admin_page' )
				);				
				
			}

		}

		/**
		 * Render the template directory admin page.
		 */
		public function render_admin_page() {
			$data = array(
				'templates_array' => $this->templates_list(),
			);
			echo $this->render_view( 'template-directory-page', $data );
		}
		
		public function gutenberg_render_admin_page() {
			$data = array(
				'templates_array' => $this->gutenberg_templates_list(),
			);
			echo $this->render_view( 'template-directory-page', $data );
		}		

		/**
		 * Utility method to call Elementor import routine.
		 *
		 * @param \WP_REST_Request $request the async request.
		 *
		 * @return string
		 */
		 
		public function import_elementor( \WP_REST_Request $request ) {
			if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
				return 'no-elementor';
			}

			$params        = $request->get_params();
			$template_name = $params['template_name'];
			$template_url  = $params['template_url'];

			require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );

			// Mime a supported document type.
			$elementor_plugin = \Elementor\Plugin::$instance;
			$elementor_plugin->documents->register_document_type( 'not-supported', \Elementor\Modules\Library\Documents\Page::get_class_full_name() );

			$template                   = download_url( esc_url( $template_url ) );
			$name                       = $template_name;
			$_FILES['file']['tmp_name'] = $template;
			$elementor                  = new \Elementor\TemplateLibrary\Source_Local;
			$elementor->import_template( $name, $template );
			unlink( $template );

			$args = array(
				'post_type'        => 'elementor_library',
				'nopaging'         => true,
				'posts_per_page'   => '1',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => true,
			);

			$query = new \WP_Query( $args );

			$last_template_added = $query->posts[0];
			//get template id
			$template_id = $last_template_added->ID;

			wp_reset_query();
			wp_reset_postdata();

			//page content
			$page_content = $last_template_added->post_content;
			//meta fields
			$elementor_data_meta      = get_post_meta( $template_id, '_elementor_data' );
			$elementor_ver_meta       = get_post_meta( $template_id, '_elementor_version' );
			$elementor_edit_mode_meta = get_post_meta( $template_id, '_elementor_edit_mode' );
			$elementor_css_meta       = get_post_meta( $template_id, '_elementor_css' );

			$elementor_metas = array(
				'_elementor_data'      => ! empty( $elementor_data_meta[0] ) ? wp_slash( $elementor_data_meta[0] ) : '',
				'_elementor_version'   => ! empty( $elementor_ver_meta[0] ) ? $elementor_ver_meta[0] : '',
				'_elementor_edit_mode' => ! empty( $elementor_edit_mode_meta[0] ) ? $elementor_edit_mode_meta[0] : '',
				'_elementor_css'       => $elementor_css_meta,
			);

			// Create post object
			$new_template_page = array(
				'post_type'     => 'page',
				'post_title'    => $template_name,
				'post_status'   => 'publish',
				'post_content'  => $page_content,
				'meta_input'    => $elementor_metas,
				'page_template' => apply_filters( 'template_directory_default_template', 'templates/builder-fullwidth-std.php' )
			);

			$post_id = wp_insert_post( $new_template_page );
			$redirect_url = add_query_arg( array(
				'post'   => $post_id,
				'action' => 'elementor',
			), admin_url( 'post.php' ) );

			return ( $redirect_url );
		}

		/**
		 * Generate action button html.
		 *
		 * @param string $slug plugin slug.
		 *
		 * @return string
		 */
		public function get_button_html( $slug ) {
			$button = '';
			$state  = $this->check_plugin_state( $slug );
			if ( ! empty( $slug ) ) {
				switch ( $state ) {
					case 'install':
						$nonce  = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'install-plugin',
									'from'   => 'import',
									'plugin' => $slug,
								),
								network_admin_url( 'update.php' )
							),
							'install-plugin_' . $slug
						);
						$button .= '<a data-slug="' . $slug . '" class="install-now sktb-install-plugin button button-primary" href="' . esc_url( $nonce ) . '" data-name="' . $slug . '" aria-label="Install ' . $slug . '">' . __( 'Install and activate', 'skt-templates' ) . '</a>';
						break;
					case 'activate':
						$plugin_link_suffix = $slug . '/' . $slug . '.php';
						$nonce              = add_query_arg(
							array(
								'action'   => 'activate',
								'plugin'   => rawurlencode( $plugin_link_suffix ),
								'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $plugin_link_suffix ),
							), network_admin_url( 'plugins.php' )
						);
						$button             .= '<a data-slug="' . $slug . '" class="activate-now button button-primary" href="' . esc_url( $nonce ) . '" aria-label="Activate ' . $slug . '">' . __( 'Activate', 'skt-templates' ) . '</a>';
						break;
				}// End switch().
			}// End if().
			return $button;
		}

		/**
		 * Getter method for the source url
		 * @return mixed
		 */
		public function get_source_url() {
			return $this->source_url;
		}

		/**
		 * Setting method for source url
		 *
		 * @param $url
		 */
		protected function set_source_url( $url ) {
			$this->source_url = $url;
		}

		/**
		 * Check plugin state.
		 *
		 * @param string $slug plugin slug.
		 *
		 * @return bool
		 */
		public function check_plugin_state( $slug ) {
			if ( file_exists( WP_CONTENT_DIR . '/plugins/' . $slug . '/' . $slug . '.php' ) || file_exists( WP_CONTENT_DIR . '/plugins/' . $slug . '/index.php' ) ) {
				require_once( ABSPATH . 'wp-admin' . '/includes/plugin.php' );
				$needs = ( is_plugin_active( $slug . '/' . $slug . '.php' ) ||
				           is_plugin_active( $slug . '/index.php' ) ) ?
					'deactivate' : 'activate';

				return $needs;
			} else {
				return 'install';
			}
		}

		/**
		 * If the composer library is present let's try to init.
		 */
		public function load_full_width_page_templates() {
			if ( class_exists( '\SktThemes\FullWidthTemplates' ) ) {
				\SktThemes\FullWidthTemplates::instance();
			}
		}

		/**
		 * By default the composer library "Full Width Page Templates" comes with two page templates: a blank one and a full
		 * width one with the header and footer inherited from the active theme.
		 * SKTB Template directory doesn't need the blonk one, so we are going to ditch it.
		 *
		 * @param array $list
		 *
		 * @return array
		 */
		public function filter_fwpt_templates_list( $list ) {
			unset( $list['templates/builder-fullwidth.php'] );

			return $list;
		}

		/**
		 * Utility method to render a view from module.
		 *
		 * @codeCoverageIgnore
		 *
		 * @since   1.0.0
		 * @access  protected
		 *
		 * @param   string $view_name The view name w/o the `-tpl.php` part.
		 * @param   array  $args      An array of arguments to be passed to the view.
		 *
		 * @return string
		 */
		protected function render_view( $view_name, $args = array() ) {
			ob_start();
			$file = $this->get_dir() . '/views/' . $view_name . '-tpl.php';
			if ( ! empty( $args ) ) {
				foreach ( $args as $sktb_rh_name => $sktb_rh_value ) {
					$$sktb_rh_name = $sktb_rh_value;
				}
			}
			if ( file_exists( $file ) ) {
				include $file;
			}

			return ob_get_clean();
		}

		/**
		 * Method to return path to child class in a Reflective Way.
		 *
		 * @since   1.0.0
		 * @access  protected
		 * @return string
		 */
		protected function get_dir() {
			return dirname( __FILE__ );
		}

		/**
		 * @static
		 * @since  1.0.0
		 * @access public
		 * @return PageTemplatesDirectory
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->init();
			}

			return self::$instance;
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'skt-templates' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'skt-templates' ), '1.0.0' );
		}
	}
}
