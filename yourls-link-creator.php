<?php 
/* 
Plugin Name: YOURLS Link Creator
Plugin URI: http://andrewnorcross.com/plugins/
Description: Creates a shortlink using YOURLS and stores as postmeta.
Version: 1.0
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

    Copyright 2012 Andrew Norcross

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 



// Start up the engine 
class YOURLSCreator
{
	/**
	 * Static property to hold our singleton instance
	 * @var YOURLSCreator
	 */
	static $instance = false;


	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return YOURLSCreator
	 */
	private function __construct() {
		add_action		( 'admin_menu',				array( $this, 'yourls_settings'	) );
		add_action		( 'admin_init', 			array( $this, 'reg_settings'	) );
		add_action		( 'admin_head', 			array( $this, 'css_head'		) );
		add_action		( 'publish_post',			array( $this, 'create_yourls'	), 10, 3 );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return YOURLSCreator
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}


	/**
	 * Create shortlink function. Called on publish_post
	 *
	 * @return YOURLSCreator
	 */

	public function create_yourls ($post_id){

		global $current_screen;
		// only fire on posts
		if ($current_screen->id !== 'post' )
			return;

		// only fire when settings have been filled out
		$yourls_api		= get_option('yourls_api');
		$yourls_url		= get_option('yourls_url');

		if(	empty($yourls_api) || empty($yourls_url) )
			return;
		
		// check for existing YOURLS
		$yourls_exist = get_post_meta($post_id, '_yourls_url', true);			
						
		// go get us a swanky new short URL if we dont have one
		if(empty($yourls_exist) ) {
			$clean_url	= str_replace('http://', '', $yourls_url);
	
			$yourls		= 'http://'.$clean_url.'/yourls-api.php';
			$api_key	= $yourls_api;
			$action		= 'shorturl';
			$format		= 'JSON';
			$post_url	= get_permalink($post_id);
	
			$yourls_r	= $yourls.'?signature='.$api_key.'&action='.$action.'&url='.$post_url.'&format='.$format.'';
			
			$response = wp_remote_get( $yourls_r );
				if( is_wp_error( $response ) ) {
					// do something with the error response
				} else {
					$data		= $response['body'];
					update_post_meta($post_id, '_yourls_url', $data);
				}
			}
		}

	/**
	 * build out settings page
	 *
	 * @return YOURLSCreator
	 */


	public function yourls_settings() {
	    add_submenu_page('options-general.php', 'YOURLS Settings', 'YOURLS Settings', 'manage_options', 'yourls-settings', array( $this, 'yourls_settings_display' ));
		add_meta_box('yours_post_display', __('YOURLS Shortlink'), array(&$this, 'yours_post_display'), 'post', 'side', 'high');
	}

	/**
	 * Register settings
	 *
	 * @return YOURLSCreator
	 */


	public function reg_settings() {
		register_setting( 'yourls_options', 'yourls_url');
		register_setting( 'yourls_options', 'yourls_api');
	}

	/**
	 * CSS in the head for the settings page
	 *
	 * @return YOURLSCreator
	 */

	public function css_head() { ?>
		<style type="text/css">

		div#icon-yourls {
			background:url(<?php echo plugins_url('/img/yourls-icon.png', __FILE__); ?>) no-repeat 0 0!important;
		}
		
		div.yourls_options {
			padding:1em;
		}
		
		table.yours-table {
			margin:0 0 30px 0;
		}
		
		div.yourls_form_text {
			margin:0 0 20px 0;
		}
		
		</style>

	<?php }

	/**
	 * Display main options page structure
	 *
	 * @return YOURLSCreator
	 */
	 
	public function yourls_settings_display() { ?>
	
		<div class="wrap">
    	<div class="icon32" id="icon-yourls"><br></div>
		<h2>YOURLS Link Creator Settings</h2>
        
	        <div class="yourls_options">
            	<div class="yourls_form_text">
            	<p>Text to explain what the heck this is gonna do.</p>
                </div>
                
                <div class="yourls_form_options">
	            <form method="post" action="options.php">
			    <?php
                settings_fields( 'yourls_options' );
				$yourls_api		= get_option('yourls_api');
				$yourls_url		= get_option('yourls_url');
				$api_display	= (!empty($yourls_api) ? $yourls_api : '');
				$url_display	= (!empty($yourls_url) ? $yourls_url : '');
				?>

                <table class="form-table yours-table">
				<tbody>
                	<tr>
                        <th><label for="yourls_url">YOURLS Custom URL</label></th>
                        <td>
                        	<input type="text" class="regular-text" value="<?php echo $url_display; ?>" id="yourls_url" name="yourls_url">
                            <p class="description">Actual URL only. Omit the http://</p>
						</td>
                    </tr>

                	<tr>
                        <th><label for="yourls_api">YOURLS API Signature Key</label></th>
                        <td>
                        	<input type="text" class="regular-text" value="<?php echo $api_display; ?>" id="yourls_api" name="yourls_api">
                            <p class="description">Found in the 'tools' section on your YOURLS admin page.</p>
						</td>
                    </tr>
				</tbody>
                </table>        
    
	    		<p><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
				</form>
                </div>
    
            </div>
        
        </div>    
	
	<?php }
		
	/**
	 * Display YOURLS shortlink if present
	 *
	 * @return YOURLSCreator
	 */

	public function yours_post_display(){
		global $post;
		$yourls_link	= get_post_meta($post->ID, '_yourls_url', true);

		if(!empty($yourls_link)) {
            echo '<input id="yourls_link" class="widefat" type="text" name="yourls_link" value="'.$yourls_link.'" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" />';
			echo '<p class="howto">Your custom YOURLS link.</p>';
		} else {
			echo '<p class="howto">No YOURLS link has been generated for this post.</p>';
		}
	}

/// end class
}


// Instantiate our class
$YOURLSCreator = YOURLSCreator::getInstance();