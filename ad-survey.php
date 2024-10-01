<?php
/**
* Plugin Name: Ad Survey
* Plugin URI: https://www.adillice.com/
* Author URI: https://www.adillice.com/
* Description: Just another custom survey plugin.
* Author: adillice
* Version: 1.0.1
**/

// no direct access
if(!defined('ABSPATH')) exit;

// auto load phpspreadsheet
require_once("includes/vendor/autoload.php");

// just read file
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// create class
class AdSurvey{
    
    // init class
    public static function adSurveyInit(){
        
        // check for session - if not start one
        if(!session_id()) session_start();

        // set class
        $adSurveyClass = __CLASS__;
        
        // init class
        new $adSurveyClass(new Spreadsheet(), new Xlsx());
        
    }

    // init variables
    private $sheet;
    private $reader;
    private static $exists = false;
    private static $tableName = "ad_survey_answers";
    protected $dir = "import";
    protected $file = "import.xlsx";
    protected $distrubution = "build";
    protected $importPageSlug = "page=ad-survey-import";
    protected $exportPageSlug = "page=ad-survey-export";
    protected $allowedFiles = array("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    protected $messages = array(
        "no-data" => "The requested data does not exist. Please re-import your survey questions and try again. If the problem persists, contact the system administrator.",
        "nonce" => "The request could not be processed. If the problem persists, please contact the system administrator.",
        "update-fail" => "The request could not be processed. If the problem persists, contact the system administrator.",
        "delete-success" => "The survey %s has been successful removed.",
        "import-success" => "The data was successfully uploaded.",
        "import-duplicate" => "The data could not be uploaded. This could be because you uploaded the exact same data. Please try again. If the problem persists, contact the system administrator.",
        "required" => "Please fill all required fields.",
        "file-type" => "Please upload the proper file type.",
        "survey-success" => "Thank you. Your survey has been successfully submitted.",
    );
    
    // constructor
    function __construct(Spreadsheet $sheet, Xlsx $reader){

        // set spreadsheet
        $this->sheet = ($sheet) ? $sheet : null;

        // set reader
        $this->reader = ($reader) ? $reader : null;

        // add scripts
        add_action('wp_enqueue_scripts', array($this, 'adFrontEndScripts')); 
        add_action('admin_enqueue_scripts', array($this, 'adBackEndScripts'));

        // POST: import file
        add_action('admin_post_uploadimportfile', array($this, 'uploadImportFile'));

        // POST: download file
        add_action('admin_post_downloadexportfile', array($this, 'downloadExportFile'));
        
        // add menu
        add_action('admin_menu', array($this, 'adSurveyMenu'));

        // AJAX: manage delete survey  
        add_action('wp_ajax_ad_survey_manage_delete', array($this, 'adSurveyDeleteSurvey'));

        // AJAX: save survey data  
        add_action('wp_ajax_nopriv_ad_survey_data', array($this, 'adSurveySaveSurveyData'));
        add_action('wp_ajax_ad_survey_data', array($this, 'adSurveySaveSurveyData'));

        // make sure we only call our code once
        add_filter('do_shortcode_tag', array($this, 'adSurveySoloShortCode'), 10, 2);

        // add shortcodes
        add_action('init', array($this, 'adSurveyShortcodes'));

    }

    // HOOK: activation
    public static function adSurveyActivate(){

        // init data option
        add_option("ad-survey-data", array());

        // add survey answer table
        self::adSurveyBuildTable();     

    }

    // HOOK: uninstall
    public static function adSurveyUninstall(){
        
        // delete options
        delete_option("ad-survey-data");
        
        // delete table 
        self::adSurveyDeleteTable();
        
    }

