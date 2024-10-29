<?php
/**
* Plugin Name: Apollo Site Tools
* Plugin URI: https://apollodatasolutions.com/
* Description: Add Google/FB tracking and other best practices quickly to your Web site without tons of plugins or having to pay
* Version: 2.7
* Author: Apollo
* Author URI: https://apollodatasolutions.com/
**/

$options = get_option('plugin_options_apollo');

if ($options['disable_admin'] == 1) {
    add_action('plugins_loaded', 'get_user_info');
} else {
    add_google_analytics($options);
    add_fb_pixel($options);
}

if ($options['pass_change_notification'] == 1) {
    if ( ! function_exists( 'wp_password_change_notification' ) ) {
        function wp_password_change_notification( $user ) {
            return;
        }
    }
}


register_deactivation_hook( __FILE__, 'apollo_plugin_remove_database' );
function apollo_plugin_remove_database() {
     global $wpdb;
     $table_name = $wpdb->prefix . 'apollo_contacts_manager';
     $sql = "DROP TABLE IF EXISTS $table_name";
     $wpdb->query($sql);
     delete_option("my_plugin_db_version");
}

function apollo_contacts_manager_install () {
   global $wpdb;
   $website_contacts_manager_db_version = "1.0";

   $table_name = $wpdb->prefix . "apollo_contacts_manager";

   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

    $sql = "CREATE TABLE " . $table_name . " (
          `id` int(11) NOT NULL AUTO_INCREMENT,
        `full_name` varchar(50) DEFAULT NULL,
        `email` varchar(100) DEFAULT NULL,
        `phone` varchar(25) DEFAULT NULL,
        `guests` int(3) DEFAULT NULL,
        `special_request` text,
        `date` Date,
        `time` Time,
        `inserted` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option("website_contacts_manager_db_version", $website_contacts_manager_db_version);
   }
   $table_name2 = $wpdb->prefix . "website_contacts_manager_settings";
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name2'") != $table_name2) {

        $sql = "CREATE TABLE " . $table_name2 . " (
          `email_to` varchar(255) default NULL,
          `email_from` varchar(50) default NULL,
          `email_subject` varchar(75) default NULL,
          `success_msg` varchar(150) default NULL,
          `failed_msg` varchar(150) default NULL
        );";
        $sql .= " INSERT INTO " . $table_name2 . "
         (`email_to`, `email_from`, `email_subject`, `success_msg`, `failed_msg`) VALUES
        ('you@yourdomain.com', 'websitecontact@yourdomain.com', 'Website contact request',
        'Thank you, your information was submitted successfully.', 'Please complete required fields.');";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
   }

       //set permissions for plugin folder so export csv file can be created and written to
    if ( ! defined('WP_PLUGIN_DIR') ) { define('MY_PLUGIN_DIR', dirname(__FILE__)); }
       if(file_exists(WP_PLUGIN_DIR . '/website-contacts-manager/')) {
        chmod(WP_PLUGIN_DIR . '/website-contacts-manager/', 0777);
    }

}
register_activation_hook(__FILE__,'apollo_contacts_manager_install');


add_action('admin_menu', 'apollo_contacts_manager_plugin_menu');
function apollo_contacts_manager_plugin_menu() {
    // add_menu_page('Website Contacts', 'Apollo Contacts', 'manage_options', 'website-contact-list', 'apollo_contacts_manager_list',
    // 'dashicons-admin-users','22');
    // add_submenu_page('website-contact-list', 'Website Contacts Settings', 'Settings', 'manage_options', 'website-contacts-settings','apollo_contacts_settings' );

}

##### BEGIN WEBSITE CONTACTS SETTINGS #####
function apollo_contacts_settings() {
    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    echo '<div class="wrap">';
    echo '<div class="icon32" id="contacts"></div>';
    echo '<h2>Website Contact Form Settings</h2><br />';
    apollo_contact_form_settings();
    echo '</div>';
}

