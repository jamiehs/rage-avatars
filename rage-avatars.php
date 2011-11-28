<?php 
/**
Plugin Name: Rage Avatars
Plugin URI: http://jamie3d.com/
Description: Replace your avatar-less users Gravatars with "rage comic avatars"
Version: 1.1
Author: jamie3d
Author URI: http://jamie3d.com
License: GPL
*/

class RageAvatars {
    var $namespace = "rage-avatars";
    var $friendly_name = "Rage Avatars";
    var $version = "1.1";

    // Default plugin options
	var $avatars = false;
	var $avatars_dir = 'avatars';
	var $avatar_count = 0;

    /**
     * Instantiation construction
     */
    function __construct() {
        // Directory path to this plugin's files
        $this->dir_name = dirname( __FILE__ );
        
        // URL path to this plugin's files
        $this->url_path = WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) );
        
        // Name of the option_value to store plugin options in
        $this->option_name = '_' . $this->namespace . '--options';
		
		// Load the avatars from the directory.
		$this->load_images();
		
		// Add a nifty admin menu item.
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

        // Register all scripts for this plugin
        $this->wp_register_scripts();

        // Register all styles for this plugin
        $this->wp_register_styles();

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
		// Load the timthumb lib.
		$timthumb_url = $this->plugins_url( 'lib/timthumb.php?src=' );
		$timthumb_params = "&w={$size}&h={$size}";
		
		// Create an integer based on the user's email and then mod it with the total count... ta da!
		$psuedo_random_integer = abs( crc32( $author_email_hash ) % $this->avatar_count );
		return $timthumb_url . plugins_url( $this->avatars_dir . '/' . $this->avatars[ $psuedo_random_integer ] , __FILE__ ) . $timthumb_params;
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
        include( "{$this->dir_name}/views/options.php" );
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
    function instance() {
        global $RageAvatars;
        $RageAvatars = new RageAvatars();
    }
	
    /**
     * Register scripts used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_script()
     */
    function wp_register_scripts() {
        // Admin JavaScript
        wp_register_script( "{$this->namespace}-admin", "{$this->url_path}/javascripts/{$this->namespace}-admin.js", array( 'jquery' ), $this->version, true );
    }

    /**
     * Register styles used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_style()
     */
    function wp_register_styles() {
        // Admin Stylesheet
        wp_register_style( "{$this->namespace}-admin", "{$this->url_path}/stylesheets/{$this->namespace}-admin.css", array(), $this->version, 'screen' );
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

// Initiatie the PluginTemplate class at the WordPress init action
add_action( 'init', array( 'RageAvatars', 'instance' ) );