    // BUILD QUESTIONS
    private function adSurveyBuildQuestions($title, $data){

        // CHECK: title & data
        if((!isset($title)) || (empty($title)) || (!isset($data) || (is_array($data)) && (count($data) <= 0))) return;

        // filter title
        $title = sanitize_text_field($title);
    
        // CHECK: check survey is complete
        if($this->adSurveyComplete($title)) return;

        // set hide class
        $jsHide = "--ad-survey-hide";

        // question count
        $questionCount = count($data); 

        // start question count
        $questionStartNum = 1;

        // init output
        $output = "<div class='ads__questions'>";
            
            // close button
            $output .= "<button class='ads__questions__close' type='button'>&nbsp;</button>"; 

                // start form
                $output .= "<form class='ads__questions__form' action='".esc_url(admin_url('admin-ajax.php'))."' method='post'>";

                // start list
                $output .= "<ul class='ads__questions__form__ul'>";

                    // build out questions
                    foreach ($data as $key => $value){

                        // get question type
                        $type = $value[0];
                        
                        // get question
                        $question = $value[1];

                        // question data set value
                        $questionData = trim(preg_replace("/[^a-zA-Z0-9]/", " ", $question));
                        $questionData = str_replace(" ", "_", preg_replace('/\s+/', ' ', $questionData));

                        // set surveu name
                        if($key <= 1) $output .= "<input type='hidden' name='survey' value='".preg_replace('/[^ \w]+/', '', $title)."'>";

                        // start li tag
                        $output .= "<li class='ads__questions__form__ul__li".(($key > 1) ? " ".$jsHide." " : "")."' data-num='0'>";

                            // question
                            $output .= "<h2 class='ads__questions__form__ul__li__header'>".$question."</h2>";

                            // question hidden input
                            $output .= "<input type='hidden' name='q_".$key."' value='".$questionData."'>";

                            // TEXT: input
                            if($type === "text"){

                                // get place holder value
                                $placeholder = (isset($value[2]) && trim($value[2]) != "") ? trim($value[2]) : "";

                                // add field
                                $output .= "<input class='ads__questions__form__ul__li__text' type='text' name='a_".$key."' placeholder='".$placeholder."' value=''>";
                                
                            }

                            // SINGLE: radio
                            if($type === "single"){

                                // get possible answers
                                $singleAnswers = explode(",", $value[2]); 

                                // LOOP: generate radio bttns
                                foreach ($singleAnswers as $value){
                                    
                                    // trim value
                                    $value = trim($value);

                                    // append radio to output
                                    $output .= "<label class='ads__questions__form__ul__li__radio'><input type='radio' name='a_".$key."' value='".$value."'>".$value."</label>";

                                }

                            }

                            // MULTIPLE: checkbox
                            if($type === "multiple"){

                                // get possible answers
                                $multipleAnswers = explode(",", $value[2]); 

                                // LOOP: generate radio bttns
                                foreach ($multipleAnswers as $value){
                                    
                                    // trim value
                                    $value = trim($value);

                                    // append radio to output
                                    $output .= "<label class='ads__questions__form__ul__li__checkbox'><input type='checkbox' name='a_".$key."[]' value='".$value."'>".$value."</label>";

                                }

                            }

                        // close li tag
                        $output .= "</li>";

                    }

                    // close ul tag
                    $output .= "</ul>";

                    // setup form gui
                    $output .= "<div class='ads__questions__form__ui'>";

                        // if we have multple questions
                        if($questionCount > 1){

                            // start gui wrapper 
                            $output .= "<div class='ads__questions__form__ui__wrapper'>";

                                // prev button
                                $output .= "<button class='ads__questions__form__ui__wrapper__prev --ad-survey-semi' type='button'>&nbsp;</button>"; 
                                
                                // copy
                                $output .= "<p class='ads__questions__form__ui__wrapper__copy'><span>1</span> of ".$questionCount."</p>";
                                
                                // next button
                                $output .= "<button class='ads__questions__form__ui__wrapper__next --ad-survey-semi' type='button'>&nbsp;</button>"; 
                                
                            // close gui wrapper 
                            $output .= "</div>";

                        }

                        // message area
                        $output .= "<div class='ads__questions__form__ui__msg --ad-survey-hide'></div>";

                        // add submit button
                        $output .= "<input class='ads__questions__form__ui__submit".(($questionCount === 1) ? "" : " ".$jsHide." ")."' type='submit' value='Submit'>";

                        // hidden fields
                        $output .= wp_nonce_field("ads-result-".$title, "ad_survey_results", true, false);
                        
                    // close gui tag
                    $output .= "</div>";
                    
                // close form tag
                $output .= "</form>";
            
        // close wrapper
        $output .= "</div>";
        
        // return output
        return $output;
        
    }

    // DB: BUILD SQL TABLE
    private static function adSurveyBuildTable(){

        // init global 
        global $wpdb;

        // create charset
        $charset = $wpdb->get_charset_collate();

        // create table name with wp prefix
        $name = $wpdb->prefix.self::$tableName;
        
        // sql query to create table 
        $sql = "CREATE TABLE IF NOT EXISTS `".$name."`(

            `id` BIGINT NOT NULL AUTO_INCREMENT,
            `survey_title` VARCHAR(255) NOT NULL,
            `question` VARCHAR(255) NOT NULL,
            `answer` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(`id`)
        
        )".$charset.";";

