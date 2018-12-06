<?php
/**
 * Plugin Name: BP Custom Background for User Profile
 * Version:1.0.8
 * Author: Brajesh Singh
 * Author URI: http://buddydev.com/members/sbrajesh/
 * Plugin URI: http://buddydev.com/plugins/bp-custom-background-for-user-profile/
 * License: GPL
 *
 * Description: Allows Users to upload custom background image for their profile pages
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BPProfileBGChanger
 */
class BPProfileBGChanger {

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Callback for various hooks
	 */
	public function setup() {
		// load textdomain.
		add_action( 'bp_loaded', array( $this, 'load_textdomain' ), 2 );
		// setup nav.
		add_action( 'bp_xprofile_setup_nav', array( $this, 'setup_nav' ) );

		// inject custom css class to body.
		add_filter( 'body_class', array( $this, 'get_body_class' ), 30 );
		// add css for background change.
		add_action( 'wp_head', array( $this, 'inject_css' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'inject_js' ) );
		add_action( 'wp_ajax_bppg_delete_bg', array( $this, 'ajax_delete_current_bg' ) );
	}

	/**
	 * Load plugin language file
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'bp-custom-background-for-user-profile', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add a sub nav to My profile for changing Background
	 */
	public function setup_nav() {

		$bp = buddypress();

		$profile_link = bp_loggedin_user_domain() . $bp->profile->slug . '/';

		bp_core_new_subnav_item(
			array(
				'name'            => __( 'Change Background', 'bp-custom-background-for-user-profile' ),
				'slug'            => 'change-bg',
				'parent_url'      => $profile_link,
				'parent_slug'     => $bp->profile->slug,
				'screen_function' => array( $this, 'screen_change_bg' ),
				'user_has_access' => ( bp_is_my_profile() || is_super_admin() ),
				'position'        => 40,
			)
		);
	}

	/**
	 * Screen change background
	 */
	public function screen_change_bg() {

		// if the form was submitted, update here.
		if ( ! empty( $_POST['bpprofbg_save_submit'] ) ) {

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_upload_profile_bg' ) ) {
				die( __( 'Security check failed', 'bp-custom-background-for-user-profile' ) );
			}

			// handle the upload.
			$allowed_bg_repeat_options = bppg_get_image_repeat_options();
			$current_option            = $_POST['bg_repeat'];

			if ( isset( $allowed_bg_repeat_options[ $current_option ] ) ) {
				bp_update_user_meta( bp_loggedin_user_id(), 'profile_bg_repeat', $current_option );
			}

			if ( $this->handle_upload() ) {
				bp_core_add_message( __( 'Background uploaded successfully!', 'bp-custom-background-for-user-profile' ) );
			}
		}

