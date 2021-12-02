<?php
/**
 * The file that defines the core theme class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://medhallal.com
 * @package    MEDHALLAL_THEME
 * @subpackage MEDHALLAL_THEME/core
 */

/**
 * Plugin Update Checker Library 4.11
 *
 * This is used to define admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    MEDHALLAL_THEME
 * @author     Medhallal <info@medhallal.com>
 */
class Medhallal_Theme_Update_Checker
{

    /**
     * @var string $postURL Post URL
     */
    private static $postURL = "https://medhallal.com";
    /**
     * @var string $secretKey The Secret key
     */
    private static $secretKey = "61a39cc11fb6e6.22857874";

    public function __construct($fullPath){
        $status = $this->licenseStatus($fullPath);
        if (isset($status['deactivate'])){
            update_option(THEME_SLUG.'_license_key', '');
            update_option(THEME_SLUG.'_license_key_mask', '');
            update_option(THEME_SLUG.'_license_key_status', 'not_active');
        }else{
            $status['deactivate'] = false;
        }
        if ($status['result'] === 'valid'){
            update_option(THEME_SLUG.'_license_key_status', 'active');
        }
        else{
            $message = $status['message'];
            if ($status['result'] === 'success' || str_contains($status['message'], 'License key already in use on')){
                if ($status['deactivate']){
                    $link   = '';
                    $btn    = '';
                }else{
                    update_option(THEME_SLUG.'_license_key_status', 'active');
                    $status['result'] = 'success';
                    $message= str_replace("Reached maximum activation. ", "", $message);;
                    $link   = get_site_url().'/wp-admin/customize.php';
                    $btn    = "let's customize your theme";
                }
                $class  = 'notice notice-success is-dismissible';
            }else{
                if ($status['deactivate']){
                    $link   = '';
                    $btn    = '';
                }else{
                    update_option(THEME_SLUG.'_license_key_status', 'not_active');
                    $message.=", please activate Your license with a valid product key.";
                    $link   = get_site_url().'/wp-admin/themes.php?page='.THEME_SLUG.'-settings';
                    $btn    = "let's activate now";
                }
                $class  = 'notice notice-error';
            }
            printf( '<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>', esc_attr( $class ), esc_html( $message ) , esc_html( $link ) , esc_html( $btn ) );
        }
        add_action('admin_menu', function() use ( $status ) {
            $this->licenseSettings( $status ); });
    }

