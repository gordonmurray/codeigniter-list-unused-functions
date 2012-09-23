<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Code_review extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * List all files and the functions within them
     */
    public function index()
    {
        ini_set('memory_limit', '2048M');

        $controller_files = $this->list_functions_in_files('controllers', $this->list_files_in_folder('controllers'));

        $model_files = $this->list_functions_in_files('models', $this->list_files_in_folder('models'));

        /*
         * Check to see if the functions inside of models are being used in controllers
         */
        $model_files = $this->function_usage_in_controllers($controller_files, $model_files);

        /*
         * Check to see if the functions inside the models are used withing other models, including itself
         */
        //$model_files = $this->function_usage_in_models($model_files);

        $data["controllers"] = $controller_files;

        $data["models"] = $model_files;

        $this->load->view('code_review', $data);
    }

    /**
     * Compile a list of files in a folder
     * @param string $folder
     */
    private function list_files_in_folder($folder)
    {
        $this->load->helper('directory');

        $files_array = directory_map('./application/' . $folder);

        $clean_files_array = array();

        foreach ($files_array as $file)
        {
            if ($file != 'code_review.php')
            {
                $clean_files_array[] = $file;
            }
        }

        return $clean_files_array;
    }

    /**
     * Open each files, compile a list of functions within
     * @param string $folder
     * @param array $array_of_filenames
     */
    private function list_functions_in_files($folder, $array_of_filenames)
    {
        $this->load->helper('file');
        $counter = 0;

        foreach ($array_of_filenames as $filename)
        {
            if ($filename != 'code_review.php') // no need to check itself
            {
                $file_content = preg_replace('/\s+/', ' ', read_file('./application/' . $folder . '/' . $filename));

                $array_of_filenames[$counter] = array(
                    'filename' => $filename,
                    'size' => get_file_info('./application/' . $folder . '/' . $filename),
                    'functions' => $this->discover_functions_in_content($file_content)
                );
            }
            $counter++;
        }

        return $array_of_filenames;
    }

    /**
     * Look through file content to find functions
     * @param string $folder
     * @param string $file_content
     */
    private function discover_functions_in_content($file_content)
    {
        $functions_found = array();
        $start_tag = "function";
        $end_tag = ")";

        while ((strstr($file_content, $start_tag)) == true)
        {
            $start_tag_position = strpos($file_content, $start_tag) + strlen($start_tag);
            $end_tag_position = strpos($file_content, $end_tag, $start_tag_position) + 1;
            $length = $end_tag_position - $start_tag_position;

            $functions_found[] = trim(substr($file_content, $start_tag_position, $length));

            /*
             * reduce the length of the $file_content for the next loop
             */
            $content_length = (strlen($file_content) - $end_tag_position);
            $file_content = substr($file_content, $end_tag_position, $content_length);
        }

        return $functions_found;
    }

    /**
     * Find any usage of the model functions in the controllers
     * @param array $controller_functions
     * @param array $model_functions
     * @return array $updated_model_functions
     */
    function function_usage_in_controllers($controller_functions, $model_functions)
    {
        $this->load->helper('file');

        $updated_model_functions = array();

        foreach ($model_functions as $model)
        {
            $model_name = str_replace(".php", "", $model['filename']);
            $model_functions = $model['functions'];
            $updated_functions_array = array();

            /*
             * loop through the functions in a model
             */
            foreach ($model_functions as $function)
            {
                $first_bracket = strpos($function, "(");
                $function = substr($function, 0, $first_bracket);
                $updated_functions_array[$function] = array('usage' => array());

                /*
                 * loop through the controllers
                 */
                foreach ($controller_functions as $controller)
                {
                    $controller_name = $controller["filename"];
                    $controller_content = read_file('./application/controllers/' . $controller["filename"]);

                    $function_call = $model_name . '->' . $function;

                    if (stristr($controller_content, $function_call) == TRUE)
                    {
                        $updated_functions_array[$function]['usage'][] = $function . '() used in the controller called ' . $controller_name;
                    }
                }
            }

            $updated_model_functions[] = array(
                'filename' => $model['filename'],
                'size' => $model['size'],
                'functions' => $model_functions,
                'functions_with_usage' => $updated_functions_array
            );
        }

        return $updated_model_functions;
    }

    /**
     * Find any function usage in other models
     * @param array $model_files
     */
    function function_usage_in_models($model_functions)
    {
        $this->load->helper('file');

        $updated_functions_array = array();
        $usage = array();

        /*
         * Look trough each model file
         */
        foreach ($model_functions as $model)
        {
            $original_model_name = str_replace(".php", "", $model['filename']);
            $model_internal_functions = $model['functions'];

            /*
             * loop through the functions in this model file
             */
            if (!empty($model_internal_functions))
            {
                foreach ($model_internal_functions as $function)
                {
                    $function = substr($function, 0, strpos($function, "("));
                    $usage = array();

                    /*
                     * Look through the models again to look for mentions
                     */
                    foreach ($model_functions as $model_to_look_inside)
                    {
                        $model_name = str_replace(".php", "", $model_to_look_inside['filename']);

                        $model_content = $this->file_content('models', $model_to_look_inside['filename']);

                        $function_call = $original_model_name . '->' . $function;
                        $internal_function_call = 'this->' . $function;

                        //echo "Looking for usage of '$function_call' or '$internal_function_call' inside " . $model_name . "<br />\n";

                        if (stristr($model_content, $function_call) == TRUE || stristr($model_content, $internal_function_call) == TRUE)
                        {
                            $usage[] = $function . '() used in the model called ' . $model_name;
                        }
                    }

                    $updated_usage = array_merge($model["functions_with_usage"][$function]["usage"], $usage);

                    $model["functions_with_usage"][$function]["usage"] = $updated_usage;

                    $updated_functions_array[] = $model;
                }
            }
        }

        //print_r($updated_functions_array);

        return $updated_functions_array;
    }

    /**
     * Read the content from a file
     * @param string $folder
     * @param string $filename
     * @return string $file_content
     */
    private function file_content($folder, $filename)
    {
        $this->load->helper('file');

        $file_content = preg_replace('/\s+/', ' ', read_file('./application/' . $folder . '/' . $filename));

        return $file_content;
    }

}

/* End of file Users.php */
/* Location: ./application/controllers/Users.php */