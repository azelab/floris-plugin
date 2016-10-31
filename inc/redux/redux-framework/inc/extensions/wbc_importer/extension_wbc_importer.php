<?php
/**
 * Extension-Boilerplate
 *
 * @link https://github.com/ReduxFramework/extension-boilerplate
 *
 * Radium Importer - Modified For ReduxFramework
 * @link https://github.com/FrankM1/radium-one-click-demo-install
 *
 * @package     WBC_Importer - Extension for Importing demo content
 * @author      Webcreations907
 * @version     1.0.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


// Don't duplicate me!
if ( !class_exists( 'ReduxFramework_extension_wbc_importer' ) ) {

    class ReduxFramework_extension_wbc_importer {

        public static $instance;

        static $version = "1.0.2";

        protected $parent;

        private $filesystem = array();

        public $extension_url;

        public $extension_dir;

        public $demo_data_dir;

        public $wbc_import_files = array();

        public $active_import_id;

        public $active_import;


        /**
         * Class Constructor
         *
         * @since       1.0
         * @access      public
         * @return      void
         */
        public function __construct( $parent ) {

            $this->parent = $parent;

            if ( !is_admin() ) return;

            //Hides importer section if anything but true returned. Way to abort :)
            if ( true !== apply_filters( 'wbc_importer_abort', true ) ) {
                return;
            }

            if ( empty( $this->extension_dir ) ) {
                $this->extension_dir = trailingslashit( str_replace( '\\', '/', dirname( __FILE__ ) ) );
                $this->extension_url = site_url( str_replace( trailingslashit( str_replace( '\\', '/', ABSPATH ) ), '', $this->extension_dir ) );
                $this->demo_data_dir = apply_filters( "wbc_importer_dir_path", $this->extension_dir . 'demo-data/' );
            }

            //Delete saved options of imported demos, for dev/testing purpose
            // delete_option('wbc_imported_demos');

            $this->getImports();
            //$this->set_home_and_blog();
            $this->field_name = 'wbc_importer';

            self::$instance = $this;

            add_filter( 'redux/' . $this->parent->args['opt_name'] . '/field/class/' . $this->field_name, array( &$this,
                    'overload_field_path'
                ) );

            add_action( 'wp_ajax_redux_wbc_importer', array(
                    $this,
                    'ajax_importer'
                ) );

            add_filter( 'redux/'.$this->parent->args['opt_name'].'/field/wbc_importer_files', array(
                    $this,
                    'addImportFiles'
                ) );

            //Adds Importer section to panel
            $this->add_importer_section();


        }

        /**
         * Get the demo folders/files
         * Provided fallback where some host require FTP info
         *
         * @return array list of files for demos
         */
        public function demoFiles() {

            $this->filesystem = $this->parent->filesystem->execute( 'object' );
            $dir_array = $this->filesystem->dirlist( $this->demo_data_dir, false, true );

            if ( !empty( $dir_array ) && is_array( $dir_array ) ) {
               
                uksort( $dir_array, 'strcasecmp' );
                return $dir_array;

            }else{

                $dir_array = array();

                $demo_directory = array_diff( scandir( $this->demo_data_dir ), array( '..', '.' ) );

                if ( !empty( $demo_directory ) && is_array( $demo_directory ) ) {
                    foreach ( $demo_directory as $key => $value ) {
                        if ( is_dir( $this->demo_data_dir.$value ) ) {

                            $dir_array[$value] = array( 'name' => $value, 'type' => 'd', 'files'=> array() );

                            $demo_content = array_diff( scandir( $this->demo_data_dir.$value ), array( '..', '.' ) );

                            foreach ( $demo_content as $d_key => $d_value ) {
                                if ( is_file( $this->demo_data_dir.$value.'/'.$d_value ) ) {
                                    $dir_array[$value]['files'][$d_value] = array( 'name'=> $d_value, 'type' => 'f' );
                                }
                            }
                        }
                    }

                    uksort( $dir_array, 'strcasecmp' );
                }
            }
            return $dir_array;
        }
        public function set_home_and_blog(){
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', '15' );
            update_option( 'page_for_posts', '444' );

        }


        
        
        public  function mailchimp_import_forms( $csv_file ) {
         
             //$csv_file = plugin_dir_path( __FILE__ ).'demo-data\floris\MailChimp-Forms.csv';
            $name = $csv_file;
            $ext_array = explode('.', $name);
            $ext = end( $ext_array );
            $tmpName = $csv_file;
            
            if($ext === 'csv') {
                if(($handle = fopen($tmpName, 'r')) !== FALSE) {
                    $num = count($handle);
                    $file = fopen( $tmpName, 'r');
                    $row = 1;
                    while (($line = fgetcsv($file, 10000, ',')) !== FALSE) {
                        if( count( $line ) > 1 ) {
                            // Check if this is a settings import by confirming the first option is 'yikes-mc-api-key'
                            if( $line[0] == 'yikes-mc-api-key' ) {  
                                $options = fgetcsv($file, 10000, ',');
                                $new_settings = array();
                                $x = 0;
                                // build our new array $key => $value pair
                                foreach( $line as $option_name ) {
                                    $new_settings[$option_name] = $options[$x]; 
                                    $x++;
                                }
                                // update the options in the databse
                                foreach( $new_settings as $option_name => $option_value ) {
                                    update_option( $option_name, $option_value );
                                }
                            } else { // if it's not, then it's an opt-in forms import
                                global $wpdb;       
                                if( $row != 1 ) {
                                    $wpdb->insert(
                                        $wpdb->prefix . 'yikes_easy_mc_forms',
                                        array(
                                            'list_id' => $line[1],
                                            'form_name' => stripslashes( $line[2] ),
                                            'form_description' => stripslashes( $line[3] ),
                                            'fields' => $line[4],
                                            'custom_styles' => $line[5],
                                            'custom_template' => $line[6],
                                            'send_welcome_email' => $line[7],
                                            'redirect_user_on_submit' => $line[8],
                                            'redirect_page' => $line[9],
                                            'submission_settings' => $line[10],
                                            'optin_settings' => $line[11],
                                            'error_messages' => $line[12],
                                            'custom_notifications' => $line[13],
                                            'impressions' => $line[14],
                                            'submissions' => $line[15],
                                            'custom_fields' => $line[16],
                                        ),
                                        array(
                                            '%s', // list id
                                            '%s', // form name
                                            '%s', // form description
                                            '%s', // fields
                                            '%s', // custom styles
                                            '%d',   // custom template
                                            '%d',   // send welcome email
                                            '%s',   // redirect user
                                            '%s',   // redirect page
                                            '%s',   // submission
                                            '%s',   // opt-in
                                            '%s', // error
                                            '%s', // custom notifications
                                            '%d',   // impressions #
                                            '%d',   // submissions #
                                            '%s', // custom fields
                                        )
                                    );
                                }   
                            }
                        }   
                        $row++;
                    }
                    fclose($file);
                } else {
                    wp_die( __( 'There was an error during import. If you continue to run into issues, please reach out to the Yikes Inc. support team.' , 'yikes-inc-easy-mailchimp-extender' ) );
                }
            }
            
        }

        public function getImports() {

            if ( !empty( $this->wbc_import_files ) ) {
                return $this->wbc_import_files;
            }

            $imports = $this->demoFiles();

            $imported = get_option( 'wbc_imported_demos' );

            if ( !empty( $imports ) && is_array( $imports ) ) {
                $x = 1;
                foreach ( $imports as $import ) {

                    if ( !isset( $import['files'] ) || empty( $import['files'] ) ) {
                        continue;
                    }

                    if ( $import['type'] == "d" && !empty( $import['name'] ) ) {
                        $this->wbc_import_files['wbc-import-'.$x] = isset( $this->wbc_import_files['wbc-import-'.$x] ) ? $this->wbc_import_files['wbc-import-'.$x] : array();
                        $this->wbc_import_files['wbc-import-'.$x]['directory'] = $import['name'];

                        if ( !empty( $imported ) && is_array( $imported ) ) {
                            if ( array_key_exists( 'wbc-import-'.$x, $imported ) ) {
                                $this->wbc_import_files['wbc-import-'.$x]['imported'] = 'imported';
                            }
                        }

                        foreach ( $import['files'] as $file ) {
                            switch ( $file['name'] ) {
                            case 'content.xml':
                                $this->wbc_import_files['wbc-import-'.$x]['content_file'] = $file['name'];
                                break;

                            case 'theme-options.txt':
                            case 'theme-options.json':
                                $this->wbc_import_files['wbc-import-'.$x]['theme_options'] = $file['name'];
                                break;

                            case 'widgets.json':
                            case 'widgets.txt':
                                $this->wbc_import_files['wbc-import-'.$x]['widgets'] = $file['name'];
                                break;

                            case 'screen-image.png':
                            case 'screen-image.jpg':
                            case 'screen-image.gif':
                                $this->wbc_import_files['wbc-import-'.$x]['image'] = $file['name'];
                                break;
                            }

                        }

                        if ( !isset( $this->wbc_import_files['wbc-import-'.$x]['content_file'] ) ) {
                            unset( $this->wbc_import_files['wbc-import-'.$x] );
                            if ( $x > 1 ) $x--;
                        }

                    }

                    $x++;
                }

            }

        }

        public function addImportFiles( $wbc_import_files ) {

            if ( !is_array( $wbc_import_files ) || empty( $wbc_import_files ) ) {
                $wbc_import_files = array();
            }

            $wbc_import_files = wp_parse_args( $wbc_import_files, $this->wbc_import_files );

            return $wbc_import_files;
        }

        public function ajax_importer() {
            if ( !isset( $_REQUEST['nonce'] ) || !wp_verify_nonce( $_REQUEST['nonce'], "redux_{$this->parent->args['opt_name']}_wbc_importer" ) ) {
                die( 0 );
            }
            if ( isset( $_REQUEST['type'] ) && $_REQUEST['type'] == "import-demo-content" && array_key_exists( $_REQUEST['demo_import_id'], $this->wbc_import_files ) ) {

                $reimporting = false;

                if ( isset( $_REQUEST['wbc_import'] ) && $_REQUEST['wbc_import'] == 're-importing' ) {
                    $reimporting = true;
                }

                $this->active_import_id = $_REQUEST['demo_import_id'];

                $import_parts         = $this->wbc_import_files[$this->active_import_id];

                $this->active_import = array( $this->active_import_id => $import_parts );

                $content_file        = $import_parts['directory'];
                $demo_data_loc       = $this->demo_data_dir.$content_file;

                if ( file_exists( $demo_data_loc.'/'.$import_parts['content_file'] ) && is_file( $demo_data_loc.'/'.$import_parts['content_file'] ) ) {

                    if ( !isset( $import_parts['imported'] ) || true === $reimporting ) {
                        include $this->extension_dir.'inc/init-installer.php';
                        $installer = new Radium_Theme_Demo_Data_Importer( $this, $this->parent );
                        $this->set_home_and_blog();
                        $csv_file_settings = plugin_dir_path( __FILE__ ).'demo-data\floris\MailChimp-Settings.csv';
                        $csv_file = plugin_dir_path( __FILE__ ).'demo-data\floris\MailChimp-Forms.csv';
                        $this->mailchimp_import_forms( $csv_file_settings);
                        $this->mailchimp_import_forms( $csv_file);
                    }else {
                        echo esc_html__( "Demo Already Imported", 'framework' );
                    }
                }

                die();
            }

            die();
        }

        public static function get_instance() {
            return self::$instance;
        }

        // Forces the use of the embeded field path vs what the core typically would use
        public function overload_field_path( $field ) {
            return dirname( __FILE__ ) . '/' . $this->field_name . '/field_' . $this->field_name . '.php';
        }

        function add_importer_section() {
            // Checks to see if section was set in config of redux.
            for ( $n = 0; $n <= count( $this->parent->sections ); $n++ ) {
                if ( isset( $this->parent->sections[$n]['id'] ) && $this->parent->sections[$n]['id'] == 'wbc_importer_section' ) {
                    return;
                }
            }

            $wbc_importer_label = trim( esc_html( apply_filters( 'wbc_importer_label', __( 'Demo Importer', 'framework' ) ) ) );

            $wbc_importer_label = ( !empty( $wbc_importer_label ) ) ? $wbc_importer_label : __( 'Demo Importer', 'framework' );

            $this->parent->sections[] = array(
                'id'     => 'wbc_importer_section',
                'title'  => $wbc_importer_label,
                'desc'   => '<p class="description">'. apply_filters( 'wbc_importer_description', esc_html__( 'Works best to import on a new install of WordPress', 'framework' ) ).'</p>',
                'icon'   => 'el-icon-website',
                'fields' => array(
                    array(
                        'id'   => 'wbc_demo_importer',
                        'type' => 'wbc_importer'
                    )
                )
            );
        }

    } // class
} // if
