<?php
/**
 * The file that defines the core theme class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://medhallal.com
 * @package    jYANN
 * @subpackage jYANN/core
 */

/**
 * Plugin Update Checker Library 4.11
 *
 * This is used to define admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    jYANN
 * @author     Medhallal <info@medhallal.com>
 */
class jYANN_Update_Checker
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
        add_action('admin_menu', array($this, 'licenseSettings'), null, $status);
    }

    public function licenseSettings($status) {
        //add new menu for jyann-settings page with page callback jyann-settings-page.
        add_theme_page("jYANN Settings", "jYANN Settings", "administrator", "jyann_settings", "jyann_settings_page", 8);

        //create the page.
        function jyann_settings_page(){
            ?>
            <div class="wrap">
                <h1>jYANN Settings</h1>
                <form method="post" action="options.php">
                    <?php
                    // display all sections for theme-options page
                    settings_fields( 'jyann_license_group' );
                    do_settings_sections("jyann-settings");
//                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
        add_settings_section (
            'jyann_license', //section name for the section to add
            'License', //section title visible on the page
            null, //callback for section description
            'jyann-settings'//page to which section will be added.
        );
        add_settings_field(
            'license_key',
            'License key',
            array( $this, 'license_key_callback'),
            'jyann-settings',
            'jyann_license',
            $status
        );
        register_setting(
            'jyann_license_group',
            'jyann_license_key'
        );
        register_setting(
            'jyann_license_group',
            'jyann_license_key_status'
        );
        register_setting(
            'jyann_license_group',
            'jyann_license_key_update_response'
        );


    }
    public function license_key_callback($status){
        if ($status['result'] === 'valid'){
            update_option('jyann_license_key_status', 'active');
            $isActive = true;
        }
        else{
            if ($status['result'] === 'success' || str_contains($status['message'], 'License key already in use on')){
                update_option('jyann_license_key_status', 'active');
                $isActive = true;
                $class  = 'notice notice-success is-dismissible';
                $message= str_replace("Reached maximum activation. ", "", $status['message']);;
                $link   = get_site_url().'/wp-admin/customize.php';
                $btn    = "let's customize your theme";
            }else{
                update_option('jyann_license_key_status', 'not_active');
                $isActive = false;
                $class  = 'notice notice-error';
                $message= $status['message'] . ", please activate Your license with a valid product key.";
                $link   = get_site_url().'/wp-admin/themes.php?page=jyann_settings';
                $btn    = "let's activate now";
            }
            printf( '<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>', esc_attr( $class ), esc_html( $message ) , esc_html( $link ) , esc_html( $btn ) );
        }
        $value = get_option('jyann_license_key');
        if ($isActive) {
            $input_hidden = "<input type='hidden' value='$value' name='jyann_license_key' />";
            $input_valid = 'input-license-valid';
            $input_disable = "disabled='disabled'";
            $input_mask = '_mask';
            empty( $value ) ? $value2 = '' : $value2 = str_repeat( '*', strlen($value) - 5 ) . substr( $value, - 5 );
            $status_class = 'button-license-deactivate';
            $button_txt = 'Deactivate';
            $error = '';
            $status = 'valid';
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
            $status = 'invalid';
        }
        wp_nonce_field( 'jyann_nonce', 'jyann_nonce_field', true, true );
        $output = $input_hidden . "<input $input_disable class='regular-text $input_valid' type='text' name='jyann_license_key$input_mask' aria-describedby='jyann_license_key_description' id='jyann_license_key' value='$value2'><span class='button $status_class'>$status</span><input type='submit' name='submit' id='submit' class='button button-primary' value='$button_txt'>$error";//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
        ?>
        <p class="description" id="license_key_description">Insert your license key here, you can purchase it from <a href="https://medhallal.com/wp-pro/jyann-pro/">medhallal.com</a>.</p>
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
        $status = get_option('jyann_license_key_status');
        $key = get_option('jyann_license_key');
        $timeout = get_option('jyann_license_key_update_response');
        $future = time()+60*60*25;
        if ($future>$timeout && $timeout>time()) {
            if ($status === 'active' && !empty($key)){
                $this->checkUpdate($fullPath);
                return ['result'=>'valid'];
            }else{
                return ['result'=>'error', 'message'=>'You didn\'t activate the theme'];
            }
        }else{
            update_option('jyann_license_key_update_response', time()+60*60*24);
            $result = $this->licenseCheck($status, $key);
            if ($result['result'] === 'valid' || $result['result'] === 'success'){
                $this->checkUpdate($fullPath);
            }
            return $result;
        }
    }
    private function licenseCheck($status, $key){
        if ($status === 'active' && !empty($key)){
            $checkResult = $this->checkLicense($key);
            $checkResult = json_decode($checkResult, true);
            if ($checkResult['status']==='active'){
                return ['result'=>'valid'];
            }else{
                return $checkResult;
            }
        }else{
            $checkResult = $this->activeLicense($key);
            return json_decode($checkResult, true);
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
    private function activeLicense($key){
        if($key !== null && $key !== ''){
            // prepare the data
            $api_params = array ();
            $api_params['slm_action'] = 'slm_activate';
            $api_params['secret_key'] = self::$secretKey;
            $api_params['license_key'] = $key;
            $api_params['item_reference'] = '';
            $api_params['registered_domain'] = get_site_url();

            // Send query to the license manager server
            // Process the return values
            $result = wp_remote_get(add_query_arg($api_params, self::$postURL), array('timeout' => 20, 'sslverify' => false));
            return $result['body'];
        }else{
            return "['result'=>'error', 'message'=>'The license key is invalid']";
        }
    }
    private function checkUpdate($fullPath){
        require dirname(__FILE__) . '/update-checker/load-v4p11.php';
        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
            'https://medhallal.com/wp-pro/jyann-pro/details.json',
            $fullPath,
            'jyann-pro'
        );
    }
}