        // require upgrade.php
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);

    }

    // DB: INSERT DATA TO SQL TABLE
    private function adSurveyInsertTable($title = null, $question = null, $answers = null){

        // end case
        if(is_null($title) || is_null($question) || is_null($answers)) return;

        // init glbal
        global $wpdb;     

        // create table name with wp prefix
        $name = $wpdb->prefix.self::$tableName;
        
        // sanitize title
        $title = esc_sql($title);

        // sanitize question
        $question = esc_sql($question);

        // sanitize answers
        $answers = esc_sql($answers);

        // if answer is an array convert to string
        $answers = (is_array($answers)) ? implode(",", $answers): $answers;
        
        // current timestamp
        $now = date("Y-m-d H:i:s");

        // DB query 
        $wpdb->insert($name, array('id' => NULL, 'survey_title' => $title, 'question' => $question, 'answer' => $answers, 'created_at' => $now, 'updated_at' => $now));

        // flush
        $wpdb->flush();
        
        // return
        return;
        
    }

    // DB: DELETE SQL TABLE
    private static function adSurveyDeleteTable(){
        
        // init global 
        global $wpdb;

        // get table name
        $name = $wpdb->prefix.self::$tableName;

        // run delete query
        $wpdb->query("DROP TABLE IF EXISTS ".$name);

        // flush
        $wpdb->flush();

        // return
        return;

    }

    // MENU: add menu to dashboard
    function adSurveyMenu(){

        // add main menu 
        add_menu_page(
            "AD Survey", 
            "AD Survey", 
            'manage_options', 
            "ad-survey",
            null,
            "dashicons-analytics"
        );

        // add sub menu
        add_submenu_page(
            "ad-survey",                            // slug
            "Manage Surveys",                       // page title
            'Manage Surveys',                       // menu title
            'manage_options',                       // options
            "ad-survey-manage",                     // slug
            array($this, "adSurveyManage"),         // callback
        );

        // add sub menu
        add_submenu_page(
            "ad-survey",                            // slug
            "Import Questions",                     // page title
            'Import Questions',                     // menu title
            'manage_options',                       // options
            "ad-survey-import",                     // slug
            array($this, "adSurveyImport"),         // callback
        );

        // add sub menu
        add_submenu_page(
            "ad-survey",                            // slug
            "Export Results",                       // page title
            'Export Results',                       // menu title
            'manage_options',                       // options
            "ad-survey-export",                     // slug
            array($this, "adSurveyExport"),         // callback
        );

        // remove sub menu with same main menu label
        remove_submenu_page("ad-survey", "ad-survey");

    }

    // SCRIPTS: frontend with cache buster
    function adFrontEndScripts(){

        // check if main js exists
        if(file_exists(__DIR__.DIRECTORY_SEPARATOR.$this->distrubution.DIRECTORY_SEPARATOR."main.js")){
            
            // add admin js
            wp_register_script('ad-survey-js', plugins_url($this->distrubution.DIRECTORY_SEPARATOR."main.js", __FILE__), array(), "1.".mt_rand(0, 100).".".mt_rand(0, 1000), false);
            wp_enqueue_script('ad-survey-js');
            
        }
        
        // check if main css exists
        if(file_exists(__DIR__.DIRECTORY_SEPARATOR.$this->distrubution.DIRECTORY_SEPARATOR."main.css")){
            wp_register_style("ad-survey-css", plugins_url($this->distrubution.DIRECTORY_SEPARATOR."main.css", __FILE__), array(), "1.".mt_rand(0, 100).".".mt_rand(0, 1000), false);
            wp_enqueue_style('ad-survey-css');
        }

        // que up items
        // wp_enqueue_script('jquery-ui-sortable');

    }

    // SCRIPTS: backend with cache buster
    function adBackEndScripts(){
        
        // check if admin js exists
        if(file_exists(__DIR__.DIRECTORY_SEPARATOR.$this->distrubution.DIRECTORY_SEPARATOR."admin.js")){

            // add admin js
            wp_register_script('ad-survey-admin-js', plugins_url('build/admin.js', __FILE__), array(), "1.".mt_rand(0, 100).".".mt_rand(0, 1000), false);
            wp_enqueue_script('ad-survey-admin-js');
            
        }

        // check if admin css file exists
        if(file_exists(__DIR__.DIRECTORY_SEPARATOR.$this->distrubution.DIRECTORY_SEPARATOR."admin.css")){

            // add admin css
            wp_register_style("ad-survey-admin-css", plugins_url($this->distrubution.DIRECTORY_SEPARATOR."admin.css", __FILE__), array(), "1.".mt_rand(0, 100).".".mt_rand(0, 1000), false);
            wp_enqueue_style('ad-survey-admin-css');

        }

    }

    // PAGE: manage data in db page
    function adSurveyManage(){

        // wrapper div
        echo "<div class='wrap adsurvery__manage'>";
    
            // title
            echo "<h1 class='wp=heading-inline adsurvery__manage__wrapper__header'>Manage Surveys</h1>";

            // response area
            echo "<div class='adsurvey__manage__wrapper__response --survey-response'></div>";

            // line
            echo "<hr class='wp-header-end adsurvery__manage__wrapper__line'>";

            echo "<div class='adsurvery__manage__wrapper__copy'>";
                echo "<p>To add a survey onto a page or post you will need a shortcode which is provided below.</p>";
                echo "<p>To copy the shortcode for a specific survey just click the <em>'Copy Shortcode'</em> button located directly under the survey title.</p>";
                echo "<p>To publicly render a survey, go and edit the desired page or post. Then paste the copied shortcode into the page editor and save your changes.</p>";
                echo "<p>You can also delete a survey that is not needed anymore by clicking the <em>'Delete Survey'</em> button. This will not delete any previously submitted user data.</p>";
            echo "</div>";

            // manage saved surveys
            echo "<div class='adsurvery__manage__wrapper__list'>";
                echo $this->manageSurveyData();
            echo "</div>";
            
            // add hidden fields [ajax url:nonce]
            echo "<input class='adsurvery__manage__wrapper__url' type='hidden' name='url' value='".esc_url(admin_url('admin-ajax.php'))."'>";
            echo wp_nonce_field("ad-s-mp-nonce", "ad-survey-manage-nonce", true, false);

        // close wrapper
        echo "</div>";

        return;
    }

    // PAGE: import data page
    function adSurveyImport(){

        // init sample file
        $adSampleFile = plugin_dir_url(__DIR__).plugin_basename(__DIR__).DIRECTORY_SEPARATOR."sample".DIRECTORY_SEPARATOR."template.xlsx";

        // wrapper div
        echo "<div class='wrap adsurvery__import__wrapper'>";
    
            // title
            echo "<h1 class='wp=heading-inline adsurvery__import__wrapper__header'>Import Questions</h1>";

            // response area
            echo "<div class='adsurvey__import__wrapper__response'>".$this->displayMessage()."</div>";

            // line
            echo "<hr class='wp-header-end adsurvery__import__wrapper__line'>";

            echo "<div class='adsurvery__import__wrapper__copy'>";
                echo "<p>Please upload an excel file <em>(xlxs) with all of your questions</em>.</p>";
                echo "<p>Each spreadsheet inside <span class='--survey-error'>your file must have a unique name</span> and should be considered as a separate survey.</p>";
                echo "<p><a href='".$adSampleFile."' target='_blank' download>Click here</a> to see a sample file to properly setup your data.</p>";
            echo "</div>";

            // form
            echo "<form class='adsurvery__import__wrapper__form' action='".esc_url(admin_url('admin-post.php')."?action=uploadimportfile")."' method='post' enctype='multipart/form-data'>";

                // label 
                echo "<label class='adsurvery__import__wrapper__form__label' for='ad-upload-file'>Upload Spreadsheet File</label>";

                // input
                echo "<input id='ad-upload-file' class='adsurvery__import__wrapper__form__file' type='file' name='ad_survey_file' accept='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'>";

                // nonce field - prevent xss - action:name
                echo wp_nonce_field("adSurveyUploadNonce", "ad_survey_nonce", true, false);

                // submit
                echo "<p class='adsurvery__import__wrapper__form__submit'><input class='button button-primary' type='submit' value='Submit'></p>";

            // close form
            echo "</form>";

        // close div
        echo "</div>";

        // return
        return;

    }

    // PAGE: export data page
    function adSurveyExport(){

        // wrapper div
        echo "<div class='wrap adsurvery__export__wrapper'>";
    
            // title
            echo "<h1 class='wp=heading-inline adsurvery__export__wrapper__header'>Export Results</h1>";

            // response area
            echo "<div class='adsurvey__export__wrapper__response'>".$this->displayMessage()."</div>";

            // line
            echo "<hr class='wp-header-end adsurvery__export__wrapper__line'>";

            // intro copy
            echo "<div class='adsurvery__export__wrapper__copy'>";
                echo "<p>Click the <em>'Export'</em> button below to download all user submitted survey responses.</p>";
                echo "<p>The downloaded file will be formatted as an excel file <em>(xlxs)</em>.</p>";
            echo "</div>";

            // form
            echo "<form class='adsurvery__export__wrapper__form' action='".esc_url(admin_url('admin-post.php')."?action=downloadexportfile")."' method='post'>";

                // submit
                echo "<p class='adsurvery__export__wrapper__form__submit'><input class='button button-primary' type='submit' value='Export'></p>";
                
                // nonce field - prevent xss - action:name
                echo wp_nonce_field("adSurveyExportNonce", "ad_survey_nonce", true, false);

            // close form
            echo "</form>";

        // close wrapper
        echo "</div>";
        
        // return
        return;

    }

    // MANAGE: display serialized survey questions
    function manageSurveyData(){

        // get serialized data
        $data = get_option("ad-survey-data");
        
        // unserialize data
        $data = maybe_unserialize($data);

        // data check
        if((is_array($data))&&(count($data) <= 0)){

            // display no data messeage
            echo "<p class='--survey-error'>".__($this->messages["no-data"])."</p>";
            
            // return
            return;

        }

        // open list
        echo "<ul>";
        
        // LOOP: thru keys and build ul list 
        foreach ($data as $key => $value){
            
            // get title
            $title = str_replace("_", " ", $key); 
            
            // start list item
            echo "<li>";
                
                // add title 
                echo "<p>".$title."</p>";

                // button wrapper
                echo "<div data-num='".$key."'>";

                    // button
                    echo "<button class='--copy-bttn'>";
                        echo "<span>Copy Shortcode</span>";
                        echo "<svg viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'><path fill-rule='evenodd' d='M4 2a2 2 0 00-2 2v9a2 2 0 002 2h2v2a2 2 0 002 2h9a2 2 0 002-2V8a2 2 0 00-2-2h-2V4a2 2 0 00-2-2H4zm9 4V4H4v9h2V8a2 2 0 012-2h5zM8 8h9v9H8V8z'/></svg>";
                    echo "</button>";
                    
                    // button
                    echo "<button class='--delete-bttn'>";
                        echo "<span>Delete Survey</span>"; 
                        echo "<svg viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M10 12L14 16M14 12L10 16M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M18 6V16.2C18 17.8802 18 18.7202 17.673 19.362C17.3854 19.9265 16.9265 20.3854 16.362 20.673C15.7202 21 14.8802 21 13.2 21H10.8C9.11984 21 8.27976 21 7.63803 20.673C7.07354 20.3854 6.6146 19.9265 6.32698 19.362C6 18.7202 6 17.8802 6 16.2V6' stroke='#000000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>";
                    echo "</button>";

                // close button wrapper
                echo "</div>";

            // close list item
            echo "</li>";

        }
        
        // close list 
        echo "</ul>";

        // return
        return;

    }

    // POST IMPORT: upload file
    function uploadImportFile(){

        // header status
        status_header(200);

        // init output
        $output = array();

        // POST data
        $uploaded = isset($_FILES['ad_survey_file']) ? $_FILES['ad_survey_file'] : null;
        $nonce = isset($_POST['ad_survey_nonce']) ? $_POST['ad_survey_nonce'] : null;

        //CHECK NONCE & USER ROLE
        if((!is_null($nonce)) && wp_verify_nonce($nonce, 'adSurveyUploadNonce') && current_user_can('manage_options')){

            // VALIDATE FILE ----------------------------- //
            // get post file sub data 
            $file = $uploaded["tmp_name"];
            $type = $uploaded["type"];
            
            // double check file mime using finfo_open
            $info = finfo_open(FILEINFO_MIME_TYPE);
            $doubleCheck = @finfo_file($info, $file);
            finfo_close($info);

            // make sure file is allowed
            if(in_array($type, $this->allowedFiles) && in_array($doubleCheck, $this->allowedFiles)){    

                // READ FILE --------------------------------- //
                // read sheet with phpspreadsheet
                $sheet = $this->reader;
                $sheet = $sheet->load($file);
                $sheetLength = $sheet->getSheetCount();
                
                //cache i
                $i = 0;

                // LOOP: thru all spread sheets
                for($i; $i < $sheetLength; $i++){ 
                    
                    // get current
                    $thisSheet = $sheet->getSheet($i);

                    // convert to array
                    $data = $thisSheet->toArray();

                    // title
                    $title = $thisSheet->getTitle();

                    // create id from title 
                    $id = str_replace(" ", "_", $title);
                    $id = trim($id);

                    // overwrite first row (excel header) with spreadsheet title
                    $data[0] = array($title);

                    // append current sheet data to output data
                    $output[$id] = $data;

                }
                
                // serialize data
                $serialize = maybe_serialize($output);

                // SAVE DATA TO DB --------------------------- //
                // update db option
                $update = update_option("ad-survey-data", $serialize);

                // SUCCESS: make sure update was successful
                if($update){

                    //redirect with success msg
                    wp_safe_redirect(
                        esc_url_raw(add_query_arg('success', __($this->messages["import-success"]), esc_url(admin_url("admin.php")."?".$this->importPageSlug)))
                    );

                // ERROR: update option failed
                }else{

                    //redirect with error msg
                    wp_safe_redirect(
                        esc_url_raw(add_query_arg('error', __($this->messages["import-duplicate"]), esc_url(admin_url("admin.php")."?".$this->importPageSlug)))
                    );

                }

            // ERROR: wrong file type
            }else{

                //redirect with error msg
                wp_safe_redirect(
                    esc_url_raw(add_query_arg('error', __($this->messages["file-type"]), esc_url(admin_url("admin.php")."?".$this->importPageSlug)))
                );

            }

        // FAILED: nonce failed
        }else{

            //redirect with error msg
            wp_safe_redirect(
                esc_url_raw(
                    add_query_arg('error', __($this->messages["nonce"]), esc_url(admin_url("admin.php")."?".$this->importPageSlug))
                )
            );
            
        }

        // exit
        exit();

    }

    // POST EXPORT: download survey results 
    function downloadExportFile(){

        // setup headers
        status_header(200);

        // init output
        $output = array();

        // POST data
        $nonce = isset($_POST['ad_survey_nonce']) ? $_POST['ad_survey_nonce'] : null;

        //CHECK NONCE & USER ROLE
        if((!is_null($nonce)) && wp_verify_nonce($nonce, 'adSurveyExportNonce') && current_user_can('manage_options')){

            // init glbal
            global $wpdb;     

            // create table name with wp prefix
            $name = $wpdb->prefix.self::$tableName;

            // get data
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", $name), ARRAY_A);
            
            // db error
            if($wpdb->last_error){

                //redirect with error msg
                wp_safe_redirect(
                    esc_url_raw(
                        add_query_arg('error', __($wpdb->last_error), esc_url(admin_url("admin.php")."?".$this->exportPageSlug))
                    )
                );

            // SUCCESS
            }else{

                // CHECK RESULTS
                if((is_array($results))&&(count($results) > 0)){

                    // Create spreadsheet
                    $sheet = $this->sheet;
                    $worksheet = $sheet->getActiveSheet();

                    // add xlsx header
                    array_push($output, array("Id", "Survey", "Question", "Answer", "Timestamp"));

                    // LOOP: thru results data
                    foreach($results as $key => $value){
                        
                        // remove last element
                        array_pop($value);

                        // add data row to output
                        array_push($output, $value);

                    }

                    // create worksheet from array
                    $worksheet->fromArray($output, null, "A1");

                    // set file headers
                    header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment; filename="survey-results.xlsx"');
                    header('Cache-Control: max-age=0');


                    // create file
                    $file = IOFactory::createWriter($sheet, "Xlsx");

                    // output to browser
                    $file->save('php://output');

                    
                
                    
  
                    

                // ERROR: no data
                }else{

                    //redirect with error msg
                    wp_safe_redirect(
                        esc_url_raw(
                            add_query_arg('error', __($this->messages["no-data"]), esc_url(admin_url("admin.php")."?".$this->exportPageSlug))
                        )
                    );

                }

            }

        // ERROR: nonce/validation error 
        }else{
            
            //redirect with error msg
            wp_safe_redirect(
                esc_url_raw(
                    add_query_arg('error', __($this->messages["nonce"]), esc_url(admin_url("admin.php")."?".$this->exportPageSlug))
                )
            );

        }

    }

    // DELETE: remove survey
    function adSurveyDeleteSurvey(){

        // ini output
        $output = array();

        // get post data and sanatize
        $survey = (isset($_POST["survey"])) ? htmlspecialchars(trim($_POST["survey"])) : null;
        $nonce = (isset($_POST["nonce"])) ? htmlspecialchars(trim($_POST["nonce"])) : null;

        // NONCE, USER ROLES & ERROR CHECKS 
        if(is_null($survey) || is_null($nonce) || (!wp_verify_nonce($nonce, 'ad-s-mp-nonce')) || (!current_user_can('manage_options'))){

            // set error
            $output["error"] = __($this->messages["nonce"]);
            
            // return error
            die(json_encode($output));
            exit();

        }

        // update db: get option
        $dbData = get_option("ad-survey-data");
        
        // data exists
        if($dbData){

            // unserialize
            $data = maybe_unserialize($dbData);

            // remove specific survey
            unset($data[$survey]);

            // serialize
            $serialize = maybe_serialize($data);

            // update db
            $updated = update_option("ad-survey-data", $serialize);

            // SUCCESS: db updated
            if($updated){

                // set output
                $output["success"] = sprintf(__($this->messages["delete-success"]), $survey);

            // ERROR: update failed
            }else{

                // set output
                $output["error"] = __($this->messages["update-fail"]);

            }

            // return error
            die(json_encode($output));
            exit();

        // ERROR: no data
        }else{

            // set output
            $output["error"] = __($this->messages["no-data"]);

            // return error
            die(json_encode($output));
            exit();

        }

        // return
        return;

    }

    // AJAX SAVE: survey data
    function adSurveySaveSurveyData(){

        // ini output
        $output = array();
        
        // set post data
        $postData = $_POST;

        // get nonce
        $nonce = isset($postData['ad_survey_results']) ? htmlspecialchars(trim($postData['ad_survey_results'])) : null;

        // get survey name/id
        $surveyID = isset($postData['survey']) ? htmlspecialchars(trim($postData['survey'])) : null;

        // CHECK: check if survey is complete
        if($this->adSurveyComplete($surveyID)){
            
            // set success msg
            $output["success"] = __($this->messages["survey-success"]);
            
            // return error
            die(json_encode($output));
            exit();

        }

        // NONCE & ERROR CHECKS 
        if(is_null($nonce) || !wp_verify_nonce($nonce, "ads-result-".$surveyID)){

            // set error
            $output["error"] = __($this->messages["nonce"]);
            
            // return error
            die(json_encode($output));
            exit();

        }

        // remove survey label, nonce & referer from $_POST 
        unset($postData['survey']);
        unset($postData['ad_survey_results']);
        unset($postData['_wp_http_referer']);

        // save success
        $success = false;

        // init counter
        $counter = 0;

        // make sure we do not have any empty answers
        foreach($postData as $index => $check){
            
            // increment counter
            $counter++;

            // only loop thru answers
            if(!isset($postData["a_".$counter])) continue;

            // init get current answer
            $currentAnswer = $postData["a_".$counter];

            // ARRAY
            if(is_array($currentAnswer)){

                // get current count
                $currentCount = count($currentAnswer);

                // make sure we do not have any empty values
                if($currentCount != count(array_filter($currentAnswer))){

                    // set error
                    $output["error"] = __($this->messages["required"]);
                    
                    // return error
                    die(json_encode($output));
                    exit();

                    // break
                    break;

                }

            // NOT ARRAY
            }else{
                
                // check if answer is empty
                if(trim($currentAnswer) === ""){

                    // set error
                    $output["error"] = __($this->messages["required"]);
                    
                    // return error
                    die(json_encode($output));
                    exit();

                    // break
                    break;

                }

            }

        }

        // get data
        $dbData = maybe_unserialize(get_option("ad-survey-data"));
        
        // check db data
        if($dbData){

            // LOOP: thru db
            foreach($dbData[$surveyID] as $key => $value){
                
                // skip zero 
                if($key < 1) continue;

                // format question in db to match post question
                $dbQuestion = trim(preg_replace("/[^a-zA-Z0-9]/", " ", $dbData[$surveyID][$key][1]));
                $dbQuestion = str_replace(" ", "_", preg_replace('/\s+/', ' ', $dbQuestion));

                // check if questions match
                if($postData["q_".$key] == $dbQuestion){

                    // set survey name
                    $surveyName = str_replace("_", " ", $surveyID);
                    $thisQuestion = $postData["q_".$key];
                    $thisAnswer = $postData["a_".$key];
                    
                    // add data to database
                    $this->adSurveyInsertTable($surveyName, $thisQuestion, $thisAnswer);
                    
                    // saved to db successful
                    $success = true;

                    // ----------------------------------------- //
                    // SESSION: -------------------------------- //
                    // ----------------------------------------- //
                    // update db option
                    $_SESSION[$surveyID."_complete"] = (bool) true;
                    
                    // write and close session
                    session_write_close();
                    // ----------------------------------------- //
                    // SESSION: -------------------------------- //
                    // ----------------------------------------- //

                }

            }

        // ERROR: no db data
        }else{

            // set error
            $output["error"] = __($this->messages["no-data"]);
            
            // return error
            die(json_encode($output));
            exit();

        }

        // SUCCESS: success check
        if($success){

            // set success
            $output["success"] = __($this->messages["survey-success"]);
            
            // return error
            die(json_encode($output));
            exit();

        }

    }

    // CHECK: survey complete check
    function adSurveyComplete($surveyLabel = null, $state = false){

        // end case  
        if(is_null($surveyLabel)) return false;

        // set survey name
        $surveyName = str_replace(" ", "_", $surveyLabel);

        // SESSION CHECK: if survey has already been completed
        if((!session_id())||(isset($_SESSION[$surveyName.'_complete']) && ($_SESSION[$surveyName.'_complete'] === true))) $state = true;

        // return state
        return $state;

    }

    // SHORTCODE: add dynamic shortcode
    function adSurveyShortcodes(){

        // get db data
        $dbData = get_option("ad-survey-data");

        // db data exists
        if($dbData){

            // unserialize
            $data = maybe_unserialize($dbData);

            // data check
            if((is_array($data))&&(count($data) > 0)){

                // LOOP: thru surveys
                foreach ($data as $key => $value){
                    
                    // get survey data
                    $surveyData = $data[$key];

                    // add short code
                    add_shortcode($key, function() use ($key, $surveyData){

                        // remove title row
                        unset($surveyData[0]);

                        // build out specific survey
                        return $this->adSurveyBuildQuestions($key, $surveyData);

                    });

                }

            }

        }

    }

    // ONE SHORTCODE: force only one survey per page
    function adSurveySoloShortCode($output, $tag){

        // init data option
        $data = maybe_unserialize(get_option("ad-survey-data"));

        // check data
        if(is_array($data) && (count($data) > 0)){
            
            // get all our codes via data keys
            $shortCodeLabels = array_keys($data);

            // check if we already have a form and its in our list
            if((!self::$exists) && in_array($tag, $shortCodeLabels)){

                // switch
                self::$exists = true;

                // return output normally
                return $output;       

            }

        // we dont have data
        }else{

            // output as normal
            return $output;

        }

    }

    // MESSAGES: display response messages
    function displayMessage(){

        // GET data - simple sanitize
        $successMessage = isset($_GET["success"]) ? trim(htmlspecialchars($_GET["success"], ENT_NOQUOTES)) : null;
        $errorMessage = (isset($_GET["error"])) ? trim(htmlspecialchars($_GET["error"], ENT_NOQUOTES)) : null;
        
        // GET: success
        if(($successMessage !== null) && !empty($successMessage)){
            echo "<div class='notice notice-success is-dismissible'>";
                echo "<p>";
                    echo __($successMessage);
                echo "</p>";
            echo "</div>";
        } 
        
        // GET: error
        if(($errorMessage !== null) && !empty($errorMessage)){
            echo "<div class='notice notice-error is-dismissible'>";
                echo "<p>";
                    echo __($errorMessage);
                echo "</p>";
            echo "</div>";
        }
        
        // return
        return;

    }

}

// add action to init class
add_action('plugins_loaded', array('AdSurvey', "adSurveyInit"));

// setup activation hook
register_activation_hook(__FILE__, array("AdSurvey", "adSurveyActivate"));

// setup uninstall hook
register_uninstall_hook(__FILE__, array("AdSurvey", "adSurveyUninstall"));