		// hook the content.
		add_action( 'bp_template_title', array( $this, 'page_title' ) );
		add_action( 'bp_template_content', array( $this, 'page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Change Background Page title
	 */
	public function page_title() {
		echo '<h3>' . __( 'Profile Photo', 'bp-custom-background-for-user-profile' ) . '</h3>';
	}

	/**
	 * Upload page content
	 */
	public function page_content() {
		?>
        <style type='text/css'>
            .radio_labels1 {
                float: left;
                width: 75px;
                height: 30px;
                text-align: center;
                line-height: 30px;
            }

            .radio_labels2 {
                float: left;
                width: 75px;
                height: 30px;
                text-align: center;
                line-height: 18px;
            }

            .radio_items {
                float: left;
                width: 75px;
                text-align: center;
                padding-top: 10px;
            }

        </style>
        <form name="bpprofbpg_change" id="bpprofbpg_change" method="post" class="standard-form"
              enctype="multipart/form-data">

			<?php $image_url = bppg_get_image();
			if ( ! empty( $image_url ) ):
				?>
                <div id="bg-delete-wrapper">

                    <div class="current-bg">
                        <img src="<?php echo $image_url; ?>" alt="current background"/>
                    </div>
                    <a href='#'
                       id='bppg-del-image'><?php _e( 'Delete', 'bp-custom-background-for-user-profile' ); ?></a>
                </div>
			<?php endif; ?>
            <p>
				<?php _e( 'If you want to change your profile background, please upload a new image.', 'bp-custom-background-for-user-profile' ); ?>
				<?php printf( __( 'Maximum allowed file size for upload: %s', 'bp-custom-background-for-user-profile' ), $this->format_size( $this->get_max_upload_size() ) ); ?>
            </p>
            <label for="bprpgbp_upload">
                <input type="file" name="file" id="bprpgbp_upload" class="settings-input"/>
            </label>


            <br/>

            <h3 style="padding-bottom:0px;"> <?php _e( 'Please choose your background repeat option', 'bp-custom-background-for-user-profile' ); ?> </h3>


            <div style="clear:both;" class="bppg-repeat-options">
				<?php
				$repeat_options = bppg_get_image_repeat_options();
				$selected       = bppg_get_image_repeat( get_current_user_id() );

				//echo "<ul>";
				foreach ( $repeat_options as $key => $label ):
					?>
                    <div class="radio_items"><?php echo $label; ?><br/><input type="radio" name="bg_repeat"
                                                                              id="bg_repeat<?php echo $key; ?>"
                                                                              value="<?php echo $key; ?>" <?php echo checked( $key, $selected ); ?>/>
                    </div>

				<?php
				endforeach;
				//echo "</ul>";
				?>
            </div>

            <br/>
            <br/>
            <br/>

			<?php wp_nonce_field( "bp_upload_profile_bg" ); ?>
            <input type="hidden" name="action" id="action" value="bp_upload_profile_bg"/>
            <p class="submit"><input type="submit" id="bpprofbg_save_submit" name="bpprofbg_save_submit" class="button"
                                     value="<?php _e( 'Save', 'bp-custom-background-for-user-profile' ) ?>"/></p>
        </form>
		<?php
	}

	//handles upload, a modified version of bp_core_avatar_handle_upload(from bp-core/bp-core-avatars.php)
	public function handle_upload() {
		global $bp;

		//include core files
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		$max_upload_size = $this->get_max_upload_size();
		$max_upload_size = $max_upload_size * 1024; //convert kb to bytes
		$file            = $_FILES;

		//I am not changing the domain of erro messages as these are same as bp, so you should have a translation for this
		$uploadErrors = array(
			0 => __( 'There is no error, the file uploaded with success', 'bp-custom-background-for-user-profile' ),
			1 => __( 'Your image was bigger than the maximum allowed file size of: ', 'bp-custom-background-for-user-profile' ) . size_format( $max_upload_size ),
			2 => __( 'Your image was bigger than the maximum allowed file size of: ', 'bp-custom-background-for-user-profile' ) . size_format( $max_upload_size ),
			3 => __( 'The uploaded file was only partially uploaded', 'bp-custom-background-for-user-profiles' ),
			4 => __( 'No file was uploaded', 'bp-custom-background-for-user-profile' ),
			6 => __( 'Missing a temporary folder', 'bp-custom-background-for-user-profile' )
		);

		if ( isset( $file['error'] ) && $file['error'] ) {
			bp_core_add_message( sprintf( __( 'Your upload failed, please try again. Error was: %s', 'bp-custom-background-for-user-profile' ), $uploadErrors[ $file['file']['error'] ] ), 'error' );

			return false;
		}

		if ( ! ( $file['file']['size'] < $max_upload_size ) ) {
			bp_core_add_message( sprintf( __( 'The file you uploaded is too big. Please upload a file under %s', 'bp-custom-background-for-user-profile' ), size_format( $max_upload_size ) ), 'error' );

			return false;
		}

		if ( ( ! empty( $file['file']['type'] ) && ! preg_match( '/(jpe?g|gif|png)$/i', $file['file']['type'] ) ) || ! preg_match( '/(jpe?g|gif|png)$/i', $file['file']['name'] ) ) {
			bp_core_add_message( __( 'Please upload only JPG, GIF or PNG photos.', 'bp-custom-background-for-user-profile' ), 'error' );

			return false;
		}


		$uploaded_file = wp_handle_upload( $file['file'], array( 'action' => 'bp_upload_profile_bg' ) );

		//if file was not uploaded correctly
		if ( ! empty( $uploaded_file['error'] ) ) {
			bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'bp-custom-background-for-user-profile' ), $uploaded_file['error'] ), 'error' );

			return false;
		}

		// assume that the file uploaded successfully
		// delete any previous uploaded image.
		self::delete_bg_for_user();
		// save in usermeta.
		bp_update_user_meta( bp_loggedin_user_id(), 'profile_bg', $uploaded_file['url'] );
		bp_update_user_meta( bp_loggedin_user_id(), 'profile_bg_file_path', $uploaded_file['file'] );


		do_action( 'bppg_background_uploaded', $uploaded_file['url'] ); //allow to do some other actions when a new background is uploaded

