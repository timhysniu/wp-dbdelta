<?php
/**
 * Database Change Management for WP_CLI
 * Author: Tim Hysniu
 */
class Db_Delta_Command extends WP_CLI_Command {

    private $_patch_dir = '';
    
    public function __construct() {
        
        $this->_patch_dir = defined('DB_DELTA_DIR') ? DB_DELTA_DIR : 'dbdelta';
        if( ! file_exists($this->_patch_dir) ) {
            if( ! mkdir($this->_patch_dir ) ) {
                WP_CLI::error( "Could not create directory: " . $this->_patch_dir );
                exit;
            } 
        }
        
        $this->_create_delta_table();
    }
    
    /**
     * Create an empty patch
     *
     * ## EXAMPLES
     *
     *     wp create $patch_id
     */    
    public function create($args, $assoc_args)
    {
        $template =  "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<patch>
  <up>
    <sql><![CDATA[ ... ]]></sql>
  </up>
  <down>
    <sql><![CDATA[ ... ]]></sql>
  </down>
</patch>";
        
        if(empty($args)) {
            WP_CLI::error( "Patch id is required. Example: wp dbdelta create patch_id ");
            exit;
        }
        
        list( $id ) = $args;
        
        $filename = date("Y-m-d-") . $id . '.xml';
        file_put_contents($this->_patch_dir . DIRECTORY_SEPARATOR . $filename, $template);
    }
    
    /**
     * Read patches directory for patches and execute
     * all or specified patch if not already run
     *
     * ## EXAMPLES
     *
     *     wp dbdelta up         ; run all upgrades in ascending order
     *     wp dbdelta $p1        ; run patch $p1
     *     wp dbdelta $p1 $p2    ; run several patches $p1 $p2 with order
     *                           ;   determined by filename (ascending)
     */
    public function up( $args, $assoc_args ) 
    {
        $this->_run_patch('up', $args);
    }
    
    /**
     * Read patches directory for patches and execute
     * all or specified patch if not already run
     *
     * ## EXAMPLES
     *
     *     wp dbdelta down       ; run all downgrades in descending order 
     *     wp dbdelta $p1        ; run patch $p1
     *     wp dbdelta $p1 $p2    ; run several patches $p1 $p2 with order
     *                           ;   determined by filename (descending)
     */    
    public function down( $args, $assoc_args )
    {
        $this->_run_patch('down', $args);
    }
    
    private function _run_patch($command, $args)
    {
        $patches = $this->_get_patches($command, $args);
        
        if(empty($patches)) {
            WP_CLI::success( "No patches to run.");
            return;
        }
        
        foreach($patches as $patch) {
            $xml = simplexml_load_file($this->_patch_dir . DIRECTORY_SEPARATOR . $patch);
            $statements = isset($xml->{$command}->sql) ? $xml->{$command}->sql : array();
            
            if( empty($statements)) {
                WP_CLI::warning( "$command: Could not read sql - " . $patch);
            }
            else {
                foreach($statements as $stmt) 
                {
                    $stmt = trim($stmt);
                    if(!empty($stmt)) {
                        $GLOBALS['wpdb']->query($stmt);
                    }
                }

                if($command == 'up') {
                    WP_CLI::success( "Upgrade complete: " . $patch);
                    $this->_log_upgrade($patch);
                }
                elseif($command == 'down') {
                    WP_CLI::success( "Downgrade complete: " . $patch);
                    $this->_log_downgrade($patch);                    
                }
            }
        }        
    }
    
    private function _get_patches($command, $args) 
    {
        $selected_paches = !empty($args) ? $args : array();
        $table = $this->_get_table_name();
        
        // scan directory for patches
        $files = array_diff(scandir($this->_patch_dir), array('.', '..'));
        
        // if a patch is specified then only that needs to run
        if(!empty($selected_paches)) {
            $files = array_intersect($selected_paches, $files);
        }
        
        // find what is executed already
        $executed = array();
        $executed_rows = $GLOBALS['wpdb']->get_results("SELECT * FROM {$table}");
        if(!empty($executed_rows)) {
            foreach($executed_rows as $row) {
                $executed[] = $row->patch_id;
            }
        }
        
        if($command == 'up') {
            $patches = array_diff($files, $executed);         
        }
        elseif($command == 'down') {
            $patches = array_intersect($executed, $files);
        }
        
        if($command == 'down') {
            rsort($patches);
        }
        
        return $patches;
    }
    
    private function _log_upgrade($patch)
    {
        $table = $this->_get_table_name();
        $GLOBALS['wpdb']->query(
            "INSERT INTO `$table` (patch_id, date_run) 
            VALUES ('$patch', NOW())");
        
    }
    
    private function _log_downgrade($patch)
    {
        $table = $this->_get_table_name();
        $GLOBALS['wpdb']->query(
            "DELETE FROM `$table` WHERE patch_id = '$patch'");        
    }
    
    private function _create_delta_table()
    {
        $table = $this->_get_table_name();
        $results = $GLOBALS['wpdb']->get_results("show tables like '{$table}'");
        if(empty($results)) {
            $results = $GLOBALS['wpdb']->query(
              "CREATE TABLE `wp_db_delta` (
                `patch_id` VARCHAR(128) NOT NULL,
                `date_run` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`patch_id`),
                UNIQUE INDEX `patch_id_UNIQUE` (`patch_id` ASC))");
        }
    }
    
    private function _get_table_name() {
        return $GLOBALS['table_prefix'] . 'db_delta';
    }
    
}

WP_CLI::add_command( 'dbdelta', 'Db_Delta_Command' );