add_action ( 'apollo_contact_form_settings', 'apollo_contact_form_settings' );
function apollo_contact_form_settings() {
    $msg = '';
    echo '<div class="postbox" style="padding:15px 0 15px 15px;">';

    global $wpdb;
    $table_name = $wpdb->prefix . "website_contacts_manager_settings";

    //get values for this record
    $record = $wpdb->get_row("SELECT * FROM $table_name");
    $email_to           = $record->email_to;
    $email_from         = $record->email_from;
    $email_subject      = $record->email_subject;
    $form_success_msg   = $record->success_msg;
    $form_failed_msg    = $record->failed_msg;
    $wpdb->flush();
    //end get values for this record

    if(isset($_POST['btnSubmit']) && $_POST['btnSubmit'] == 'Submit') {
        $record = $wpdb->get_row("SELECT * FROM $table_name");
        $email_to           = $_POST['email_to'];
        $email_from         = $_POST['email_from'];
        $email_subject      = $_POST['email_subject'];
        $form_success_msg   = $_POST['form_success_msg'];
        $form_failed_msg    = $_POST['form_failed_msg'];

        $msg .= (!empty($email_to   )       ? '' : 'To Email is required.<br />');
        $msg .= (!empty($email_from)        ? '' : 'From Email is required.<br />');
        $msg .= (!empty($email_subject)     ? '' : 'Email Subject is required.<br />');
        $msg .= (!empty($form_success_msg)  ? '' : 'Success Message is required.<br />');
        $msg .= (!empty($form_failed_msg)   ? '' : 'Failed Message is required.<br />');

        if(empty($msg)) {
            $sql = $wpdb->prepare("
            UPDATE ".$table_name."
            SET email_to=%s, email_from=%s, email_subject=%s, success_msg=%s, failed_msg=%s",
            $email_to, $email_from, $email_subject, $form_success_msg, $form_failed_msg );
            $wpdb->query($sql);
            //$wpdb->show_errors();
            $msg = 'SUCCESS: Settings have been saved!';
        }
    }//if $Post
?>
<?php if(!empty($msg)) { echo '<div id="valmsg">'.$msg.'</div>'; } ?>
<form action="" method="POST" name="frmMain">
<style>
ul li label { float: left;width: 140px; }
#edButtonHTML { margin-top: 1px; background: #f1f1f1; border: none; color: #999999; }
#edButtonPreview { margin-top: 1px;  background: #f1f1f1; border: none; color: #999999; }
#edButtonHTML.active { margin-top: 1px; background: #e5e5e5; color: #000000; }
#edButtonPreview.active { margin-top: 1px; background: #e5e5e5; color: #000000; }
</style>
<ul>
<li><label for="to_email">To Email<span> *</span>: </label><input type="text" id="email_to" maxlength="150" size="100" name="email_to" value="<?php echo @$email_to; ?>" /></li>
<li><label for="from_email">From Email<span> *</span>: </label><input type="text" id="email_from" maxlength="150" size="100" name="email_from" value="<?php echo @$email_from; ?>" /></li>
<li><label for="subject">Email Subject<span> *</span>: </label><input type="text" id="email_subject" maxlength="150" size="100" name="email_subject" value="<?php echo @$email_subject; ?>" /></li>
<li><label for="form_success_msg">Success Message<span> *</span>: </label><input type="text" id="form_success_msg" maxlength="150" size="100" name="form_success_msg" value="<?php echo @$form_success_msg; ?>" /></li>
<li><label for="form_failed_msg">Failed Message<span> *</span>: </label><input type="text" id="form_failed_msg" maxlength="150" size="100" name="form_failed_msg" value="<?php echo @$form_failed_msg; ?>" /></li>
<li>&nbsp;</li>
<li><label for="submit">&nbsp;</label><input name="btnSubmit" type="submit" value="Submit" class="button-primary"/></li>
</ul>
</form>
<?php
}//end apollo_contacts_settings() function
##### END WEBSITE CONTACTS SETTINGS #####



function apollo_contacts_manager_list() {
    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    echo '<div class="wrap">';
    echo '<div class="icon32" id="contacts"></div>';
    echo '<h2>Website Contacts</h2>';
    apollo_list_website_contacts();
    echo '</div>';
}
add_action ( 'apollo_contacts_manager_list', 'apollo_contacts_manager_list' );  //hook name, function name




function apollo_list_website_contacts() {
    global $wpdb;
    $table_name = $wpdb->prefix . "apollo_contacts_manager";

    //begin delete
    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
        $id = $_REQUEST['id'];

        //delete from db
        $sql = $wpdb->prepare("
        DELETE FROM ".$table_name."
        WHERE id = %d",
        $id." LIMIT 1" );
        $wpdb->query($sql);
        $wpdb->flush();
        $deletemsg = "Record has been deleted.";
    }
    //end delete

    $where       = '1=1';
    $searchstring = '';
    if(isset($_REQUEST['s']) && !empty($_REQUEST['s'])) {
        $searchstring = trim($_REQUEST['s']);
        $where .= " AND (fname LIKE '%$searchstring%'";
        $where .= " OR lname LIKE '%$searchstring%'";
        $where .= " OR full_name LIKE '%$searchstring%'";
        $where .= " OR company_name LIKE '%$searchstring%'";
        $where .= " OR email LIKE '%$searchstring%') ";
    }

    //get total record count for pagination
    $items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE %s", $where));

    //build pagination
    require_once("pagination.class.php");
    $p = new pagination;
    $p->items($items);
    $p->limit(200); // Limit entries per page
    $p->target("admin.php?page=website-contact-list");
    $p->currentPage($_GET[$p->paging]); // Gets and validates the current page
    $p->nextLabel('');
    $p->prevLabel('');
    $p->calculate(); // Calculates what to show
    $p->parameterName('paging');
    $p->adjacents(1); //No. of page away from the current page
    if(!isset($_GET['paging'])) {
        $p->page = 1;
     } else {
        $p->page = $_GET['paging'];
    }
    $limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
    //end build pagination

    //get list
    $get_data = $wpdb->get_results("SELECT id,full_name,email,phone,guests,date,time,DATE_FORMAT(inserted, '%m/%d/%Y') as display_date FROM $table_name WHERE $where ORDER BY inserted DESC $limit");

?>
<?php if(isset($deletemsg) && !empty($deletemsg)) { echo '<div id="actionmsg">'.$deletemsg.'</div>'; } ?>
<form class="search-form" action="admin.php?page=website-contact-list" method="post">
<p class="search-box">
    <input type="text" id="contact-search-input" name="s" value="<?php echo @$searchstring; ?>" />
    <input type="submit" value="Search Contacts" class="button" />
