<?php

/**
 * Connected to the view_user_task VIEW. Only for retrieving.
 */
class ViewUserTask extends Model
{
    private $byPriority = false;
    private $byDueDate = false;


    public function __construct()
    {
        parent::__construct("view_user_task", "id,list_id,due_date,content,priority,is_completed");
    }

    /**
     * Set the options for filtering getTasks
     */
    public function setOptions($byPriority, $byDueDate)
    {
        $this->byPriority = $byPriority;
        $this->byDueDate = $byDueDate;
    }

    /**
     * Get task by id and user_id, through the view
     * @param int $id the task id
     * @return object the task or false
     */
    public function getById($id) 
    {
        return $this->findone(["id = ? AND " . $this->getUserQuery(), $id]);
    }

    /**
     * Get the first task by list_id and user_id, through the view
     * @param int $id the list id
     * @return object the first task or false
     */
    public function getByListId($listId)
    {
        return $this->findone(["list_id = ? AND " . $this->getUserQuery(), $listId]);
    }

    
    /**
     * Get the number of active tasks for the list.
     * @param int $listId the given list id
     * @return int the active task count for the list
     */
    public function countTasksActive($listId)
    {
        return $this->count(["list_id = ? AND is_completed = 0 AND " . $this->getUserQuery(), $listId]);
    }

    
    /**
     * Get all tasks for the given $listId.
     * @param int $listId the given list id
     * @return object the query results
     */
    public function getTasksAll($listId)
    {
        return $this->getTasks($listId, "list_id = ? AND " . $this->getUserQuery());
    }

    /**
     * Get active tasks for the given $listId.
     * @param int $listId the given list id
     * @return object the query results
     */
    public function getTasksActive($listId)
    {
        return $this->getTasks($listId, "list_id = ? AND is_completed = 0 AND " . $this->getUserQuery());
    }

    /**
     * Get completed tasks for the given $listId.
     * @param int $listId the given list id
     * @param bool $byPriority order by priority
     * @return object the query results
     */
    public function getTasksCompleted($listId)
    {
        return $this->getTasks($listId, "list_id = ? AND is_completed = 1 AND " . $this->getUserQuery());
    }

    
    /**
     * Get tasks based on the sql statement and 
     * ordered by priority if requested.
     * @param int $listId the given list id
     * @param string $sql the filter string
     * @return object the query results
     */
    private function getTasks($listId, $sql)
    {
        $options = [];

        if ($this->byPriority) {
            array_push($options, "is_completed < 1 DESC");
            array_push($options, "priority > 0 DESC");
        }
        if ($this->byDueDate) {
            array_push($options, "due_date DESC");
        }
        // Create the order by string
        $sqlOptions = (!empty($options)) ? "ORDER BY " . implode(", ", $options) : "";

        $this->load([$sql . " " . $sqlOptions, $listId]);
        return $this->query;
    }
}