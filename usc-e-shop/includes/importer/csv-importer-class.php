<?php
/**
 * Welcart CSV Importer
 *
 * @package Welcart
 */

// phpcs:disable WordPress.WP.AlternativeFunctions
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Welcart CSV Importer class
 */
class Welcart_CSV_Importer {

	/**
	 * Instance
	 *
	 * @var Welcart_CSV_Importer
	 */
	private static $instance = null;

	/**
	 * Import session prefix
	 *
	 * @var string
	 */
	private $import_transient_prefix = 'welcart_csv_import_';

	/**
	 * Page title
	 *
	 * @var string
	 */
	private $page_title;

	/**
	 * Data columns
	 *
	 * @var array
	 */
	private $table_columns = array();

	/**
	 * Meta fields
	 *
	 * @var array
	 */
	private $meta_fields = array();

	/**
	 * Import type
	 *
	 * @var array
	 */
	private $type;

	/**
	 * Importer construct
	 *
	 * @param string $type Import type.
	 */
	public function __construct( $type ) {

		$this->type = $type;
		if ( empty( $this->type ) ) {
			add_action( 'admin_post_welcart_csv_upload', array( $this, 'handle_error' ) );
			add_action( 'admin_post_welcart_csv_mapping', array( $this, 'handle_error' ) );
		} else {
			add_action( 'admin_post_welcart_csv_upload', array( $this, 'handle_csv_upload' ) );
			add_action( 'admin_post_welcart_csv_mapping', array( $this, 'handle_csv_mapping' ) );
			add_action( 'admin_post_download_csv_error_log', array( $this, 'download_error_log' ) );
			add_action( 'wp_ajax_process_csv_chunk', array( $this, 'process_csv_chunk' ) );

			$this->register_csv_import_handling_page();

		}
	}

	/**
	 * Get instance
	 *
	 * @param string $type Import type.
	 * @return Welcart_CSV_Importer
	 */
	public static function get_instance( $type ) {
		if ( null === self::$instance ) {
			self::$instance = new Welcart_CSV_Importer( $type );
		}
		return self::$instance;
	}

	/**
	 * Set condition data
	 *
	 * @param array $condition Condition data.
	 */
	public function set_condition( $condition ) {
		$this->page_title    = isset( $condition['page_title'] ) ? $condition['page_title'] : __( 'Data Import', 'usces' );
		$this->table_columns = isset( $condition['columns'] ) ? $condition['columns'] : array();
		$this->meta_fields   = isset( $condition['meta_fields'] ) ? $condition['meta_fields'] : array();
	}

	/**
	 * Add handling page
	 */
	public function register_csv_import_handling_page() {
		add_submenu_page(
			'wel-hidden-page', // Specify a fake parent page to hide from the menu.
			__( 'Welcart CSV Import', 'usces' ),
			__( 'Welcart CSV Import', 'usces' ),
			'manage_options',
			'welcart-csv-importer',
			array( $this, 'handle_import_page_action' )
		);
	}

