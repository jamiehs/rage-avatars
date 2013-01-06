<?php 
/**
Plugin Name: Rage Avatars
Plugin URI: http://jamie3d.com/
Description: Replace your avatar-less users Gravatars with "rage comic avatars"
Version: 1.0.3
Author: jamie3d
Author URI: http://jamie3d.com
License: GPL
*/

// Include constants file
require_once( dirname( __FILE__ ) . '/lib/constants.php' );

class RageAvatars {
    var $namespace = "rage-avatars";
    var $friendly_name = "Rage Avatars";

    // Default plugin options
	var $avatars = false;
	var $avatars_dir = 'avatars';
	var $avatar_count = 0;

    /**
     * Instantiation construction
     */
    function __construct() {
        // Name of the option_value to store plugin options in
        $this->option_name = '_' . $this->namespace . '--options';
        
        // Set and Translate defaults
        $this->defaults = array(
            'foo' => 'bar'
        );
		
		// Load the avatars from the directory.
		$this->load_images();
		
		// Add a nifty admin menu item.
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

        // Load all library files used by this plugin
        $libs = glob( RAGE_AVATARS_DIRNAME . '/lib/*.php' );
        foreach( $libs as $lib ) {
            include_once( $lib );
        }
        
        // Add all action, filter and shortcode hooks
        $this->add_hooks();
    }
    
    /**
     * Process update page form submissions
     * 
     * @uses ::sanitize()
     * @uses wp_redirect()
     * @uses wp_verify_nonce()
     */
    private function _admin_options_update() {
        // Verify submission for processing using wp_nonce
        if( wp_verify_nonce( $_REQUEST['_wpnonce'], "{$this->namespace}-update-options" ) ) {
            $data = array();
            /**
             * Loop through each POSTed value and sanitize it to protect against malicious code. Please
             * note that rich text (or full HTML fields) should not be processed by this function and 
             * dealt with directly.
             */
            foreach( $_POST['data'] as $key => $val ) {
                $data[$key] = $this->_sanitize( $val );
            }
            
            /**
             * Place your options processing and storage code here
             */
             
            // Update the options value with the data submitted
            update_option( $this->option_name, $data );
            
            wp_safe_redirect( $_REQUEST['_wp_http_referer'] );
            exit;
        }
    }

    /**
     * Sanitize data
     * 
     * @param mixed $str The data to be sanitized
     * 
     * @uses wp_kses()
     * 
     * @return mixed The sanitized version of the data
     */
    private function _sanitize( $str ) {
        if ( !function_exists( 'wp_kses' ) ) {
            require_once( ABSPATH . 'wp-includes/kses.php' );
        }
        global $allowedposttags;
        global $allowedprotocols;
        
        if ( is_string( $str ) ) {
            $str = wp_kses( $str, $allowedposttags, $allowedprotocols );
        } elseif( is_array( $str ) ) {
            $arr = array();
            foreach( (array) $str as $key => $val ) {
                $arr[$key] = $this->_sanitize( $val );
            }
            $str = $arr;
        }
        
        return $str;
    }

    /**
     * Hook into register_activation_hook action
     * 
     * Put code here that needs to happen when your plugin is first activated (database
     * creation, permalink additions, etc.)
     */
    static function activate() {
        // Do activation actions
    }
    
    /**
     * Adds all the filters and hooks
     */
    function add_hooks() {
        // Register all JavaScripts for this plugin
        add_action( 'init', array( &$this, 'wp_register_scripts' ), 1 );
        
        // Register all Stylesheets for this plugin
        add_action( 'init', array( &$this, 'wp_register_styles' ), 1 );
        
        // Add Avatar Filter
        add_filter( 'get_avatar', array( $this, 'filter_avatar' ), 1, 5 );
    }
    
	/**
	 * Filter Avatar
	 * 
	 * Filters the WordPress get_avatar() function.
	 * 
	 * @uses $this->get_rage_avatar()
	 * 
	 * @param string $avatar (<img /> tag) from WordPress
	 * @param object $comment from WordPress
	 * @param integer $size of the avatar in pixels
	 * @param string $default url of the avatar as defined by WordPress
	 * @param string $alt text for the image
	 * 
	 * @return string image tag for the user's avatar.
	 */
	public function filter_avatar( $avatar, $comment, $size, $default, $alt ){
		$author_email = false;	
		
		if( is_object( $comment ) ){
			$author_email = (string) strtolower( trim( $comment->comment_author_email ) );
		}
		
		// If there's no email, let's just get outta here.
		if( !$author_email ) {
			return $avatar;
		}
		
		// Hash the email address. We'll use this for the crc32
		$author_email_hash = md5( $author_email );
		$new_psuedo_random_avatar = $this->get_rage_avatar( $author_email_hash, $size );
		
		// Extract the default argument from the Gravatar and replace it. 
		preg_match('/src=[\'"]([^"\']+)/', $avatar, $matches);
		$gravatar_url = $matches[1];
		$new_gravatar_url = preg_replace( '/d=([^&]+)/', 'd=' . urlencode( $new_psuedo_random_avatar ), $gravatar_url );
		
		return preg_replace('/src=([\'"])([^"\']+)/', "src=$1{$new_gravatar_url}", $avatar );
	}

