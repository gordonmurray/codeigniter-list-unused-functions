<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Code_review extends CI_Controller {

    function __construct() {
        parent::__construct();
    }

    private $func_exclude = array('__construct');
    private $file_exclude = array('code_review.php');

    public function index() {

        ini_set('memory_limit', '2048M');
        $controller_files = array();
        $model_files = array();
        $view_files = array();
        $javascript_files = array();

        $controller_files = $this->list_functions_in_files('application/controllers/', $this->list_files_in_folder('application/controllers/'), $controller_files);
        $model_files = $this->list_functions_in_files('application/models/', $this->list_files_in_folder('application/models/'), $model_files);

        $view_files = $this->list_functions_in_files('application/views/', $this->list_files_in_folder('application/views/'), $view_files);
        $javascript_files = $this->list_functions_in_files('public/js/', $this->list_files_in_folder('public/js/'), $javascript_files);


        /*
         * Check to see if the functions inside of models are being used in controller and model files
         */
        $merged_files = array_merge($controller_files,$model_files);
        $model_files = $this->function_usage_in_group_of_files($model_files, $merged_files, 'model');


        /*
         * Check to see if the functions inside of controller are being used in view, js and controller files
         */
        $merged_files = array_merge($view_files, $javascript_files, $controller_files);
        $controller_files = $this->function_usage_in_group_of_files($controller_files, $merged_files, 'controller');

        $data["controllers"] = $controller_files;
        $data["models"] = $model_files;

        $this->load->helper('number_helper');
        $this->load->view('code_review', $data);
    }

    /**
     * Compile a list of files in a folder
     * @param string $folder
     */
    private function list_files_in_folder($folder) {
        $this->load->helper('directory');
        $files_array = directory_map('./' . $folder);
        return $files_array;
    }

    /**
     * Open each files, compile a list of functions within
     * @param string $folder
     * @param array $array_of_filenames
     * @param $new_array
     */
    private function list_functions_in_files($folder, $array_of_filenames, &$new_array) {
        $this->load->helper('file');
        foreach ($array_of_filenames as $key => $filename) {
            if (is_array($filename)){
                $this->list_functions_in_files($folder . $key . '/', $filename, $new_array);
            }
            else{
                if ( !in_array($filename, $this->file_exclude) ) {
                    $file_content = read_file('./' . $folder . $filename);
                    $new_array[] = array(
                        'filename' => $filename,
                        'info' => get_file_info('./' . $folder . $filename),
                        'functions' => $this->discover_functions_in_content($file_content)
                    );
                }
            }
        }
        return $new_array;
    }

    // --------------------------------------------------------------------
    /**
     * Look through file content to find functions
     * @param string $file_content
     */
    private function discover_functions_in_content($file_content) {
        $functions_found = array();

        preg_match_all('/function\s+[A-z0-9_]*\(/', $file_content, $matches);

        if (!empty($matches[0])) {

            foreach ($matches[0] as $key => $match) {
                $func_name = str_replace('function ', '',$match);
                $func_name = trim(str_replace('(', '',$func_name));

                if ( !in_array($func_name, $this->func_exclude) ){
                    $functions_found[] = $func_name;
                }
            }
        }
        return $functions_found;
    }

    // --------------------------------------------------------------------
    /**
     * Find $files_source array of files with methods being used in $files_target array of files
     * @param array $files_target
     * @param array $files_source
     * @return array files_source array
     */
    function function_usage_in_group_of_files($files_source, $files_target, $whose_funcions) {
        $this->load->helper('file');
        $updated_files_source = array();
        foreach ($files_source as $file_source) {
            $file_source_name = str_replace(".php", "", $file_source['filename']);
            $file_source_functions = $file_source['functions'];
            /*
             * loop through the source functions
             */
            foreach ($file_source_functions as $function_source_name) {
                $usage = array();
                /*
                 * loop through the target files
                 */
                foreach ($files_target as $file_target) {
                    $file_target_name = $file_target["filename"];
                    $file_target_content = read_file($file_target["info"]["server_path"]);
                    if ($whose_funcions == 'model'){
                        $pattern = '/('.$file_source_name.'|this)->'.$function_source_name.'/i';
                        preg_match_all($pattern, $file_target_content, $matches);
                    }
                    elseif($whose_funcions == 'controller'){
                        $pattern = '/('.$file_source_name.'(\/|->)'.$function_source_name.')|(this->'.$function_source_name.')/i';
                        preg_match_all($pattern, $file_target_content, $matches);
                    }

                    if (!empty($matches[0])){
                        $usage[] = $function_source_name . ' used in the file ' . $file_target["info"]["server_path"];
                    }

                }

                if (!isset($file_source["functions_with_usage"][$function_source_name]["usage"])){
                    $file_source["functions_with_usage"][$function_source_name]["usage"] = array();
                }
                $file_source["functions_with_usage"][$function_source_name]["usage"] = array_merge($file_source["functions_with_usage"][$function_source_name]["usage"], $usage);
            }
            $updated_files_source[] = $file_source;
        }
        return $updated_files_source;
    }

    function array_value_recursive(array $arr){
        $val = array();
        array_walk_recursive($arr, function($v, $k) use(&$val){
            array_push($val, $v);
        });
        return count($val) > 1 ? $val : array_pop($val);
    }

}
