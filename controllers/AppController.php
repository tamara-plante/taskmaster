<?php

class AppController extends Controller
{
    private $lists;
    private $task;


    public function __construct($f3)
    {
        parent::__construct($f3);
        $this->lists = new Lists();
        $this->task = new ViewUserTask();
    }

    public function renderSetup()
    {
        // Setup the css needed
        $this->set("css", [
            "https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css", 
            "css/app.css", 
            "css/app-tasks.css",
        ]);
        $this->set("scripts", [
            "https://code.jquery.com/ui/1.13.3/jquery-ui.js",
            "js/app.js",
        ]);
        $this->set("container", "app-container");
        $this->set("username", $_SESSION["username"]);
        $this->set("avatar", $_SESSION["avatar"] ?? "public/images/avatar.png");
    }

    /**
     * Render the app page.
     */
    public function render()
    {
        // Get the lists
        $currentLists = $this->lists->getAll();

        // If the user has no list, create the default one.
        if (empty($currentLists)) {

            $_SESSION["listId"] = null;
            $this->lists->createDefault();
            $currentLists = $this->lists->getAll();
        }

        $this->set("lists", $currentLists);
        $this->set("listsRecent", $this->lists->getRecent());
        $this->set("mode", (isset($_SESSION["mode"])) ? $_SESSION["mode"] : "all");
        $this->set("byPriority", (isset($_SESSION["byPriority"])) ? $_SESSION["byPriority"] : false);
        $this->set("byDueDate", (isset($_SESSION["byDueDate"])) ? $_SESSION["byDueDate"] : false);
        $this->set("tasks", []);

        // Load from the session
        if (isset($_SESSION["listId"])) {
            // Validate the list exists
            $list = $this->lists->getById($_SESSION["listId"]);
            // Load the last list
            if ($list) {
                $this->loadList($list);
            }
            // Cached session list is invalid, load the first list.
            else {
                $this->loadFirstList();
            }
        }
        // Load the first list
        else {
            $this->loadFirstList();
        }

        $this->renderSetup();
        echo $this->template->render("app.html");
    }

    /**
     * Remember the selected mode (all, active, completed)
     */
    public function setMode()
    {
        $mode = $this->get("PARAMS.mode");
        switch($mode) {
            case "all":
            case "active":
            case "completed":
                $_SESSION["mode"] = $mode;
                break;
        }
        $this->f3->reroute("@app");
    }

    /**
     * Remember the selected list in the session.
     */
    public function setList()
    {
        // Validate the list id exists
        $list = $this->lists->getById($this->get("PARAMS.id"));
        if ($list) {

            $_SESSION["listId"] = $this->get("PARAMS.id");
            $this->f3->reroute("@app#l-" . $this->get("PARAMS.id"));
        }

        $this->f3->reroute("@app");
    }

    /**
     * Remember the order by priority state.
     */
    public function setByPriority()
    {
        $_SESSION["byPriority"] = isset($_SESSION["byPriority"]) ? !$_SESSION["byPriority"] : true;
        $this->f3->reroute("@app");
    }

    /**
     * Remember the order by due date state.
     */
    public function setByDueDate()
    {
        $_SESSION["byDueDate"] = isset($_SESSION["byDueDate"]) ? !$_SESSION["byDueDate"] : true;
        $this->f3->reroute("@app");
    }

    /**
     * Set the list to the first list found by list_order
     * and load it.
     */
    private function loadFirstList()
    {
        $list = $this->lists->getFirstList();
        $_SESSION["listId"] = $list["id"];
        $this->loadList($list);
    }

    /**
     * Load the selected list and it's tasks
     * @param object $list the list object from the database
     */
    private function loadList($list)
    {
        $listId = $list["id"];
        $this->set("selectedTitle", $list["title"]);
        $this->set("selectedId", $listId);
        
        $mode = $this->get("mode");
        $this->task->setOptions($this->get("byPriority"), $this->get("byDueDate"));

        switch($mode) {
            case "all":
                $taskList = $this->task->getTasksAll($listId);
                break;
            case "active":
                $taskList = $this->task->getTasksActive($listId);
                break;
            case "completed":
                $taskList = $this->task->getTasksCompleted($listId);
                break;
            default:
                return;
        }
        $this->set("selectedCount", $this->task->countTasksActive($listId));
        $this->set("tasks", $taskList);
    }
}