    /**
     * Retrieve the stored plugin option or the default if no user specified value is defined
     * 
     * @param string $option_name The name of the option you wish to retrieve
     * 
     * @uses get_option()
     * 
     * @return mixed Returns the option value or false(boolean) if the option is not found
     */
    function get_option( $option_name, $reload = false ) {
        // If reload is true, kill the existing options value so it gets fetched fresh.
        if( $reload )
            $this->options = null;
        
        // Load option values if they haven't been loaded already
        if( !isset( $this->options ) || empty( $this->options ) ) {
            $this->options = get_option( $this->option_name, $this->defaults );
        }
        
        if( isset( $this->options[$option_name] ) ) {
            return $this->options[$option_name];    // Return user's specified option value
        } elseif( isset( $this->defaults[$option_name] ) ) {
            return $this->defaults[$option_name];   // Return default option value
        }
        return false;
    }

	/**
	 * Get Rage Avatar
	 * 
	 * Fetches a user's avatar and potentially resizes it.
	 * 
	 * @uses $this->plugins_url()
	 * @uses $this->avatar_count
	 * @uses $this->avatars_dir
	 * @uses $this->avatars
	 * 
	 * @param string $author_email_hash md5 hash of the author's email
	 * @param integer $size of the avatar in pixels
	 * 
	 * @return string TimThumb ready URL of the new default avatar for the user.
	 */
	private function get_rage_avatar( $author_email_hash, $size ){
		// Create an integer based on the user's email and then mod it with the total count... ta da!
		$psuedo_random_integer = abs( crc32( $author_email_hash ) % $this->avatar_count );
		return  plugins_url( $this->avatars_dir . '/' . $this->avatars[ $psuedo_random_integer ] , __FILE__ );
	}
	
	/**
	 * Plugins URL
	 * 
	 * An adaptation of the WordPress plugins_url() function
	 * that handles the case where a relative URL is returned instead
	 * of an absolute one.
	 * 
	 * @uses plugins_url()
	 * 
	 * @param $url string The partial URL for building.
	 * 
	 * @return string Absolute URL of the file specified.
	 */
	public function plugins_url( $url ) {
		$plugins_url = plugins_url( $url , __FILE__ );
		if( !strpos( $plugins_url, '://' ) ){
			$plugins_url = '//' . $_SERVER['HTTP_HOST'] . $plugins_url;
		}
		return $plugins_url;
	}
	
	/**
	 * Load Images
	 * 
	 * Loads the images from the images dir and caches them.
	 * 
	 * @uses $this->avatars
	 * @uses $this->avatar_count
	 * @uses $this->avatars_dir
	 * 
	 */
	private function load_images() {
		if( !$this->avatars ){
			$avatars = glob( dirname( __FILE__ ) . '/' . $this->avatars_dir . '/*.png' );
			// trim the filenames so it's just relative.
			foreach( $avatars as &$relative_avatar ){
				$relative_avatar = basename( $relative_avatar );
			}
			$this->avatars = $avatars;
			$this->avatar_count = count( $avatars );
		}
	}
	
    /**
     * Define the admin menu options for this plugin
     * 
     * @uses add_action()
     * @uses add_options_page()
     */
    function admin_menu() {
        $page_hook = add_options_page( $this->friendly_name, $this->friendly_name, 'administrator', $this->namespace, array( &$this, 'admin_options_page' ) );

        // Add print scripts and styles action based off the option page hook
        //add_action( 'admin_print_scripts-' . $page_hook, array( &$this, 'admin_print_scripts' ) );
        add_action( 'admin_print_styles-' . $page_hook, array( &$this, 'admin_print_styles' ) );
    }

    /**
     * The admin section options page rendering method
     * 
     * @uses current_user_can()
     * @uses wp_die()
     */
    function admin_options_page() {
        if( !current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page' );
        }
        $page_title = $this->friendly_name . ' Options';
        $namespace = $this->namespace;
        include( RAGE_AVATARS_DIRNAME . "/views/options.php" );
    }

    /**
     * Load JavaScript for the admin options page
     * 
     * @uses wp_enqueue_script()
     */
    function admin_print_scripts() {
        wp_enqueue_script( "{$this->namespace}-admin" );
    }

    /**
     * Load Stylesheet for the admin options page
     * 
     * @uses wp_enqueue_style()
     */
    function admin_print_styles() {
        wp_enqueue_style( "{$this->namespace}-admin" );
    }

    /**
     * Initialization function to hook into the WordPress init action
     * 
     * Instantiates the class on a global variable and sets the class, actions
     * etc. up for use.
     */
    static function instance() {
        global $RageAvatars;
        
        // Only instantiate the Class if it hasn't been already
        if( !isset( $RageAvatars ) ) $RageAvatars = new RageAvatars();
    }
	
    /**
     * Register scripts used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_script()
     */
    function wp_register_scripts() {
        // Admin JavaScript
        //wp_register_script( "{$this->namespace}-admin", RAGE_AVATARS_URLPATH . "/javascripts/{$this->namespace}-admin.js", array( 'jquery' ), RAGE_AVATARS_VERSION, true );
    }

    /**
     * Register styles used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_style()
     */
    function wp_register_styles() {
        // Admin Stylesheet
        wp_register_style( "{$this->namespace}-admin", RAGE_AVATARS_URLPATH . "/stylesheets/{$this->namespace}-admin.css", array(), RAGE_AVATARS_VERSION, 'screen' );
    }
	
	// Helper debug function
    function debug( $var, $verbose = false ) {
        echo "<pre>";

        if( $verbose == true ) {
            var_dump( $var );
        } else {
            if( is_array( $var ) || is_object( $var ) ) {
                print_r( $var );
            } elseif( is_bool( $var ) ) {
                echo var_export( $var, true );
            } else {
                echo $var;
            }
        }

        echo "</pre>";
    }
}

if( !isset( $RageAvatars ) ) {
    RageAvatars::instance();
}
register_activation_hook( __FILE__, array( 'RageAvatars', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RageAvatars', 'deactivate' ) );