</p>
</form>
<?php $siteurl = get_option('siteurl'); ?>
<div class="tablenav">
<a href="<?php echo $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/website-contacts-export.php'; ?>" target="_blank">Export Contacts</a>
<div class='tablenav-pages'><?php echo $p->show(); ?></div>
</div>
  <table class="widefat">
<thead>
    <tr>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Guests</th>
        <th>Date</th>
        <th>Time</th>
        <th>Added on</th>
    </tr>
</thead>
<tfoot>
    <tr>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Guests</th>
        <th>Date</th>
        <th>Time</th>
        <th>Added on</th>
    </tr>
</tfoot>
<tbody>
<?php
if( $get_data ) :
$i=0;
foreach ( $get_data as $record ) :
    $rowclass = (($i%2) == 0 ? 'class="alternate"' : '');
    $full_name = (!empty($record->full_name) ? $record->full_name : $record->fname.' '.$record->lname );
?>
   <tr <?php echo $rowclass; ?>>
     <td class="column-title"><strong><a href="?page=website-contact-edit&id=<?php echo $record->id; ?>" title="Click to edit"><?php echo (!empty($full_name) && $full_name !==' ' ? $full_name : 'N/A'); ?></a></strong>
     <div class="row-actions"><span class='edit'><a href="?page=website-contact-edit&id=<?php echo $record->id; ?>" title="Edit this item">Edit</a> | </span><span class='trash'><a href="?page=website-contact-list&id=<?php echo $record->id; ?>&action=delete" title="Delete this item" onClick="javascript:return confirmMsg('Warning: This action will delete selected record. Continue?');">Delete</a></span></div>
     </td>
     <td><?php echo $record->phone; ?></td>
     <td class="column-title"><a href="mailto:<?php echo $record->email; ?>" title="Click to email"><?php echo $record->email; ?></a></td>
     <td><?php echo $record->guests; ?></td>
     <td><?php echo $record->date; ?></td>
     <td><?php echo $record->time; ?></td>
     <td><?php echo $record->display_date; ?></td>
   </tr>
<?php
$i++;
endforeach;
else :
if(isset($searchstring) && !empty($searchstring)) {
    echo '<tr><td><br />No results found for <strong>'.$searchstring.'</strong>.<br /><br /></td></tr>';
} else {
    echo '<tr><td><br />No records found.<br /><br /></td></tr>';
}
endif;
$wpdb->flush();
?>
</tbody>
</table>
<?php if( $get_data ) : ?>
<div class="tablenav">
<div class='tablenav-pages'><?php echo $p->show(); ?></div>
</div>
<?php
endif;
}//end apollo_list_website_contacts() function



function get_user_info() {
    $options = get_option('plugin_options_apollo');

    $current_user = wp_get_current_user();

    if (user_can($current_user, 'administrator')) {
        // Do not add tracking for admins
    } else {
        add_google_analytics($options);
        add_fb_pixel($options);
    }
}


function add_google_analytics($options) {
    if (isset($options['text_string']) && $options['text_string'] != '') {
        add_action('wp_head', 'apollo_ga_main');
    }
}


function add_fb_pixel($options){
    if (isset($options['plugin_facebook_pixel']) && $options['plugin_facebook_pixel'] != '') {
        add_action('wp_head', 'add_facebook_pixel');
    }
}


add_action('wp_head', 'add_google_webmaster');
function add_google_webmaster() {
    global $options;

    if (isset($options['plugin_google_webmaster']) && $options['plugin_google_webmaster'] != '') {
        echo "
            <meta name='google-site-verification' content='". $options['plugin_google_webmaster'] ."'>
";
    }
}

add_action('wp_head', 'add_custom_js_header');
function add_custom_js_header(){
    global $options;

    if (isset($options['plugin_custom_js_header'])  && $options['plugin_custom_js_header'] != '') {
        echo "
        <!-- Custom JS Header -->
            ". $options['plugin_custom_js_header'] ."
        <!-- Custom JS Header END -->
";
    }
}


add_action('wp_footer', 'add_custom_js_footer', 100);
function add_custom_js_footer(){
    global $options;

    if (isset($options['plugin_custom_js_footer']) && $options['plugin_custom_js_footer'] != '') {
        echo "
        <!-- Custom JS Footer -->
            ". $options['plugin_custom_js_footer'] ."
        <!-- Custom JS Footer END -->
";
    }
}


add_action('wp_head', 'add_facebook_pixel');
function add_facebook_pixel(){
    global $options;

    if (isset($options['plugin_facebook_pixel']) && $options['plugin_facebook_pixel'] != '') {
        echo "
        <!-- Facebook Pixel Code -->
        <script>
          !function(f,b,e,v,n,t,s)
          {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
          n.callMethod.apply(n,arguments):n.queue.push(arguments)};
          if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
          n.queue=[];t=b.createElement(e);t.async=!0;
          t.src=v;s=b.getElementsByTagName(e)[0];
          s.parentNode.insertBefore(t,s)}(window, document,'script',
          'https://connect.facebook.net/en_US/fbevents.js');
          fbq('init', '". $options['plugin_facebook_pixel'] ."');
          fbq('track', 'PageView');
        </script>
        <noscript><img height='1' width='1' alt='Facebook Pixel'
          src='https://www.facebook.com/tr?id=". $options['plugin_facebook_pixel'] ."&ev=PageView&noscript=1'
        /></noscript>
        <!-- End Facebook Pixel Code -->
        ";
    }
}

