<?php
/**
 * Plugin Name: Export Comment Author Emails
 * Description: Create and export email list from existing comments on your website. Build an extra email list
 * Plugin URI: http://onetarek.com/my-wordpress-plugins/export-comment-author-emails/
 * Author: oneTarek
 * Author URI: http://onetarek.com
 * Version: 1.0.0
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

//Don't allow direct access
if( ! defined( 'ABSPATH' ) ) exit;

define( 'ECAE_VERSION', '1.0.0' );
define( 'ECAE_PLUGIN_FILE', __FILE__ );
define( 'ECAE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // Plugin Directory
define( 'ECAE_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // with forward slash (/). Plugin URL (for http requests).
define( 'ECAE_PLUGIN_PAGE_SLUG', 'export_comment_author_emails');
if( ! class_exists( 'Export_Comment_Author_Emails' ) ) :

/**
 * Main Export_Comment_Author_Emails Class.
 *
 * @since 1.0.0
 */
class Export_Comment_Author_Emails{
	
	public function __construct(){
		add_action( 'plugins_loaded', array( $this , 'load_textdomain' ) );
		add_action('admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_export_comment_author_emails', array( $this, 'export_comment_author_emails_ajax') );
	}
	
	public function admin_menu(){

		add_management_page( __('Export Comment Author Emails', 'ecae'), __('Export Comment Author Emails', 'ecae'), 'manage_options', ECAE_PLUGIN_PAGE_SLUG, array( $this, 'pluign_page') );

    }

	/*
	 * Plugin main page
	 * @since 1.0.0
	 */
	public function pluign_page(){
		?>
		<div class="wrap">
			<div id="icon-tools-pages" class="icon32"></div><h1><?php _e('Export Comment Author Emails', 'ecae') ?></h1>
			<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" method="post">
				<?php wp_nonce_field( 'export_comment_author_emails', 'export_comment_author_emails_nonce' ); ?>
				<input type="hidden" name="action" value="export_comment_author_emails">
			
				<?php 
					$email_list_text = $this->get_comment_email_list_for_display();
					if( $email_list_text == "" ){ $email_list_text = __( 'No Email Found', 'ecae' ); }
					$count =  $this->get_comment_authors_data_count();
				?>
				<table class="widefat" style="width:700px;margin-top:30px;">
					<thead>
						<tr>
						<th width="150">&nbsp;</th>
						<th>&nbsp;</th>
						</tr>
					</thead>
					<tr>
						<td><label><strong><?php echo  __( 'Found Emails', 'ecae' ) ?></strong></label></td>
						<td>
							<textarea style="width:100%;height:300px;" readonly><?php echo esc_textarea( $email_list_text ) ?></textarea><br>
							<?php echo  sprintf( __( '%d email address has been found. Only maximum 50 emails are being shown here', 'ecae' ), $count ); ?>
						</td>
					</tr>
					<tr>
						<td><label><strong><?php echo  __( 'Fields to export', 'ecae' ) ?></strong></label></td>
						<td>
							<input type="checkbox" name="export_field_name" id="export_field_name" value="1"><label for="export_field_name"><?php echo  __( 'Name ( Comment author name )', 'ecae' ) ?></lable><br>
							<input type="checkbox" name="export_field_email" id="export_field_email" value="1" checked><label for="export_field_email"><?php echo  __( 'Email ( Comment author email )', 'ecae' ) ?></lable><br>
							<input type="checkbox" name="export_field_url" id="export_field_url" value="1"><label for="export_field_url"><?php echo  __( 'Website ( Comment author url )', 'ecae' ) ?></lable><br>
							<?php echo  __( 'Select fields you want to export', 'ecae' ) ?>
						</td>
					</tr>
					<tr>
						<td><label><strong><?php echo  __( 'File type', 'ecae' ) ?></strong></label></td>
						<td>

							<input type="radio" name="export_file_type" id="export_file_type_csv" value="csv" checked ><label for="export_file_type_csv"><?php echo  __( 'CSV', 'ecae' ) ?></lable><br>
							<input type="radio" name="export_file_type" id="export_file_type_txt" value="txt"><label for="export_file_type_txt"><?php echo  __( 'Text', 'ecae' ) ?></lable><br>
							<?php echo  __( 'Select type of the export file', 'ecae' ) ?>
						</td>
					</tr>
					<tr>
						<td><label><strong><?php echo  __( 'Delimiter', 'ecae' ) ?></strong></label></td>
						<td>

							<input type="text" name="export_delimiter" id="export_delimiter" value="," size="5" /><br>
							<?php echo  __( 'Delimiter text. Fields will be seperated by this text', 'ecae' ) ?>
						</td>
					</tr>
					<tr>
						<td><label><strong><?php echo  __( 'Header Row', 'ecae' ) ?></strong></label></td>
						<td>
							<input type="checkbox" name="export_header_row" id="export_header_row" value="1"><label for="export_header_row"><?php echo  __( 'Include header row. First row will be the name of fields', 'ecae' ) ?></lable><br>
							
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input type="submit" class="button-primary" id="ecea_download_btn" value="<?php echo  __( 'Download', 'ecae' ) ?>" /><br><br><br><br><br>
							
						</td>
					</tr>
				</table>
				</form>	
		</div>
		

	<?php 
	}//end function pluign_page