    public function licenseSettings($status) {
        //add new menu for medhallal_theme_settings page with page callback medhallal_theme_settings_page.
        add_theme_page(THEME_NAME." Settings", THEME_NAME." Settings", "administrator", THEME_SLUG."-settings", "medhallal_theme_settings_page", 8);

        //create the page.
        function medhallal_theme_settings_page(){
            ?>
            <div class="wrap">
                <h1><?php echo THEME_NAME ?> Settings</h1>
                <form method="post" action="options.php">
                    <?php
                    // display all sections for theme-options page
                    settings_fields( 'medhallal_theme_license_group' );
                    do_settings_sections(THEME_SLUG."-settings");
//                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
        add_settings_section (
            'medhallal_theme_license', //section name for the section to add
            'License', //section title visible on the page
            null, //callback for section description
            THEME_SLUG.'-settings'//page to which section will be added.
        );
        add_settings_field(
            'license_key',
            'License key',
            array( $this, 'license_key_callback'),
            THEME_SLUG.'-settings',
            'medhallal_theme_license',
            $status
        );
        register_setting(
            'medhallal_theme_license_group',
            THEME_SLUG.'_license_key'
        );
        register_setting(
            'medhallal_theme_license_group',
            THEME_SLUG.'_license_key_mask'
        );
        register_setting(
            'medhallal_theme_license_group',
            THEME_SLUG.'_license_key_status'
        );
        register_setting(
            'medhallal_theme_license_group',
            THEME_SLUG.'_license_key_update_response'
        );
    }
    public function license_key_callback($status){
        $value = get_option(THEME_SLUG.'_license_key');
        if (($status['result'] === 'valid' || $status['result'] === 'success') && !$status['deactivate']) {
            $input_hidden = "<input type='hidden' value='$value' name='".THEME_SLUG."_license_key' />";
            $input_valid = 'input-license-valid';
            $input_disable = "";
            $input_mask = '_mask';
            empty( $value ) ? $value2 = '' : $value2 = str_repeat( '*', strlen($value) - 5 ) . substr( $value, - 5 );
            $status_class = 'button-license-deactivate';
            $button_txt = 'Deactivate';
            $error = '';
            $input_status = 'valid';
        }
        else{
            $input_hidden = '';
            $input_valid = '';
            $input_disable = '';
            $input_mask = '';
            $value2 = $value;
            $status_class = 'button-license-activate';
            $button_txt = 'Activate';
            $error = "<p style='color:#dd3d36'>" . wp_kses_data( $status['message'] ) . "</p>";
            $input_status = 'invalid';
        }
        wp_nonce_field( THEME_SLUG.'_nonce', THEME_SLUG.'_nonce_field', true, true );
        $output = $input_hidden . "<input $input_disable class='regular-text $input_valid' type='text' name='".THEME_SLUG."_license_key$input_mask' aria-describedby='".THEME_SLUG."_license_key_description' id='".THEME_SLUG."_license_key' value='$value2'><span class='button $status_class'>$input_status</span><input type='submit' name='submit' id='submit' class='button button-primary' value='$button_txt'>$error";//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
        ?>
        <p class="description" id="license_key_description">Insert your license key here, you can purchase it from <a href="https://medhallal.com/wp-pro/<?php echo THEME_SLUG ?>">medhallal.com</a>.</p>
        <style>
            input.input-license-valid {
                border: 1px solid #7ad03a;
            }
            .button.button-license-deactivate {
                color: #fff;
                background: #7ad03a;
                margin-inline-start: 10px;
                cursor: default;
            }

            .button.button-license-activate {
                color: #fff;
                background: #dd3d36;
                margin-inline-start: 10px;
                cursor: default;
            }
            input[type=submit]{
                margin-inline-start: 10px !important;
            }
        </style>
        <?php
    }
    private function licenseStatus($fullPath){
        $key = get_option(THEME_SLUG.'_license_key');
        $key_mask = get_option(THEME_SLUG.'_license_key_mask');
        if (empty($key)){
            return ['result'=>'error', 'message'=>'You didn\'t activate the theme'];
        }elseif (!empty($key_mask)){
            $result = json_decode($this->toggleLicense($key, 'deactivate'), true);
            $result['deactivate'] = true;
            return $result;
        }
        $status = get_option(THEME_SLUG.'_license_key_status');
        if (empty($status)){
            $status = 'not_active';
        }
        $timeout = get_option(THEME_SLUG.'_license_key_update_response');
        if (empty($timeout)){
            $timeout = time()+60*60*30;
        }
        $future = time()+60*60*25;
        if ($future>$timeout && $timeout>time()) {
            if ($status === 'active' && !empty($key)){
                $this->checkUpdate($fullPath);
                return ['result'=>'valid'];
            }else{
                return ['result'=>'error', 'message'=>'You didn\'t activate the theme'];
            }
        }else{
            update_option(THEME_SLUG.'_license_key_update_response', time()+60*60*24);
            $result = $this->licenseCheck($status, $key);
            if ($result['result'] === 'valid' || $result['result'] === 'success'){
                $this->checkUpdate($fullPath);
            }
            return $result;
        }
    }
    private function licenseCheck($status, $key){
        if ($status === 'active' && !empty($key)){
            $checkResult = json_decode($this->checkLicense($key), true);
            if ($checkResult['status']==='active'){
                return ['result'=>'valid'];
            }else{
                return $checkResult;
            }
        }else{
            return json_decode($this->toggleLicense($key), true);
        }
    }
    private function checkLicense($key){
        // prepare the data
        $api_params = array ();
        $api_params['slm_action'] = 'slm_check';
        $api_params['secret_key'] = self::$secretKey;
        $api_params['license_key'] = $key;

        // Send query to the license manager server
        // Process the return values
        $result = wp_remote_get(add_query_arg($api_params, self::$postURL), array('timeout' => 20, 'sslverify' => false));
        return $result['body'];
    }
    private function toggleLicense($key, $action = 'activate'){
        if($key !== null && $key !== ''){
            // prepare the data
            $api_params = array ();
            $api_params['slm_action'] = 'slm_'.$action;
            $api_params['secret_key'] = self::$secretKey;
            $api_params['license_key'] = $key;
            $api_params['item_reference'] = '';
            $api_params['registered_domain'] = get_site_url();

            // Send query to the license manager server
            // Process the return values
            $result = wp_remote_get(add_query_arg($api_params, self::$postURL), array('timeout' => 20, 'sslverify' => false));
            return $result['body'];
        }else{
            if ($action==='activate') {
                return "['result'=>'error', 'message'=>'The license key is invalid']";
            }else{
                return "['result'=>'error', 'message'=>'We couldn\'t deactivate your license, please activate and deactivate it again']";
            }
        }
    }
    private function checkUpdate($fullPath){
        require dirname(__FILE__) . '/update-checker/load-v4p11.php';
        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
            'https://medhallal.com/wp-pro/jyann-pro/details.json',
            $fullPath,
            THEME_SLUG
        );
    }
}