function apollo_ga_main(){
    global $options;

    if (isset($options['text_string']) && $options['text_string'] != '' && $options['plugin_gtag'] == ''){
    echo "
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src='https://www.googletagmanager.com/gtag/js?". $options['text_string'] ."'></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());

          gtag('config', '".$options['text_string']."');
        </script>
    ";
};
}


add_action('wp_head', 'add_gtag');

function add_gtag() {
    global $options;

    if (isset($options['plugin_gtag']) && $options['plugin_gtag'] != '' && $options['text_string']) {
        echo "
            <!-- Global site tag (gtag.js) - Google Analytics -->
            <script async src='https://www.googletagmanager.com/gtag/js?". $options['text_string'] ."'></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());

              gtag('config', '".$options['text_string']."');
              gtag('config', '".$options['plugin_gtag']."');
          </script>
        ";
    } else if (isset($options['plugin_gtag']) && $options['plugin_gtag'] != '' && $options['text_string'] == null) {
        echo "
            <!-- Global site tag (gtag.js) - Google Analytics -->
            <script async src='https://www.googletagmanager.com/gtag/js?". $options['plugin_gtag'] ."'></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());

              gtag('config', '".$options['plugin_gtag']."');
          </script>
        ";
    }
}


// add the admin options page
add_action('admin_menu', 'plugin_admin_add_page');
function plugin_admin_add_page() {
    add_menu_page('Apollo Site Tools', 'Apollo Site Tools', 'manage_options', 'apollo_ga_plugin', 'apollo_ga_form_input');
}

add_shortcode( 'apollo_form', 'cf_shortcode' );

add_shortcode( 'include_file', 'include_shortcode' );

function include_shortcode($atts){
  extract( shortcode_atts( array(
    'file' => ''
  ), $atts ) );

  if ($file!='')
    return @file_get_contents($file);
}

function wpdocs_footag_func() {
    return '
    <h1>Apollo Form</h1>
    <form method="post" action="'.esc_url( $_SERVER['REQUEST_URI'] ).'" role="form" id="apollo-reservation-form">
        <div class="apollo-form-status-message"></div>
        <input class="form-control" id="full_name" name="cf-name" type="text" placeholder="Name" value="" maxlength="50">
        <input class="form-control" id="email" name="cf-email" type="mail" placeholder="Email" value="" maxlength="25">
        <input class="button pull-right" type="submit" value="SUBMIT" name="cf-submitted">
    </form>
    ';
}

// wp_mail()
function deliver_mail() {

    // if the submit button is clicked, send the email
    if ( isset( $_POST['cf-submitted'] ) ) {

        // sanitize form values

        $subject = 'Contact Form Submission';

        $name = sanitize_text_field( $_POST["cf-name"] );
        $phone = $_POST["cf-phone"];
        $email = sanitize_email( $_POST["cf-email"] );
        $guests = $_POST["cf-guests"];
        $special_request = $_POST["cf-special-request"];
        $date = $_POST["cf-date"];
        $time = $_POST["cf-time"];

        $message = 'Name: '.$name;
        $message .='Phone: '.$phone;
        $message .='Email: '.$email;
        $message .='Guests: '.$guests;
        $message .='Special Request: '.$special_request;
        $message .='Date: '.$date;
        $message .='Time: '.$time;

        date_default_timezone_set('US/Eastern');

        global $wpdb;
        $table_name = $wpdb->prefix . "apollo_contacts_manager";

        // get the blog administrator's email address
        $to = get_option( 'admin_email' );

        $table_name_second = $wpdb->prefix . "website_contacts_manager_settings";

        //get values for this record
        $record = $wpdb->get_row("SELECT * FROM $table_name_second");
        $email_to           = $record->email_to;
        $email_from         = $record->email_from;
        $email_subject      = $record->email_subject;
        $form_success_msg   = $record->success_msg;
        $form_failed_msg    = $record->failed_msg;

        $headers = "From: <".$email_from.">" . "\r\n";
        // $wpdb->flush();

        // If email has been process for sending, display a success message
        if ( wp_mail( $email_to , $email_subject, $message, $headers ) ) {
            echo '<div>';
            echo $form_success_msg;
            echo '</div>';
            $timestamp = date('Y-m-d H:i:s');

            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $sql = "INSERT INTO " . $table_name . "
                 (`full_name`, `phone`, `email`, `guests`, `special_request`,`date`,`time`,`inserted`) VALUES
                ('".$name."','".$phone."','".$email."','".$guests."','".$special_request."','".$date."','".$time."','".$timestamp."');";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            }

        } else {
            echo $form_failed_msg;
        }
    }
}

