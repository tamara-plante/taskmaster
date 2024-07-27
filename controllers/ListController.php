<?php

class ListController extends Controller
{
    private $model;


    public function __construct($f3)
    {
        parent::__construct($f3);
        $this->model = new Lists();
    }

    /**
     * Create a new list.
     */
    public function create()
    {
        // Sanitize form inputs
        $this->set("POST", [
            "title" => trim($this->get("POST.title")),
            "user_id" => $_SESSION["userId"],
        ]);

        if ($this->isFormValid()) {

            $listId = $this->model->create();
            $this->f3->reroute("@getList(@id={$listId})");
        }
        $this->f3->reroute("@app");
    }

    /**
     * Edit the list title.
     * @return string the new title or "" if it failed.
     */
    public function editTitle()
    {
        $listId = $_SESSION["listId"];

        $this->set("POST", [
            "title" => trim($this->get("POST.title")),
        ]);

        if (!$this->isFormValid()) {
            $this->echoJSON(["error" => $this->get("errors")[0]]);
            return;
        }

        // If the form is valid, try to update, it will return the title or error
        $this->echoJSON(["title" => $this->model->updateTitle($listId)]);
    }

    /**
     * Update the order of a list element.
     */
    public function updateListOrder()
    {
        $this->model->updateListOrder($this->get("PARAMS.id"), $this->get("PARAMS.order"));
    }

    /**
     * Delete a list and its tasks.
     */
    public function delete()
    {
        $listId = $this->get("PARAMS.id");
        try {
            $isListDeleted = $this->model->delete($listId);
        }
        // Invalid list id
        catch (Exception $e) {
            $isListDeleted = false;
        }

        // Make sure the selected list is unset if it was deleted
        if ($isListDeleted && ($_SESSION["listId"] === $listId)) {
            $_SESSION["listId"] = null;
        }
        echo $isListDeleted;
    }

    /**
     * Validate the POST form information.
     * @return bool true if the form is valid
     */
    private function isFormValid()
    {
        $errors = [];

        if ($this->get("POST.title") == "") {
            array_push($errors, "List name is required.");
        }
        else if (!$this->validateMaxLength($this->get("POST.title"), 30)) {
            array_push($errors, "Title should be 30 characters max.");
        }

        return $this->validateForm($errors);
    }
}