	/**
	 * Loads the plugin language files.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain(){
		load_plugin_textdomain( 'ecae', false, ECAE_PLUGIN_DIR."languages/" );
	}

	public function get_comment_email_list_for_display(){
		$authors = $this->get_comment_authors_data( 50 );
		$list_text = "";
		foreach( $authors as $a )
		{
			$list_text.=$a[1]."\n";
		}
		return $list_text;
	}
	public function get_comment_authors_data( $limit = 0 ){
	  global $wpdb; 
	  $limit = intval($limit);
	  $sql = 
	  	"SELECT comment_author, comment_author_email, comment_author_url 
	  	FROM ".$wpdb->prefix ."comments 
	  	WHERE comment_approved = 1 AND comment_author_email !=''
	  	GROUP BY comment_author_email
	    ORDER BY comment_author_email ASC";
	  if( $limit )
	  {
	  	$sql = $sql." LIMIT ".$limit;
	  }
	  //echo $sql;
	  $results = $wpdb->get_results( $sql, 'ARRAY_N' );
	    
	  return $results;
	}

	public function get_comment_authors_data_count(){
	  global $wpdb; 
	  $sql = 
	  	"SELECT count( distinct comment_author_email ) 
	  	FROM ".$wpdb->prefix ."comments 
	  	WHERE comment_approved = 1 AND comment_author_email !=''";

	  $count = $wpdb->get_var( $sql );
	  return $count;
	}

	public function export_comment_author_emails_ajax(){
		
		check_admin_referer( 'export_comment_author_emails', 'export_comment_author_emails_nonce' );

		set_time_limit(0);

		$include_name = isset( $_POST['export_field_name'] ) ? 1 : 0;
		$include_email = isset( $_POST['export_field_email'] ) ? 1 : 0;
		$include_url = isset( $_POST['export_field_url'] ) ? 1 : 0;
		$set_header_row = isset( $_POST['export_header_row'] ) ? 1 : 0;

		$delimiter = isset( $_POST['export_delimiter'] ) ? trim( $_POST['export_delimiter'] ) : ',';
		if( $delimiter == "" )
		{ 
			$delimiter = ',';
		}
		$file_type = isset( $_POST['export_file_type'] ) ? trim( $_POST['export_file_type'] ) : 'csv';
		if( $file_type != 'csv' && $file_type != 'txt' )
		{
			$file_type = 'csv';
		}
		$Content_Type = ( $file_type == 'csv' ) ? 'text/csv' : 'text/plain';
		$file_ext = ( $file_type == 'csv' ) ? '.csv' : '.txt';

		header('Content-Type: '.$Content_Type.'; charset=' . get_option( 'blog_charset' ));
	    header('Content-Disposition: attachment; filename=comments-author-emails.' . $file_ext);
	    //Disable caching
	    header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
	    header('Pragma: no-cache'); // HTTP 1.0
	    header('Expires: 0'); // Proxies    
	    
	    $authors = $this->get_comment_authors_data(); 
	    
	    //print header row
	    if($set_header_row )
	    {
	    	$field_names = array();
	    	if( $include_name ){ $field_names[] = 'Name'; }
	    	if( $include_email ){ $field_names[] = 'Email'; }
	    	if( $include_url ){ $field_names[] = 'Website'; }
	    	echo implode( $delimiter, $field_names );
	    	echo "\r\n";
	    }

	    foreach( $authors as $a )
	    {
	    	$row = array();
	    	if( $include_name ){ $row[] = $a[0]; }
	    	if( $include_email ){ $row[] = $a[1];; }
	    	if( $include_url ){ $row[] = $a[2];; }
	    	echo implode( $delimiter, $row );
	    	echo "\r\n";

	    }
	    
	    exit();

		
	}



}//end class

$export_comment_author_emails_obj = new Export_Comment_Author_Emails();

endif;