function html_form_code() {
    echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
    echo '<p>';
    echo 'Name<br />';
    echo '<input type="text" name="cf-name" class="apollo-form-name" pattern="[a-zA-Z0-9 ]+" value="' . ( isset( $_POST["cf-name"] ) ? esc_attr( $_POST["cf-name"] ) : '' ) . '" size="40" />';
    echo '</p>';
    echo '<p>';
    echo 'Phone<br />';
    echo '<input type="tel" name="cf-phone" class="apollo-form-phone" value="' . ( isset( $_POST["cf-phone"] ) ? esc_attr( $_POST["cf-phone"] ) : '' ) . '" size="40" />';
    echo '</p>';
    echo '<p>';
    echo 'Email<br />';
    echo '<input type="email" name="cf-email" class="apollo-form-email" value="' . ( isset( $_POST["cf-email"] ) ? esc_attr( $_POST["cf-email"] ) : '' ) . '" size="40" />';
    echo '</p>';
    echo '<p>';
    echo 'Guests (required) <br />';
    echo '<select name="cf-guests" class="apollo-form-guests" value="' . ( isset( $_POST["cf-guests"] ) ? esc_attr( $_POST["cf-guests"] ) : '' );
    echo '<option value="1">1</option>';
    echo '<option value="2">2</option>';
    echo '<option value="3">3</option>';
    echo '<option value="4">4</option>';
    echo '<option value="5">5</option>';
    echo'</select>';
    echo '</p>';
    echo '<p>';
    echo 'Special Request<br />';
    echo '<textarea rows="3" cols="5" class="apollo-form-special-request" name="cf-special-request">' . ( isset( $_POST["cf-special-request"] ) ? esc_attr( $_POST["cf-special-request"] ) : '' ) . '</textarea>';
    echo '</p>';
    echo '<p>';
    echo 'Date<br />';
    echo '<input type="date" name="cf-date" class="apollo-form-date" value="' . ( isset( $_POST["cf-date"] ) ? esc_attr( $_POST["cf-date"] ) : '' ) . '">';
    echo '</p>';
    echo '<p>';
    echo 'Time<br />';
    echo '<input type="time" name="cf-time" class="apollo-form-time" value="' . ( isset( $_POST["cf-time"] ) ? esc_attr( $_POST["cf-time"] ) : '' ) . '">';
    echo '</p>';
    echo '<p><input type="submit" name="cf-submitted" class="apollo-form-submit" value="Send"/></p>';
    echo '</form>';
}

function cf_shortcode() {
    ob_start();
    deliver_mail();
    html_form_code();

    return ob_get_clean();
}


add_shortcode( 'apollo_instagram_feed', 'apollo_apollo_instagram_feed' );
function apollo_apollo_instagram_feed(){
    // $my_plugin = ABSPATH . 'Archive-1/js/lemonsta.js';
    global $options;

    if (isset($options['plugin_instagram'])  && $options['plugin_instagram'] != '') {
        $instagram_user_id = $options["plugin_instagram"];

        return '
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script src="./wp-content/plugins/apollo-site-tools/js/lemonsta.js?'.$path.'"></script>
        <link rel="stylesheet" type="text/css" href="./wp-content/plugins/apollo-site-tools/css/lemonsta.css">
        <div class="apollo-instagram-feed"></div>
        <script>$(".apollo-instagram-feed").lemonsta("'.$instagram_user_id.'");</script>
        ';
    }
}


add_action( 'admin_menu', 'register_newpage' );
function register_newpage(){
    add_submenu_page( 'apollo_ga_plugin', 'Php Info', 'php Info', 'manage_options', 'apollo_ga_plugin/phpinfo.php', 'php_info');
}


function php_info(){

    ob_start();
    phpinfo(INFO_ALL & ~INFO_LICENSE & ~INFO_CREDITS);
    $info = ob_get_clean();
    $info = preg_replace("/^.*?\<body\>/is", "", $info);
    $info = preg_replace("/<\/body\>.*?$/is", "", $info);
    wp_enqueue_style("apollo_ga_plugin", plugins_url("/apollo-site-tools/css/phpinfo.css"), array(), false, "all");


    $current_user = wp_get_current_user();

    if (user_can($current_user, 'administrator')) {
       echo $info;
    } else {
        echo 'Feature only accessible by admins';
    }

}
function apollo_ga_form_input() {
?>
    <h1>Apollo Site Tools Settings</h1><hr><br>
    <?php echo 'Current PHP version: ' . phpversion();?>

    <form action='options.php' method='post'>

    <?php settings_fields('plugin_options_apollo'); ?>

    <?php do_settings_sections('apollo_ga_plugin'); ?>
<?php
}

