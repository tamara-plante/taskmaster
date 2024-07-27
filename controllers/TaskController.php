<?php

class TaskController extends Controller
{
    private $model;


    public function __construct($f3)
    {
        parent::__construct($f3);
        $this->model = new Task();
    }

    /**
     * Get a task data.
     * @return json response
     */
    public function getTask()
    {
        $task = $this->model->getById($this->get("PARAMS.id"));
        $this->echoJSON(($task) ? $task->cast() : "{}");
    }

    /**
     * Create a task and return the new data.
     * @return json response
     */
    public function createTask()
    {
        if (isset($_SESSION["listId"])) {
            // Sanitize form inputs
            $this->set("POST", [
                "list_id" => $_SESSION["listId"],
                "content" => trim($this->get("POST.content")),
                "due_date" => trim($this->get("POST.due_date")),
                "priority" => (int) $this->get("POST.priority"),
            ]);

            if ($this->isFormValid()) {

                // Save the task
                $taskId = $this->model->create();
                $task = $this->model->getById($taskId);
                $taskArray = $task->cast();

                // Build the baseTask url for the li.
                $taskArray["task_url"] = $this->getTaskUrl($taskArray["id"]);

                return $this->echoJSON($taskArray);
            }
        }
        echo "{}";
    }

    /**
     * Update the task information and return the new data.
     * @return json response
     */
    public function updateTask()
    {
        if (isset($_SESSION["listId"]) && array_key_exists("id", $this->get("POST"))) {

            $taskId = $this->get("POST.id");
            $this->set("POST", [
                "list_id" => $_SESSION["listId"],
                "content" => trim($this->get("POST.content")),
                "due_date" => trim($this->get("POST.due_date")),
                "priority" => (int) $this->get("POST.priority"),
            ]);

            if ($this->isFormValid()) {
                try {
                    $task = $this->model->updateTask($taskId);
                }
                catch (Exception $e) {
                    echo $e;
                    return;
                }
                // Send back json response of the task object
                $taskArray = $task->cast();
                $taskArray["task_url"] = $this->getTaskUrl($taskArray["id"]);

                return $this->echoJSON($taskArray);
            }
        }
        echo "{}";
    }

    /**
     * Toggle the is_completed status.
     * @return echo if succesful or error
     */
    public function toggleTask()
    {
        try {
            echo $this->model->toggleTask($this->get("PARAMS.id"));
        }
        catch (Exception $e) {
            echo $e;
        }
    }

    /**
     * Delete a task.
     * @return echo if succesful or error
     */
    public function deleteTask()
    {
        try {
            echo $this->model->deleteById($this->get("PARAMS.id")); 
        }
        // Invalid id
        catch (Exception $e) {
            echo $e;
        }
    }
    
    /**
     * Validate if the POST form is valid.
     * content and list_id validation.
     * @return bool true if the form has no errors
     */
    private function isFormValid()
    {
        $errors = [];

        if ($this->get("POST.content") == "") {
            array_push($errors, "Task content is required.");
        }
        if ($this->get("POST.list_id") == "") {
            array_push($errors, "List Id is required.");
        }

        return $this->validateForm($errors);
    }

    /**
     * Construct the @getTask url internally to return with json object.
     * @param int $taskId the task id
     */
    private function getTaskUrl($taskId)
    {
        return $this->get("BASE") . $this->f3->alias("getTask", "id = " . $taskId);
    }
}