	/**
	 * Switch page
	 */
	public function handle_import_page_action() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->go_to_error_page( __( 'Permission error.', 'usces' ) . ' (#073)' );
		}
		check_admin_referer( 'welcart_csv_import', 'wc_nonce' );

		$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
		if ( $this->type !== $type ) {
			$this->render_error_page( __( 'Type does not match.', 'usces' ) . ' (#001)' );
		}

		$step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : 'upload';

		$import_id = isset( $_GET['import_id'] ) ? sanitize_text_field( wp_unslash( $_GET['import_id'] ) ) : '';
		if ( ! $import_id && 'upload' !== $step ) {
			$this->render_error_page( __( 'Invalid access.', 'usces' ) . ' (#050)' );
		}

		switch ( $step ) {
			case 'upload':
				$this->render_upload_form( $type );
				break;
			case 'preview':
				$encoding = isset( $_GET['encoding'] ) ? sanitize_text_field( wp_unslash( $_GET['encoding'] ) ) : 'UTF-8';
				$this->render_preview_and_mapping( $type, $import_id, $encoding );
				break;
			case 'import':
				$this->render_import_progress( $type, $import_id );
				break;
			case 'result':
				$this->render_result_page( $type, $import_id );
				break;
			case 'error':
				$this->render_error_page();
				break;
			default:
				$this->render_error_page( __( 'Invalid step. (#002)', 'usces' ) );
		}
	}

	/**
	 * Upload form
	 *
	 * @param string $type Import type.
	 */
	private function render_upload_form( $type ) {

		if ( $this->type !== $type ) {
			$this->render_error_page( __( 'Type does not match.', 'usces' ) . ' (#005)' );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<p><?php echo esc_html__( 'Welcart CSV Importer', 'usces' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'welcart_csv_import', 'wc_nonce' ); ?>
				<input type="hidden" name="action" value="welcart_csv_upload">
				<input type="hidden" name="type" value="<?php echo esc_attr( $this->type ); ?>">
				<table class="form-table">
					<tr>
						<th><label for="csv_file"><?php echo esc_html__( 'CSV or ZIP File', 'usces' ); ?></label></th>
						<td>
							<input type="file" name="csv_file" id="csv_file" accept=".csv,.zip" required>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Upload', 'usces' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * File upload process
	 */
	public function handle_csv_upload() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->go_to_error_page( __( 'Permission error.', 'usces' ) . ' (#072)' );
		}
		check_admin_referer( 'welcart_csv_import', 'wc_nonce' );

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		if ( $this->type !== $type ) {
			$this->go_to_error_page( __( 'Type does not match.', 'usces' ) . ' (#007)' );
		}

		if ( false !== $this->file_upload_error() ) {
			$message = $this->file_upload_error();
			$this->go_to_error_page( $message . __( ' (#008)', 'usces' ) );
		}

		$file_name_org = isset( $_FILES['csv_file']['name'] ) ? sanitize_text_field( wp_unslash( $_FILES['csv_file']['name'] ) ) : '';
		if ( empty( $file_name_org ) ) {
			$this->go_to_error_page( __( 'Failed to get file name.', 'usces' ) . ' (#009)' );
		}
		$file_name_org  = sanitize_file_name( basename( urldecode( $file_name_org ) ) );
		$temp_file_name = wp_unique_filename( WEL_IMPORT_UPLOADS_DIR, $file_name_org );
		$uploaded_file  = WEL_IMPORT_UPLOADS_DIR . '/' . $temp_file_name;

		$allowed_types = array( 'text/csv', 'application/zip' );
		$file_type     = wp_check_filetype_and_ext( $uploaded_file, $file_name_org );
		if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
			$this->go_to_error_page( __( 'Please specify a CSV or ZIP file.', 'usces' ) . ' (#009)' );
		}

		// Delete all CSV files.
		$files = glob( WEL_IMPORT_UPLOADS_DIR . '/*.csv' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}

		$tmp_name_org = isset( $_FILES['csv_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['csv_file']['tmp_name'] ) ) : '';
		if ( empty( $tmp_name_org ) || ! move_uploaded_file( $tmp_name_org, $uploaded_file ) ) {
			$this->go_to_error_page( __( 'Failed to save file.', 'usces' ) . ' (#009)' );
		}

		$ext = strtolower( pathinfo( $file_name_org, PATHINFO_EXTENSION ) );

		if ( 'zip' === $ext ) {

			// Extract CSV from ZIP.
			$zip = new ZipArchive();

			if ( true === $zip->open( $uploaded_file ) ) {

				if ( $zip->numFiles > 2 ) {
					$this->go_to_error_page( __( 'Too many files in ZIP.', 'usces' ) . ' (#030)' );
				}

				$csv_found = false;

				for ( $i = 0; $i < $zip->numFiles; $i++ ) {

					$entry = $zip->getNameIndex( $i );
					if ( 'csv' === strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ) ) {

						$csv_file_name  = basename( $entry );
						$temp_file_name = wp_unique_filename( WEL_IMPORT_UPLOADS_DIR, $csv_file_name );
						$temp_file      = WEL_IMPORT_UPLOADS_DIR . '/' . $temp_file_name;
						if ( ! copy( "zip://{$uploaded_file}#{$entry}", $temp_file ) ) {
							$this->go_to_error_page( __( 'Failed to extract CSV file from ZIP.', 'usces' ) . ' (#015)' );
						}
						$csv_found = true;
						break;

					} else {
						$this->go_to_error_page( __( 'CSV file not found in ZIP.', 'usces' ) . ' (#031)' );
					}
				}
				$zip->close();
				wp_delete_file( $uploaded_file );

				if ( ! $csv_found ) {
					$this->go_to_error_page( __( 'CSV file not found in ZIP.', 'usces' ) . ' (#012)' );
				}

				$csv_name = $csv_file_name;

			} else {
				$this->go_to_error_page( __( 'Unable to open ZIP file.', 'usces' ) . ' (#013)' );
			}
		} else {

			$temp_file = $uploaded_file;
			$csv_name  = $file_name_org;
		}

		$import_id = uniqid();

		$data = array(
			'temp_file' => $temp_file_name,
			'csv_name'  => $csv_name,
		);
		set_transient( $this->import_transient_prefix . $import_id, $data, 30 * MINUTE_IN_SECONDS );

		$redirect_url = add_query_arg(
			array(
				'page'      => 'welcart-csv-importer',
				'step'      => 'preview',
				'type'      => $this->type,
				'import_id' => $import_id,
				'wc_nonce'  => wp_create_nonce( 'welcart_csv_import' ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Preview & Mapping page
	 *
	 * @param string $type Import type.
	 * @param string $import_id Import ID.
	 * @param string $encoding Character encoding.
	 */
	private function render_preview_and_mapping( $type, $import_id, $encoding ) {

		$data = get_transient( $this->import_transient_prefix . $import_id );
		if ( ! $data ) {
			$this->render_error_page( __( 'Import session not found.', 'usces' ) . ' (#051)' );
		}

		$temp_file_name = sanitize_file_name( basename( sanitize_text_field( wp_unslash( $data['temp_file'] ) ) ) );
		$temp_file      = WEL_IMPORT_UPLOADS_DIR . '/' . $temp_file_name;
		$csv_name       = sanitize_text_field( wp_unslash( $data['csv_name'] ) );

		if ( ! file_exists( $temp_file ) ) {
			$this->render_error_page( __( 'Temporary file not found.', 'usces' ) . ' ' . __( 'Please start over from the beginning.', 'usces' ) . ' (#017)' );
		}

		// To handle large files with low memory, do not use WP_Filesystem.
		$handle = fopen( $temp_file, 'r' );
		if ( false === $handle ) {
			$this->render_error_page( __( 'Failed to read file.', 'usces' ) . ' ' . __( 'Please start over from the beginning.', 'usces' ) . ' (#018)' );
		}

		$sample   = fread( $handle, 1000 );
		$enc_type = mb_detect_encoding( $sample, array( 'SJIS-win', 'SJIS', 'UTF-8', 'EUC-JP' ), true );
		rewind( $handle );

		if ( 'SJIS' === $encoding && 'UTF-8' !== $enc_type ) {
			stream_filter_append( $handle, 'convert.iconv.sjis-win/utf-8' );
		}

		$rows     = array();
		$max_rows = 6; // Retrieve maximum 6 rows for preview.
		$i        = 0;

		while ( $i < $max_rows ) {
			$data = fgetcsv( $handle );
			if ( false === $data ) {
				// End of file reached.
				break;
			}
			// Exclude empty rows (allowing '0').
			if ( ! empty( array_filter( $data, 'strlen' ) ) ) {
				// Remove BOM from first field in first row.
				if ( 0 === $i && isset( $data[0] ) && 0 === strncmp( $data[0], "\xEF\xBB\xBF", 3 ) ) {
					$data[0] = substr( $data[0], 3 );
				}
				$rows[] = $data;
				++$i;
			}
		}
		fclose( $handle );

		if ( 'SJIS' === $encoding && 'UTF-8' === $enc_type ) {
			$rows = array();
		}
		$num_columns  = ! empty( $rows ) ? count( $rows[0] ) : 0;
		$num_all_rows = $this->count_csv_lines( $temp_file, $encoding );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<p><?php echo esc_html__( 'CSV Preview & Mapping', 'usces' ); ?></p>

			<div class="guide-area">
				<p><?php echo esc_html__( 'Preview the CSV file and perform data mapping.', 'usces' ); ?></p>
				<p><?php echo esc_html__( 'If you wish to re-upload the CSV file, you can do so using the "Re-upload" option.', 'usces' ); ?></p>
				<?php do_action( 'welcart_csv_preview_top_guide' ); ?>
			</div>

			<div id="toggle-upload-container">
				<button type="button" id="toggle-upload"><?php echo esc_html__( 'Re-upload', 'usces' ); ?></button>
			</div>

			<div id="upload-container" style="display:none;">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="margin-bottom:20px;padding:10px;border:1px solid #ccc;background:#f1f1f1;">
				<?php wp_nonce_field( 'welcart_csv_import', 'wc_nonce' ); ?>
				<input type="hidden" name="action" value="welcart_csv_upload">
				<input type="hidden" name="type" value="<?php echo esc_attr( $this->type ); ?>">
				<input type="hidden" name="import_id" value="<?php echo esc_attr( $import_id ); ?>">
				<div class="form-area" id="reupload-container" >
					<p><?php echo esc_html__( 'CSV or ZIP File', 'usces' ); ?>(<?php echo esc_html__( 'Re-upload', 'usces' ); ?>)</p>
					<p><input type="file" name="csv_file" id="csv_file" accept=".csv,.zip" required></p>
				<?php submit_button( __( 'Upload', 'usces' ) ); ?>
				</div>
			</form>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'welcart_csv_import', 'wc_nonce' ); ?>
				<input type="hidden" name="action" value="welcart_csv_mapping">
				<input type="hidden" name="csv_encoding" value="<?php echo esc_attr( $encoding ); ?>">
				<input type="hidden" name="type" value="<?php echo esc_attr( $this->type ); ?>">
				<input type="hidden" name="import_id" value="<?php echo esc_attr( $import_id ); ?>">

				<div class="form-area" id="setting-container">
					<div class="field-area">
						<p><span><?php echo esc_html__( 'Character Encoding', 'usces' ); ?></span>
						
							<select id="csv_encoding_select" name="csv_encoding_select">
								<option value="UTF-8" <?php selected( $encoding, 'UTF-8' ); ?>>UTF-8</option>
								<option value="SJIS" <?php selected( $encoding, 'SJIS' ); ?>>Shift-JIS</option>
							</select>
						</p>
						<p class="field-exp"><?php echo esc_html__( '※ If characters are garbled, please select the appropriate encoding.', 'usces' ); ?></p>
					</div>
					<div class="field-area">
						<p><span><?php echo esc_html__( 'Header Row', 'usces' ); ?></span>
						
							<label><input type="radio" name="has_header" value="1" checked><?php echo esc_html__( 'Field Names', 'usces' ); ?></label>
							<label><input type="radio" name="has_header" value="0"><?php echo esc_html__( 'Data', 'usces' ); ?></label>
						</p>
						<p class="field-exp"><?php echo esc_html__( '※ If the first row contains field names, it will not be processed.', 'usces' ); ?></p>
					</div>

					<?php do_action( 'welcart_csv_preview_option' ); ?>

				</div>

				<h2><?php echo esc_html__( 'CSV Preview', 'usces' ); ?></h2>
				<div id="csv-preview-container" style="overflow-x:auto;">
					<p class="csvfile-name"><?php echo esc_html__( 'File Name: ', 'usces' ) . esc_html( $csv_name ); ?></p>
					<p class="csvfile-name"><?php echo esc_html__( 'Valid Data Count: ', 'usces' ); ?><span id="all_data_num"></span></p>
					<table class="widefat">
						<thead id="csv-preview-thead"></thead>
						<tbody id="csv-preview-tbody"></tbody>
					</table>
				</div>

				<h2><?php echo esc_html__( 'Data Mapping', 'usces' ); ?></h2>
				<div class="guide-area">
					<p><?php echo esc_html__( 'The first column is required. At least one additional column must be selected.', 'usces' ); ?></p>
					<?php do_action( 'welcart_csv_mapping_top_guide' ); ?>
				</div>

				<div class="form-area" id="setting-container">
					<?php do_action( 'welcart_csv_mapping_option' ); ?>
				</div>

				<div id="mapping-container">
					<table class="form-table" id="main-column-mapping">
						<?php foreach ( $this->table_columns as $col_key => $col_label ) : ?>
						<tr>
							<th><label for="mapping_<?php echo esc_attr( $col_key ); ?>"><?php echo esc_html( $col_label ); ?></label></th>
							<td>
								<select name="mapping[<?php echo esc_attr( $col_key ); ?>]" id="mapping_<?php echo esc_attr( $col_key ); ?>" data-default="<?php echo esc_attr( $col_label ); ?>">
									<!-- Generated by JS -->
								</select>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>
					<?php if ( ! empty( $this->meta_fields ) ) : ?>
						<?php echo esc_html__( 'Meta Fields', 'usces' ); ?>
					<table class="form-table" id="meta-column-mapping">
						<?php foreach ( $this->meta_fields as $col_key => $col_label ) : ?>
						<tr>
							<th><label for="mapping_<?php echo esc_attr( $col_key ); ?>"><?php echo esc_html( $col_label ); ?></label></th>
							<td>
								<select name="mapping[<?php echo esc_attr( $col_key ); ?>]" id="mapping_<?php echo esc_attr( $col_key ); ?>" data-default="<?php echo esc_attr( $col_label ); ?>">
									<!-- Generated by JS -->
								</select>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>
					<?php endif; ?>

					<?php do_action( 'welcart_csv_after_mapping_table' ); ?>

				<div id="submit-container">
					<p class="submit"><input type="submit" name="submit" id="import_submit" class="button button-primary" value="<?php echo esc_attr__( 'Start Update', 'usces' ); ?>" ></p>
				</div>
			</form>
		</div>
		<script>
		(function($){
			var previewData = <?php echo wp_json_encode( $rows ); ?>;
			var numColumns  = <?php echo absint( $num_columns ); ?>;
			var numAllData  = <?php echo absint( $num_all_rows ); ?>;

			<?php do_action( 'welcart_csv_preview_pre_js' ); ?>

			function escapeHtml(txt){
				return $('<div/>').text(txt).html();
			}

			function updatePreview(){

				if(previewData.length===0){
					$('#csv-preview-container').html('<?php echo esc_js( __( 'Unable to load data', 'usces' ) ); ?>');
					$('#mapping-container').html('');
					$('#import_submit').prop('disabled', true);
					return;
				}
				var hasHeader = $('input[name="has_header"]:checked').val();
				var headerHTML = '';
				var startIndex = 0;
				if( hasHeader==='1' && previewData.length>0 ) {
					$.each(previewData[0], function(i, cell){
						var txt = cell ? cell : '<?php echo esc_js( __( 'Column', 'usces' ) ); ?>'+(i+1);
						headerHTML += '<th>' + escapeHtml(txt) + '</th>';
					});
					startIndex = 1;
					$('#all_data_num').html(numAllData-1);
				} else {
					for(var i = 0; i < numColumns; i++){
						headerHTML += '<th><?php echo esc_js( __( 'Column', 'usces' ) ); ?>'+(i+1)+'</th>';
					}
					$('#all_data_num').html(numAllData);
				}
				$('#csv-preview-thead').html('<tr>' + headerHTML + '</tr>');

				var bodyHTML = '';
				for(var r = startIndex; r < Math.min(previewData.length, startIndex + 5); r++){
					bodyHTML += '<tr>';
					$.each(previewData[r], function(j, cell){
						bodyHTML += '<td>' + escapeHtml(cell) + '</td>';
					});
					bodyHTML += '</tr>';
				}
				$('#csv-preview-tbody').html(bodyHTML);

				$('#mapping-container select').each(function(){
					var defaultColLabel = $(this).data('default'); 
					var opts = '<option value=""><?php echo esc_js( __( '(Not Set)', 'usces' ) ); ?></option>';
					for(var c = 0; c < numColumns; c++){
						var cellText = '';
						if(hasHeader==='1' && previewData.length > 0){
							cellText = previewData[0][c] ? previewData[0][c] : '<?php echo esc_js( __( 'Column', 'usces' ) ); ?>' + (c+1);
						} else {
							cellText = '<?php echo esc_js( __( 'Column', 'usces' ) ); ?>' + (c+1);
						}
						var selected = (defaultColLabel === cellText) ? ' selected="selected"' : '';
						opts += '<option value="' + c + '"' + selected + '">'+escapeHtml(cellText) + '</option>';
					}
					$(this).html(opts);
				});
			}

			function updateSubmitButton(){
				var selects = $('#mapping-container select');
				if(selects.length === 0){
					$('#import_submit').prop('disabled', true);
					return;
				}
				
				// If the first select is empty, disable submit.
				var firstVal = selects.first().val();
				if(firstVal === ''){
					$('#import_submit').prop('disabled', true);
					return;
				}
				
				// If all selects (except the first) are empty, disable submit.
				var othersAllEmpty = true;
				selects.slice(1).each(function(){
					if($(this).val() !== ''){
						othersAllEmpty = false;
						return false;
					}
				});
				if(othersAllEmpty){
					$('#import_submit').prop('disabled', true);
				} else {
					$('#import_submit').prop('disabled', false);
				}
			}

			$('input[name="has_header"]').on('change', updatePreview);
			$('#csv_encoding_select').on('change', function(){
				var enc       = $(this).val();
				var type      = $('input[name="type"]').val();
				var import_id = $('input[name="import_id"]').val();
				var wc_nonce  = $('#wc_nonce').val();
				var url       = new URL(window.location.href);
				url.searchParams.set('encoding', enc);
				url.searchParams.set('type', type);
				url.searchParams.set('import_id', import_id);
				url.searchParams.set('wc_nonce', wc_nonce);
				window.location.href = url.href;
			});
			$('#mapping-container').on('change', 'select', updateSubmitButton);
			$('#toggle-upload').on('click', function(){
				if ($('#upload-container').is(':visible')) {
					// If form is visible, hide it and change button text back to "Re-upload"
					$('#upload-container').hide();
					$(this).text('<?php echo esc_js( __( 'Re-upload', 'usces' ) ); ?>');
				} else {
					// If form is hidden, show it and change button text to "Close"
					$('#upload-container').show();
					$(this).text('<?php echo esc_js( __( 'Close', 'usces' ) ); ?>');
				}
			});

			// Function to highlight mapped columns
			function updateMappingHighlights(){
				// Remove highlight class from all headers and cells.
				$('#csv-preview-thead th, #csv-preview-tbody td').removeClass('mapped-column');

				// Process each select within mapping-container.
				$('#mapping-container select').each(function(){
				var colIndex = $(this).val();
				if(colIndex !== ''){
					// Add class to corresponding header.
					$('#csv-preview-thead th').eq(colIndex).addClass('mapped-column');
					// Add class to corresponding cell in each row.
					$('#csv-preview-tbody tr').each(function(){
					$(this).find('td').eq(colIndex).addClass('mapped-column');
					});
				}
				});
			}

			// Call updateMappingHighlights after updateSubmitButton.
			$('#mapping-container').on('change', 'select', function(){
				updateSubmitButton();
				updateMappingHighlights();
			});

			updatePreview();
			updateSubmitButton();
			updateMappingHighlights();

			<?php do_action( 'welcart_csv_preview_post_js' ); ?>

		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save mapping info => Start import
	 */
	public function handle_csv_mapping() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->go_to_error_page( __( 'Permission error.', 'usces' ) . ' (#071)' );
		}
		check_admin_referer( 'welcart_csv_import', 'wc_nonce' );

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		if ( $this->type !== $type ) {
			$this->go_to_error_page( __( 'Type does not match.', 'usces' ) . ' (#020)' );
		}

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';
		if ( ! $import_id ) {
			$this->go_to_error_page( __( 'Invalid access.', 'usces' ) . ' (#053)' );
		}
		$data = get_transient( $this->import_transient_prefix . $import_id );
		if ( ! $data ) {
			$this->go_to_error_page( __( 'Import session not found.', 'usces' ) . ' (#054)' );
		}

		$temp_file_name = sanitize_file_name( basename( sanitize_text_field( wp_unslash( $data['temp_file'] ) ) ) );
		$temp_file      = WEL_IMPORT_UPLOADS_DIR . '/' . $temp_file_name;
		$csv_name       = sanitize_text_field( wp_unslash( $data['csv_name'] ) );

		$csv_encoding = isset( $_POST['csv_encoding'] ) ? sanitize_text_field( wp_unslash( $_POST['csv_encoding'] ) ) : 'UTF-8';
		$has_header   = isset( $_POST['has_header'] ) ? (bool) sanitize_text_field( wp_unslash( $_POST['has_header'] ) ) : false;

		if ( isset( $_POST['mapping'] ) && is_array( $_POST['mapping'] ) && ! empty( $_POST['mapping'] ) ) {
			$mapping = array_map( 'sanitize_text_field', wp_unslash( $_POST['mapping'] ) );
		} else {
			$this->go_to_error_page( __( 'Required fields have not been selected. (#033)', 'usces' ) );
		}

		if ( empty( $temp_file ) || ! file_exists( $temp_file ) ) {
			$this->go_to_error_page( __( 'Temporary file not found.', 'usces' ) . ' ' . __( 'Please re-upload the CSV.', 'usces' ) . ' (#021)' );
		}
		$total_lines = $this->count_csv_lines( $temp_file, $csv_encoding );
		if ( $total_lines <= 0 ) {
			$this->go_to_error_page( __( 'CSV file is empty. Please check the content.', 'usces' ) . ' (#034)' );
		}

		$data = array(
			'temp_file'      => $temp_file,
			'csv_name'       => $csv_name,
			'csv_encoding'   => $csv_encoding,
			'has_header'     => $has_header,
			'mapping'        => $mapping,
			'total_lines'    => $total_lines,
			'offset'         => $has_header ? 1 : 0,
			'imported'       => 0,
			'start_time'     => time(),
			'inserted_count' => 0,
			'updated_count'  => 0,
			'memory_limit'   => ini_get( 'memory_limit' ),
		);
		$data = apply_filters( 'welcart_csv_import_transient_data', $data, $import_id );
		set_transient( $this->import_transient_prefix . $import_id, $data, 30 * MINUTE_IN_SECONDS );

		// Initialize error log.
		$error_log_path = $this->get_error_log_path();
		file_put_contents( $error_log_path, '' );

		$redirect_url = add_query_arg(
			array(
				'page'      => 'welcart-csv-importer',
				'step'      => 'import',
				'type'      => $this->type,
				'import_id' => $import_id,
				'wc_nonce'  => wp_create_nonce( 'welcart_csv_import' ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Import progress page
	 *
	 * @param string $type Import type.
	 * @param string $import_id Import ID.
	 */
	private function render_import_progress( $type, $import_id ) {

		$data = get_transient( $this->import_transient_prefix . $import_id );
		if ( ! $data ) {
			$this->render_error_page( __( 'Import session not found.', 'usces' ) . ' (#024)' );
		}

		$memory_limit = isset( $data['memory_limit'] ) ? $data['memory_limit'] : '';
		$memory_usage = isset( $data['memory_usage'] ) ? $data['memory_usage'] : '';

		$has_header = isset( $data['has_header'] ) ? $data['has_header'] : false;
		if ( $has_header ) {
			$total_lines = (int) $data['total_lines'] - 1;
		} else {
			$total_lines = (int) $data['total_lines'];
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<p><?php echo esc_html__( 'Import Progress', 'usces' ); ?></p>
			<div class="guide-area">
				<p><?php echo esc_html__( 'Please keep this page open until the import process is complete. Do not refresh, change pages, or close your browser.', 'usces' ); ?></p>
				<p><?php echo esc_html__( 'If the sum of added and updated records does not equal the total count, some data was not imported. Please check the error log after processing.', 'usces' ); ?></p>
				<?php do_action( 'welcart_csv_import_top_guide' ); ?>
			</div>
			<div>
				<p><strong><?php echo esc_html__( 'CSV File Name', 'usces' ); ?>:</strong> <?php echo esc_html( $data['csv_name'] ); ?></p>
				<p><strong><?php echo esc_html__( 'Total Count', 'usces' ); ?>:</strong> <?php echo esc_html( $total_lines ); ?></p>
				<p><strong><?php echo esc_html__( 'Processed Count', 'usces' ); ?>:</strong> <span id="processed-rows"><?php echo esc_html( $data['offset'] ); ?></span></p>
				<p><strong><?php echo esc_html__( 'Added Count', 'usces' ); ?>:</strong> <span id="inserted-count"><?php echo esc_html( $data['inserted_count'] ); ?></span></p>
				<p><strong><?php echo esc_html__( 'Updated Count', 'usces' ); ?>:</strong> <span id="updated-count"><?php echo esc_html( $data['updated_count'] ); ?></span></p>
				<p><strong><?php echo esc_html__( 'Memory Usage', 'usces' ); ?>:</strong> <span id="memory_usage"></span> / <?php echo esc_html( $memory_limit ); ?></p>
				<p><strong><?php echo esc_html__( 'Estimated Remaining Time', 'usces' ); ?>:</strong> <span id="estimated-time">--</span></p>
			</div>
			<div id="progress-container" style="border:1px solid #ccc;width:100%;background:#f7f7f7;margin:10px 0;">
				<div id="progress-bar" style="width:0%;height:30px;background:#0073aa;"></div>
			</div>
			<p id="progress-text">0%</p>
			<div id="import-result"></div>
		</div>
		<script>
			window.wel_csv_importer_data = window.wel_csv_importer_data || {};
			window.wel_csv_importer_data.initial_offset = <?php echo intval( $data['offset'] ); ?>;
			window.wel_csv_importer_importId = "<?php echo esc_js( $import_id ); ?>";
			window.wel_csv_importer_nonce  = "<?php echo esc_html( wp_create_nonce( 'process_csv_chunk_nonce' ) ); ?>";
			window.wel_csv_complete_nonce  = "<?php echo esc_html( wp_create_nonce( 'welcart_csv_import' ) ); ?>";
			window.wel_csv_importer_csvName = "<?php echo esc_js( $data['csv_name'] ); ?>";
			window.wel_csv_importer_type = "<?php echo esc_js( $this->type ); ?>";
			window.onbeforeunload = function(){
				return "<?php echo esc_js( __( 'Import is in progress. Leaving this page will interrupt the process.', 'usces' ) ); ?>";
			};
		</script>
		<?php
	}

	/**
	 * Process CSV chunk
	 */
	public function process_csv_chunk() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission error.', 'usces' ) . ' (#070)' ) );
		}

		if ( ! check_ajax_referer( 'process_csv_chunk_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please reload the page.', 'usces' ) ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		if ( $this->type !== $type ) {
			wp_send_json_error( array( 'message' => __( 'Type is invalid.', 'usces' ) ) );
		}

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';
		$offset    = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		if ( empty( $import_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid import ID. Please re-upload the CSV.', 'usces' ) ) );
		}

		// Get session.
		$data = get_transient( $this->import_transient_prefix . $import_id );
		if ( ! $data ) {
			wp_send_json_error( array( 'message' => __( 'Import session has expired. Please re-upload the CSV.', 'usces' ) ) );
		}

		$temp_file    = $data['temp_file'];
		$csv_encoding = $data['csv_encoding'];
		$has_header   = $data['has_header'];
		$mapping      = $data['mapping'];
		$total_lines  = $data['total_lines'];
		$imported     = isset( $data['imported'] ) ? intval( $data['imported'] ) : 0;
		$start_time   = isset( $data['start_time'] ) ? intval( $data['start_time'] ) : time();

		$inserted_count = isset( $data['inserted_count'] ) ? intval( $data['inserted_count'] ) : 0;
		$updated_count  = isset( $data['updated_count'] ) ? intval( $data['updated_count'] ) : 0;

		if ( ! file_exists( $temp_file ) ) {
			wp_send_json_error( array( 'message' => __( 'CSV file not found. Please re-upload the CSV.', 'usces' ) ) );
		}

		$handle = fopen( $temp_file, 'r' );
		if ( 'SJIS' === $csv_encoding ) {
			stream_filter_append( $handle, 'convert.iconv.sjis-win/utf-8' );
		}

		if ( ! $handle ) {
			wp_send_json_error( array( 'message' => __( 'Unable to open CSV file. Please re-upload the CSV.', 'usces' ) ) );
		}

		// Move pointer according to offset.
		for ( $i = 0; $i < $offset; $i++ ) {
			fgetcsv( $handle );
		}

		$process_info = array(
			'inserted_count' => 0,
			'updated_count'  => 0,
			'imported'       => 0,
			'processed'      => 0,
		);
		// Process each chunk via hook.
		$process_info = apply_filters( 'wel_csv_importer_chunk_process', $process_info, $handle, $data );

		fclose( $handle );

		$new_offset              = $offset + $process_info['processed'];
		$data['offset']          = $new_offset;
		$data['imported']       += $process_info['imported'];
		$data['inserted_count'] += $process_info['inserted_count'];
		$data['updated_count']  += $process_info['updated_count'];
		set_transient( $this->import_transient_prefix . $import_id, $data, 30 * MINUTE_IN_SECONDS );

		$percent          = min( 100, intval( ( $new_offset / $total_lines ) * 100 ) );
		$complete         = ( $new_offset >= $total_lines );
		$elapsed          = time() - $start_time;
		$avg_time_per_row = $offset > 0 ? ( $elapsed / $offset ) : 0;
		$remaining_rows   = $total_lines - $new_offset;
		$estimated_time   = round( $avg_time_per_row * $remaining_rows );
		$formatted_time   = $this->format_seconds( $estimated_time );
		$memory_usage     = number_format( memory_get_peak_usage() / 1024 / 1024, 1 ) . ' M';

		wp_send_json_success(
			array(
				'next_offset'    => $new_offset,
				'percent'        => $percent,
				'complete'       => $complete,
				'total'          => $total_lines,
				'imported'       => $data['imported'],
				'added'          => $data['inserted_count'],
				'updated'        => $data['updated_count'],
				'estimated_time' => $formatted_time,
				'memory_usage'   => $memory_usage,
			)
		);
	}

	/**
	 * Import completion page
	 *
	 * @param string $type Import type.
	 * @param string $import_id Import ID.
	 */
	private function render_result_page( $type, $import_id ) {

		$data = get_transient( $this->import_transient_prefix . $import_id );
		if ( ! $data ) {
			$this->render_error_page( __( 'Import session not found.', 'usces' ) . ' (#024)' );
		}

		if ( file_exists( WEL_IMPORT_UPLOADS_DIR ) ) {
			// Delete all existing CSV files.
			$files = glob( WEL_IMPORT_UPLOADS_DIR . '/*.csv' );
			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
		}

		$total      = isset( $data['total_lines'] ) ? $data['total_lines'] : 0;
		$updated    = isset( $data['updated_count'] ) ? $data['updated_count'] : '';
		$added      = isset( $data['inserted_count'] ) ? $data['inserted_count'] : '';
		$imported   = isset( $data['imported'] ) ? $data['imported'] : '';
		$csv_name   = isset( $data['csv_name'] ) ? $data['csv_name'] : '';
		$has_header = isset( $data['has_header'] ) ? $data['has_header'] : false;
		if ( $has_header ) {
			$total_lines = (int) $total - 1;
		} else {
			$total_lines = (int) $total;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<p><?php echo esc_html__( 'Import Completed', 'usces' ); ?></p>
			<div class="guide-area">
				<p><?php echo esc_html__( 'If the sum of added and updated records does not equal the total count, some data was not imported. Please check the error log after processing.', 'usces' ); ?></p>
				<?php do_action( 'welcart_csv_result_top_guide' ); ?>
			</div>
			<table class="widefat">
				<tbody>
					<tr>
						<th><?php echo esc_html__( 'CSV File Name', 'usces' ); ?></th>
						<td><?php echo esc_html( $csv_name ); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Total Count', 'usces' ); ?></th>
						<td><?php echo intval( $total_lines ); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Processed Count', 'usces' ); ?></th>
						<td><?php echo intval( $imported ); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Added Count', 'usces' ); ?></th>
						<td><?php echo intval( $added ); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Updated Count', 'usces' ); ?></th>
						<td><?php echo intval( $updated ); ?></td>
					</tr>
				</tbody>
			</table>
			<?php
			$error_log_path = $this->get_error_log_path();
			if ( file_exists( $error_log_path ) ) {
				if ( 0 === filesize( $error_log_path ) ) {

					// No error.
					file_put_contents( $error_log_path, __( "No error.\n", 'usces' ) );
					$first50 = __( "No error.\n", 'usces' );
					echo '<p>' . esc_html__( 'No Error', 'usces' ) . '</p>';

				} else {

					$lines     = file( $error_log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
					$first50   = array_slice( $lines, 0, 50 );
					$error_log = implode( "\n", $first50 );
					$num_log   = count( $lines );
					if ( $num_log > 50 ) {
						$error_log .= "\n" . __( 'More...', 'usces' );
					}
					$args      = array(
						'action'   => 'download_csv_error_log',
						'type'     => $this->type,
						'wc_nonce' => wp_create_nonce( 'download_csv_error_log' ),
					);
					$admin_url = add_query_arg( $args, admin_url( 'admin-post.php' ) );
					?>
					<h2><?php echo esc_html__( 'Error Log', 'usces' ); ?></h2>
					<p>
						<a href="<?php echo esc_url( $admin_url ); ?>" class="button">
							<?php echo esc_html__( 'Download Error Log', 'usces' ); ?>
						</a>
					</p>
					<pre style="background:#f7f7f7;padding:10px;"><?php echo esc_html( $error_log ); ?></pre>
					<?php

				}
			} else {
				echo '<p>' . esc_html__( 'No log file found.', 'usces' ) . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Error page
	 *
	 * @param string $message Error message.
	 */
	private function render_error_page( $message = '' ) {

		if ( empty( $message ) ) {
			$message = get_transient( 'welcart_csv_import_error' );
		}
		if ( ! $message ) {
			$message = __( 'No message provided.', 'usces' );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<p><?php echo esc_html__( 'An error occurred', 'usces' ); ?></p>
			<div class="guide-area">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
		</div>
		<?php
		do_action( 'admin_print_footer_scripts' );
		do_action( 'admin_footer' );
		do_action( 'admin_print_scripts' );
		do_action( 'admin_print_styles' );

		exit;
	}

	/**
	 * Redirect to error page
	 *
	 * @param string $message Error message.
	 */
	private function go_to_error_page( $message ) {
		set_transient( 'welcart_csv_import_error', $message, 5 * MINUTE_IN_SECONDS );
		$redirect_url = add_query_arg(
			array(
				'page'     => 'welcart-csv-importer',
				'step'     => 'error',
				'type'     => $this->type,
				'wc_nonce' => wp_create_nonce( 'welcart_csv_import' ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Download error log
	 */
	public function download_error_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->go_to_error_page( __( 'Permission error.', 'usces' ) . ' (#060)' );
		}
		check_admin_referer( 'download_csv_error_log', 'wc_nonce' );

		$error_log_path = $this->get_error_log_path();
		if ( ! file_exists( $error_log_path ) ) {
			$this->render_error_page( __( 'Error log file not found.', 'usces' ) . ' (#028)' );
		}
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="csv_error_log.txt"' );
		header( 'Content-Length: ' . filesize( $error_log_path ) );
		readfile( $error_log_path );
		exit;
	}

	/**
	 * Count CSV lines
	 *
	 * @param string $file File path.
	 * @param string $encoding Character encoding.
	 * @return int Line count.
	 */
	private function count_csv_lines( $file, $encoding ) {
		$count  = 0;
		$handle = fopen( $file, 'r' );
		if ( ! $handle ) {
			return 0;
		}
		while ( true ) {
			$data = fgetcsv( $handle );
			if ( false === $data ) {
				break;
			}
			if ( ! empty( array_filter( $data ) ) ) {
				++$count;
			}
		}
		fclose( $handle );
		return $count;
	}

	/**
	 * Get main columns
	 */
	public function get_columns() {
		return $this->table_columns;
	}

	/**
	 * Get meta fields
	 */
	public function get_meta_fields() {
		return $this->meta_fields;
	}

	/**
	 * Format seconds to time string
	 *
	 * @param int $seconds Seconds.
	 * @return string Formatted time.
	 */
	private function format_seconds( $seconds ) {
		$hours   = floor( $seconds / 3600 );
		$minutes = floor( ( $seconds % 3600 ) / 60 );
		$secs    = $seconds % 60;
		$str     = '';
		if ( $hours > 0 ) {
			$str .= $hours . __( ' hours ', 'usces' );
		}
		if ( $minutes > 0 ) {
			$str .= $minutes . __( ' minutes ', 'usces' );
		}
		$str .= $secs . __( ' seconds', 'usces' );
		return $str;
	}

	/**
	 * File upload error message
	 *
	 * @return string Error message.
	 */
	private function file_upload_error() {
		// phpcs:disable WordPress.Security.NonceVerification -- Already verified inside CSV importer class
		if ( ! isset( $_FILES['csv_file'] ) || empty( $_FILES['csv_file']['name'] ) ) {
			return __( 'Please select a file to upload.', 'usces' );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$file = $_FILES['csv_file'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Get error code.
		$error_code    = absint( $file['error'] );
		$error_message = '';

		// Error code messages.
		switch ( $error_code ) {
			case UPLOAD_ERR_OK:
				$error_message = false;
				break;
			case UPLOAD_ERR_INI_SIZE:
				$error_message = __( 'File size is too large.', 'usces' );
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$error_message = __( 'Exceeds maximum file size specified in the form.', 'usces' );
				break;
			case UPLOAD_ERR_PARTIAL:
				$error_message = __( 'File was only partially uploaded.', 'usces' );
				break;
			case UPLOAD_ERR_NO_FILE:
				$error_message = __( 'No file selected.', 'usces' );
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$error_message = __( 'Missing a temporary folder.', 'usces' );
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$error_message = __( 'Failed to write file to disk.', 'usces' );
				break;
			case UPLOAD_ERR_EXTENSION:
				$error_message = __( 'File upload stopped by extension.', 'usces' );
				break;
			default:
				$error_message = __( 'An unknown error occurred.', 'usces' );
				break;
		}

		return $error_message;
	}

	/**
	 * Error handling
	 */
	public function handle_error() {
			$this->render_error_page( __( 'Import type not specified. (#029)', 'usces' ) );
	}

	/**
	 * Get error log file path
	 */
	public function get_error_log_path() {
		return WEL_IMPORT_UPLOADS_DIR . '/csv-error-log.txt';
	}
}