// add the admin settings options and help text
add_action('admin_init', 'plugin_admin_init');
function plugin_admin_init() {
    register_setting( 'plugin_options_apollo', 'plugin_options_apollo', 'plugin_options_validate');

    add_settings_section('plugin_main', 'Add Tracking Codes', 'section_1_desc', 'apollo_ga_plugin');
    add_settings_section('plugin_main_email_send', 'Mail Sender Options', 'section_email_send_desc', 'apollo_ga_plugin');
    add_settings_section('plugin_main_3', 'Add Custom Javascript', 'section_3_desc', 'apollo_ga_plugin');
    // add_settings_section('plugin_main_4', 'Form Data', 'section_4_desc', 'apollo_ga_plugin');
    add_settings_section('plugin_main_2', 'Deployment Checklist', 'section_2_desc', 'apollo_ga_plugin');

    add_settings_field('plugin_text_string', 'Google Analytics', 'input_google_analytics', 'apollo_ga_plugin', 'plugin_main', array('plugin_text_string'));
    add_settings_field('plugin_gtag', 'Global Site Tag (gtag.js)', 'input_gtag', 'apollo_ga_plugin', 'plugin_main', array('plugin_gtag'));
    add_settings_field('plugin_google_webmaster', 'Google Webmaster Verification Code', 'input_google_webmaster', 'apollo_ga_plugin', 'plugin_main', array('plugin_google_webmaster'));
    add_settings_field('plugin_facebook_pixel', 'Facebook Pixel Code', 'input_facebook_pixel', 'apollo_ga_plugin', 'plugin_main', array('plugin_facebook_pixel'));
    add_settings_field('plugin_instagram', 'Instagram Username', 'input_instagram_id', 'apollo_ga_plugin', 'plugin_main', array('plugin_instagram'));
    add_settings_field('plugin_disable_admin', 'Disable Admin Tracking', 'plugin_setting_disable_admin', 'apollo_ga_plugin', 'plugin_main');
    add_settings_field('plugin_pass_change_notification', 'Disable Password Change Notification', 'plugin_setting_pass_change_notification', 'apollo_ga_plugin', 'plugin_main');
    add_settings_field('plugin_custom_js_header', 'Custom JS (Header)', 'input_custom_header_js', 'apollo_ga_plugin', 'plugin_main_3', array('plugin_custom_js_header'));
    add_settings_field('plugin_custom_js_footer', 'Custom JS (Footer)', 'input_custom_footer_js', 'apollo_ga_plugin', 'plugin_main_3', array('plugin_custom_js_footer'));


    add_settings_field('plugin_email_sender_name', 'Sender Name', 'input_email_sender_name', 'apollo_ga_plugin', 'plugin_main_email_send', array('plugin_email_sender_name'));
    add_settings_field('plugin_email_sender_mail', 'Sender Email', 'input_email_sender_mail', 'apollo_ga_plugin', 'plugin_main_email_send', array('plugin_email_sender_mail'));

    add_settings_field('plugin_google_analytics_check', 'Add Google Analytics', 'plugin_setting_google_analytics_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_google_webmaster_check', 'Add Google Webmaster', 'plugin_setting_google_webmaster_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_https_check', 'Enable https security (redirect from insecure)', 'plugin_setting_https_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_www_check', 'Decide on www or non-www and redirect', 'plugin_setting_www_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_admin_default_check', 'Verify site admin email is correct not default', 'plugin_setting_admin_default_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_site_email_check', 'Verify site From: email isn’t causing spam block', 'plugin_setting_site_email_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_admin_name_check', 'Change admin username to not be admin', 'plugin_setting_admin_name_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_admin_url_check', 'Change admin URL from wp-admin to something else for more security', 'plugin_setting_admin_url_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_compress_images_check', 'Optimize images', 'plugin_setting_compress_images_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_site_speed_check', 'Verify site speed test', 'plugin_setting_site_speed_check', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_email_log', 'Install Email Logs', 'plugin_setting_email_logs', 'apollo_ga_plugin', 'plugin_main_2');
    add_settings_field('plugin_media_replace', 'Install Media Replace', 'plugin_setting_media_replace', 'apollo_ga_plugin', 'plugin_main_2');
}


function section_1_desc() {
    echo '<hr><br><p>To remove any tracking code from the Web site, simple delete the corresponding tracking IDs below and hit Save Changes.</p>';
}

function section_email_send_desc(){
    echo '<hr><br><p>To change Wordpress default mail sender name and email.</p>';
}

function section_2_desc() {
    echo '<hr><br><p>Add custom JavaScript code to the header and/or footer of every page.  This can help with registering FB pixel events, for example.</p>';
}


function section_3_desc() {
    echo '<hr><br><p>Check off common deployment tasks as you complete them to make sure you have followed all best practices</p>';
}

function section_4_desc() {
    echo '<hr><br><p>View Contacts</p>';
    global $options;
    echo '<small>Name:'.$options["apollo_form_data"].'</small>';
    $json_options = json_encode($options);
     echo'<small>'.$json_options.'</small>';

}


function input_google_analytics($args) {
    global $options;

    echo "<input id='plugin_text_string' name='plugin_options_apollo[text_string]' size='40' type='text' value='{$options['text_string']}' placeholder='e.g. UA-10617632-1 or G-XB12DLB6Z0'><br>";
    echo "<small>Add Google analytics tracking code via analytics.js.  Only add the UA-10617632-1 or G-XB12DLB6Z0 part, the rest will be added automatically</small>";
}


function input_gtag($args) {
    global $options;

    echo "<input id='plugin_gtag' name='plugin_options_apollo[plugin_gtag]' size='40' type='text' value='{$options['plugin_gtag']}' placeholder='e.g. AW-971632851'><br>";
    echo "<small>Add Google Tag manager tag, for example for Google AdWords account.  Only add the tag itself, e.g. AW-971632851</small>";
}


function input_google_webmaster($args) {
    global $options;

    echo "<input id='plugin_google_webmaster' name='plugin_options_apollo[plugin_google_webmaster]' size='40' type='text' value='{$options['plugin_google_webmaster']}' placeholder='e.g. n3X_0t1nf94DqQaMF2dyczp4MNzHdq5JsCcRWMz7gVg'><br>";
    echo "<small>Add the Google Webmaster/Search Console verification code here.  It will be added as a meta tag to the head.</small>";
}


function input_facebook_pixel($args) {
    global $options;

    echo "<input id='plugin_facebook_pixel' name='plugin_options_apollo[plugin_facebook_pixel]' size='40' type='text' value='{$options['plugin_facebook_pixel']}' placeholder='e.g. 1511928129032582'><br>";
    echo "<small>Add the Facebook Pixel ID here, the rest of the code will be automatically added.</small>";
}

function input_instagram_id($args) {
    global $options;

    echo "<input id='plugin_instagram' name='plugin_options_apollo[plugin_instagram]' size='40' type='text' value='{$options['plugin_instagram']}' placeholder='e.g. kobebryant'><br>";
    echo "<small>Add an Instagram user name here to use the Instagram shortcode. The shortcode is:</small> <code>[apollo_instagram_feed]</code>";
}


function input_custom_header_js($args) {
    global $options;

    echo "<textarea id='plugin_custom_js_header' name='plugin_options_apollo[plugin_custom_js_header]' rows='5' cols='50' style='font-family: monospace;' placeholder='<script>
console.log(\"Hi\");
</script>'>{$options['plugin_custom_js_header']}</textarea><br>";
    echo "<small>Add custom snippets of JS code to the head section of the site, for example to track specific conversion actions</small>";
}


function input_custom_footer_js($args) {
    global $options;

    echo "<textarea id='plugin_custom_js_footer' name='plugin_options_apollo[plugin_custom_js_footer]' rows='5' cols='50' style='font-family: monospace;' placeholder='<script>
console.log(\"Bye\");
</script>'>{$options['plugin_custom_js_footer']}</textarea><br>";
    echo "<small>Add custom snippets of JS code to the footer part of the site, for example to add code that requires other code to be included before or should be added at the end to be non-blocking</small>";

    submit_button();
}

function input_email_sender_name($args) {
    global $options;

    echo "<input id='plugin_email_sender_name' name='plugin_options_apollo[plugin_email_sender_name]' size='40' type='text' value='{$options['plugin_email_sender_name']}' placeholder='Sender Name'><br>";
}

function input_email_sender_mail($args) {
    global $options;

    echo "<input id='plugin_email_sender_mail' name='plugin_options_apollo[plugin_email_sender_mail]' size='40' type='text' value='{$options['plugin_email_sender_mail']}' placeholder='info@example.com'><br>";

    submit_button();
}

// Hooking up our functions to WordPress filters
add_filter( 'wp_mail_from', 'wpb_sender_email' );
add_filter( 'wp_mail_from_name', 'wpb_sender_name' );

add_filter('plugin_action_links_apollo-site-tools/wp_ga_main.php', 'wp_settings_link');
function wp_settings_link($links){

    $mylinks = array(
        '<a href="' . admin_url( 'options-general.php?page=apollo_ga_plugin' ) . '">Settings</a>',
    );
    return array_merge( $links, $mylinks );

}

// Function to change email address
function wpb_sender_email( $original_email_address ) {
    global $options;

    if($options["plugin_email_sender_mail"]){
        return sanitize_email($options["plugin_email_sender_mail"]);
    }

    return $original_email_address;
}

// Function to change sender name
function wpb_sender_name( $original_email_from ) {
    global $options;

    if($options["plugin_email_sender_name"]){
        return $options["plugin_email_sender_name"];
    }

    return $original_email_from;
}

function plugin_setting_disable_admin(){
    global $options;

    if ($options['disable_admin'] == 0) {
        echo "<input id='plugin_disable_admin' name='plugin_options_apollo[disable_admin]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_disable_admin' name='plugin_options_apollo[disable_admin]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
    echo "<small>When admin tracking is disabled, GA and FB pixel code will not be included in the page source to exclude admins from being tracked.</small>";

}

function plugin_setting_pass_change_notification(){
    global $options;

    if ($options['pass_change_notification'] == 0) {
        echo "<input id='plugin_pass_change_notification' name='plugin_options_apollo[pass_change_notification]' type='checkbox' value='1' placeholder='Disable Password Change Notification'><br>";
    } else {
        echo "<input id='plugin_pass_change_notification' name='plugin_options_apollo[pass_change_notification]' type='checkbox' value='1' placeholder='Disable Password Change Notification' checked='checked'><br>";
    }
    echo "<small>When password change notification is disabled, you won't receive any email when users change their password.</small>";
    submit_button();
}


function plugin_setting_google_analytics_check() {
    global $options;

    if ($options['plugin_google_analytics_check'] == 0) {
        echo "<input id='plugin_google_analytics_check' name='plugin_options_apollo[plugin_google_analytics_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_google_analytics_check' name='plugin_options_apollo[plugin_google_analytics_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_google_webmaster_check() {
    global $options;

    if ($options['plugin_google_webmaster_check'] == 0) {
        echo "<input id='plugin_google_webmaster_check' name='plugin_options_apollo[plugin_google_webmaster_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_google_webmaster_check' name='plugin_options_apollo[plugin_google_webmaster_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_https_check() {
    global $options;

    if ($options['plugin_https_check'] == 0) {
        echo "<input id='plugin_https_check' name='plugin_options_apollo[plugin_https_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_https_check' name='plugin_options_apollo[plugin_https_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_www_check() {
    global $options;

    if ($options['plugin_www_check'] == 0) {
        echo "<input id='plugin_www_check' name='plugin_options_apollo[plugin_www_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_www_check' name='plugin_options_apollo[plugin_www_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_admin_default_check() {
    global $options;

    if ($options['plugin_admin_default_check'] == 0) {
        echo "<input id='plugin_admin_default_check' name='plugin_options_apollo[plugin_admin_default_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_admin_default_check' name='plugin_options_apollo[plugin_admin_default_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_site_email_check() {
    global $options;

    if ($options['plugin_site_email_check'] == 0) {
        echo "<input id='plugin_site_email_check' name='plugin_options_apollo[plugin_site_email_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_site_email_check' name='plugin_options_apollo[plugin_site_email_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_admin_name_check() {
    global $options;

    if ($options['plugin_admin_name_check'] == 0) {
        echo "<input id='plugin_admin_name_check' name='plugin_options_apollo[plugin_admin_name_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_admin_name_check' name='plugin_options_apollo[plugin_admin_name_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_admin_url_check() {
    global $options;

    if($options['plugin_admin_url_check'] == 0){
        echo "<input id='plugin_admin_url_check' name='plugin_options_apollo[plugin_admin_url_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_admin_url_check' name='plugin_options_apollo[plugin_admin_url_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_compress_images_check() {
    global $options;

    if($options['plugin_compress_images_check'] == 0) {
        echo "<input id='plugin_compress_images_check' name='plugin_options_apollo[plugin_compress_images_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_compress_images_check' name='plugin_options_apollo[plugin_compress_images_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }
}


function plugin_setting_site_speed_check() {
    global $options;

    if ($options['plugin_site_speed_check'] == 0) {
        echo "<input id='plugin_site_speed_check' name='plugin_options_apollo[plugin_site_speed_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking'><br>";
    } else {
        echo "<input id='plugin_site_speed_check' name='plugin_options_apollo[plugin_site_speed_check]' type='checkbox' value='1' placeholder='Disable Admin Tracking' checked='checked'><br>";
    }

}

function plugin_setting_email_logs() {
    global $options;

    if ($options['plugin_email_log'] == 0) {
        echo "<input id='plugin_email_log' name='plugin_options_apollo[plugin_email_log]' type='checkbox' value='1' placeholder='Install Email Logs'><br>";
    } else {
        echo "<input id='plugin_email_log' name='plugin_options_apollo[plugin_email_log]' type='checkbox' value='1' placeholder='Install Email Logs' checked='checked'><br>";
    }
    echo "<small><a href='https://wordpress.org/plugins/email-log/'>https://wordpress.org/plugins/email-log/</a></small>";

}


function plugin_setting_media_replace() {
    global $options;

    if ($options['plugin_media_replace'] == 0) {
        echo "<input id='plugin_media_replace' name='plugin_options_apollo[plugin_media_replace]' type='checkbox' value='1' placeholder='Install Media Replace'><br>";
    } else {
        echo "<input id='plugin_media_replace' name='plugin_options_apollo[plugin_media_replace]' type='checkbox' value='1' placeholder='Install Media Replace' checked='checked'><br>";
    }
    echo "<small><a href='https://wordpress.org/plugins/enable-media-replace/'>https://wordpress.org/plugins/enable-media-replace/</a></small>";

    submit_button();
}



function plugin_options_validate($input) {
    $options = get_option('plugin_options_apollo');

    $options['text_string'] = trim($input['text_string']);
    $options['plugin_google_webmaster'] = trim($input['plugin_google_webmaster']);
    $options['disable_admin'] = trim($input['disable_admin']);
    $options['pass_change_notification'] = trim($input['pass_change_notification']);

    $options['plugin_google_analytics_check'] = trim($input['plugin_google_analytics_check']);
    $options['plugin_google_webmaster_check'] = trim($input['plugin_google_webmaster_check']);
    $options['plugin_facebook_pixel'] = trim($input['plugin_facebook_pixel']);
    $options['plugin_instagram'] = trim($input['plugin_instagram']);
    $options['plugin_custom_js_header'] = trim($input['plugin_custom_js_header']);
    $options['plugin_custom_js_footer'] = trim($input['plugin_custom_js_footer']);
    $options['plugin_gtag'] = trim($input['plugin_gtag']);
    $options['plugin_email_sender_name'] = trim($input['plugin_email_sender_name']);
    $options['plugin_email_sender_mail'] = trim($input['plugin_email_sender_mail']);

    $options['plugin_https_check'] = trim($input['plugin_https_check']);
    $options['plugin_www_check'] = trim($input['plugin_www_check']);
    $options['plugin_admin_default_check'] = trim($input['plugin_admin_default_check']);
    $options['plugin_site_email_check'] = trim($input['plugin_site_email_check']);
    $options['plugin_site_email_check'] = trim($input['plugin_site_email_check']);
    $options['plugin_admin_name_check'] = trim($input['plugin_admin_name_check']);
    $options['plugin_admin_url_check'] = trim($input['plugin_admin_url_check']);
    $options['plugin_compress_images_check'] = trim($input['plugin_compress_images_check']);
    $options['plugin_site_speed_check'] = trim($input['plugin_site_speed_check']);
    $options['plugin_email_log'] = trim($input['plugin_email_log']);
    $options['plugin_media_replace'] = trim($input['plugin_media_replace']);

    return $options;
}
