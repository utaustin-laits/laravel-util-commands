<?php

namespace Laits\Util\Commands;

use LonghornOpen\CanvasApi\CanvasApiClient;
use LonghornOpen\CanvasApi\CanvasApiException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;


class ReportCourseRetention extends Command{
    /**
     * The name and signature of the console command
     * @var string
     */

     protected $signature = 'laits:report-course-retention';

    /**
     * The description of the console command
     * @var string 
     */

     protected $description = 'Report Canvas course retention';

    /**
     * Execute the command 
     */

     public function handle()
     {
         // List the tables in the database
         $tables = $this->getTables();
     
         $this->info("Available tables:");
         $this->line(implode(", ", $tables));
     
         // Ask the user for the name of the table. If none is entered, choose the table named "courses"
         $tableName = $this->ask("What is the name of the table containing information about Canvas courses? (Leave blank if the table's name is 'courses')");
     
         if (!$tableName) {
             $tableName = "courses";
         }
         
        //  Checking if the name of the table in the array of all of the table's names
         if (!in_array($tableName, $tables)) {
             $this->error('The specified table name was not found.');
             return;
         }
     
         $columns = $this->listColumns($tableName);
     
         // List the columns in the table
         $this->info('Available columns in the table:');
         $this->line(implode(", ", $columns));
     
         // Ask the user for the name of the column that contains the course ID. If none is entered, choose the column named "canvas_course_id"
         $columnName = $this->ask("What is the name of the column that contains the course ID? (Leave blank if the column's name is 'canvas_course_id')");
        
        // If the user does not provide column name, assign canvas_course_id
        if (!$columnName){
            $columnName = 'canvas_course_id';
        }

        // Checking the validity of column name
        if (!in_array($columnName, $columns)) {
            $this->error('The specified column name was not found.');
            return;
        }

         // Get the list of course IDs. Iterate through the course IDs, use the API from Canvas, and check whether each course still exists
         $courseIDList = $this->getCourseID($tableName, $columnName);
        
        //  Starting Printing Out The Course ID that do not exist
         $this->info('List of Course IDs that do not exist:');
         foreach ($courseIDList as $course) {
             $courseID = $course->$columnName;
     
             // Check with the API if the course still exists
             if (!$this->checkCourseValid($courseID)) {
                 // Find the 'created_at' column for non-existent courses and assign its value to a variable if not null
                 $record = DB::table($tableName)->where($columnName, "=", $courseID)->first(['created_at']);
                 $created_at = $record ? $record->created_at : 'Not Found';
     
                 // Log the course ID and 'created_at' value
                 $this->info('Course ID: ' . $courseID . ' | Created at: ' . $created_at);
             }
         }
     }
     
   

    /**
     * Get a list of table names from the database.
     *
     * @return array An array of table names.
     */
     private function getTables()
     {
         $tables = Schema::getTables();
         return array_map(function($table) {
             return $table['name'];
         }, $tables);
     }

    /**
     * List Columns in the Table Name 
     * 
     * @param string $tableName The name of the table that contains the couseID
     * 
     * @return array An array of column names.
     */
     private function listColumns($tableName)
     {
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
        return $columns;
     }

    /**
     * Get Course IDs 
     *
     * @param string $tableName The name of the table that contains the courseIDs column. 
     * @param string $columnName The name of the column that contains the course IDs.
     * 
     * @return array An array of std class object in which every element has the column name specified: canvas_course_id
     */
     private function getCourseID($tableName, $columnName ){
        $courseIDs = DB::table($tableName)->select($columnName)-> get();
        return $courseIDs;
     }

     // Access Canvas to Check the Validity of the Course 
     /**
     * Access Canvas to Check the Validity of the Course 
     *
     * @param string @courseID the id of the course 
     * 
     * @return array An array of course id
     */
     private function checkCourseValid($courseID){
      
        $api_host = 'utexas.instructure.com';
       # Check if 'access_key' is present in environment variables
        if (getenv('access_key')) {
            $access_key = getenv('access_key');
        } else {
            $this->error("Failed to access the Canvas Course API: The access key is missing from the environment variable. Please ensure that 'access_key' is properly set up.");
            $access_key = null; // Or assign a default value if needed
        } 
        $api = new CanvasApiClient($api_host, $access_key);

        try{
            $course = $api->get("courses/" . $courseID);
            return True;
        }catch (CanvasApiException $e) {
            return False;
        }
     }


}   