		return true;
	}

	private function format_size( $size ) {
		$upload_size_unit = $size * 1024; //convert kb to bytes
		$sizes            = array( 'KB', 'MB', 'GB' );
		for ( $u = - 1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u ++ ) {
			$upload_size_unit /= 1024;
		}

		if ( $u < 0 ) {
			$upload_size_unit = 0;
			$u                = 0;
		} else {
			$upload_size_unit = (int) $upload_size_unit;
		}

		return $upload_size_unit . $sizes[ $u ];
	}

	//get the allowed upload size
	//there is no setting on single wp, on multisite, there is a setting, we will adhere to both
	private function get_max_upload_size() {
		$max_file_sizein_kb = get_site_option( 'fileupload_maxk' ); //it wil be empty for standard wordpress


		if ( empty( $max_file_sizein_kb ) ) {//check for the server limit since we are on single wp
			$upload_size_unit = wp_max_upload_size();

			$upload_size_unit   /= 1024; //KB
			$max_file_sizein_kb = $upload_size_unit;
		}

		return apply_filters( 'bppg_max_upload_size', $max_file_sizein_kb );
	}

	//inject css
	public function inject_css() {

		if ( ! function_exists( 'buddypress' ) ) {
			return;
		}

		$image_url = bppg_get_image();

		if ( empty( $image_url ) || apply_filters( 'bppg_iwilldo_it_myself', false ) ) {
			return;
		}

		$repeat_type = bp_get_user_meta( bp_loggedin_user_id(), 'profile_bg_repeat', true );

		if ( ! $repeat_type ) {
			$repeat_type = 'repeat';
		}

		?>
        <style type="text/css">
            body.is-user-profile {
                background: url(<?php echo $image_url; ?>) !important;
                background-repeat: <?php echo $repeat_type; ?> !important;
            }
        </style>
		<?php
	}

	//inject custom class for profile pages

	public function get_body_class( $classes ) {

		if ( ! function_exists( 'bp_is_user' ) ) {
			return $classes;
		}

		if ( ! bp_is_user() ) {
			return $classes;
		} else {
			$classes[] = 'is-user-profile';
		}

		return $classes;
	}

	//inject js if I am viewing my own profile
	public function inject_js() {

		if ( bp_is_my_profile() && bp_is_profile_component() && bp_is_current_action( 'change-bg' ) ) {
			wp_enqueue_script( 'bpbg-js', plugin_dir_url( __FILE__ ) . 'bppbg.js', array( 'jquery' ) );
		}
	}

	//ajax delete the existing image

	public function ajax_delete_current_bg() {

		//validate nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], "bp_upload_profile_bg" ) ) {
			die( 'what!' );
		}

		self::delete_bg_for_user();

		$message = '<p>' . __( 'Background image deleted successfully!', 'bp-custom-background-for-user-profile' ) . '</p>'; //feedback but we don't do anything with it yet, should we do something

		echo $message;

		exit( 0 );
	}

	//reuse it
	public function delete_bg_for_user() {

		$user_id = bp_loggedin_user_id();

		//delete the associated image and send a message
		$old_file_path = get_user_meta( $user_id, 'profile_bg_file_path', true );

		if ( $old_file_path ) {
			@unlink( $old_file_path ); //remove old files with each new upload
		}

		bp_delete_user_meta( $user_id, 'profile_bg_file_path' );
		bp_delete_user_meta( $user_id, 'profile_bg' );
		bp_delete_user_meta( $user_id, 'profile_bg_repeat' );
	}

}

/* public function for your use */

/**
 * Get image url.
 *
 * @param int $user_id user id.
 *
 * @return string  url of the image associated with current user or false
 */
function bppg_get_image( $user_id = 0 ) {

	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$image_url = bp_get_user_meta( $user_id, 'profile_bg', true );

	return apply_filters( 'bppg_get_image', $image_url, $user_id );
}


function bppg_get_image_repeat( $user_id = false ) {

	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}


	$current_repeat_option = bp_get_user_meta( $user_id, 'profile_bg_repeat', true );

	if ( ! $current_repeat_option ) {
		$current_repeat_option = 'repeat';
	}

	return $current_repeat_option;
}

function bppg_get_image_repeat_options() {
	return array(
		'repeat'    => __( 'Repeat', 'bp-custom-background-for-user-profile' ),
		'repeat-x'  => __( 'Repeat Horizontally', 'bp-custom-background-for-user-profile' ),
		'repeat-y'  => __( 'Repeat Vertically', 'bp-custom-background-for-user-profile' ),
		'no-repeat' => __( 'Do Not Repeat', 'bp-custom-background-for-user-profile' )
	);
}

$_profbg = new BPProfileBGChanger();
