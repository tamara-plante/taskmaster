<?php

class Lists extends Model
{
    public function __construct()
    {
        parent::__construct("list");
    }

    /**
     * Get list filtered by id and user_id.
     * @param int $id the list id
     * @return object the list
     */
    public function getById($id)
    {
        return $this->findone(["id = ? AND " . $this->getUserQuery(), $id]);
    }

    /**
     * Get the first list for the user.
     * @return object the first list found based on list_order
     */
    public function getFirstList()
    {
        return $this->findone([$this->getUserQuery() . " ORDER BY list_order"]);
    }

    /**
     * Get all lists by list_order for the user.
     * @return array returned list that matched the criterias
     */
    public function getAll()
    {
        $this->load([$this->getUserQuery() . " ORDER BY list_order"]);
        return $this->query;
    }

    public function getRecent()
    {
        $this->load([$this->getUserQuery() . " ORDER BY last_updated DESC LIMIT 3"]);
        return $this->query;
    }

    /**
     * Create the list entry.
     * @return int id of the newly created list
     */
    public function create()
    {
        $this->copyfrom("POST");
        // Count based on total list the user has
        $this->list_order = $this->count([$this->getUserQuery()]);
        
        $this->save();
        return $this->id;
    }
    /**
     * Create the default list.
     * @return int id of the newly created list.
     */
    public function createDefault()
    {
        // Create a default list
        $this->copyfrom([
            "title" => "To Do",
            "user_id" => $_SESSION["userId"],
            "list_order" => 0,
        ]);

        $this->save();
        return $this->id;
    }

    /**
     * Update the list title.
     * @param int $id the list id
     * @return string the title
     */
    public function updateTitle($id)
    {
        $this->load(["id = ? AND " . $this->getUserQuery(), $id]);
        $this->copyfrom("POST");

        $this->update();
        return $this->title;
    }

    /**
     * Update the order of the lists.
     * @param int $id the list id that changed
     * @param int $newOrder the list's new position
     */
    public function updateListOrder($id, $newOrder)
    {
        $this->load(["id = ? AND " . $this->getUserQuery(), $id]);
        $currentOrder = $this->list_order;

        // Solution based on 
        // https://dba.stackexchange.com/questions/36875/arbitrarily-ordering-records-in-a-table
        $filters = ($currentOrder > $newOrder) ? [1, $newOrder, $currentOrder] : [-1, $currentOrder, $newOrder];
        // Update all effected list_order in one call.
        $this->db->exec("UPDATE list SET list_order = list_order + ? WHERE list_order >= ? AND list_order <= ? AND " . $this->getUserQuery(), $filters);

        $this->load(["id = ?", $id]);
        $this->list_order = $newOrder;

        $this->update();
    }

    public function updateTimeStamp($id)
    {
        $this->load(["id = ? AND " . $this->getUserQuery(), $id]);
        $this->last_updated = time();

        $this->update();
    }

    /**
     * Delete the list entry. Make sure tasks are deleted first.
     * @param int $id the list to delete
     * @throws Exception if the list id is invalid
     * @return bool if the delete was succesful
     */
    public function delete($id)
    {
        $this->load(["id = ? AND " . $this->getUserQuery(), $id]);

        // Invalid list id
        if ($this->dry()) {
            throw new Exception("Invalid list id");
        }
        // Delete the tasks
        $this->db->exec("DELETE FROM task WHERE list_id = ?", $id);
        // Delete the list
        $isDeleted = $this->erase();

        // Update the list_order of every list after the list we are deleting
        if ($isDeleted) {
            $this->db->exec("UPDATE list SET list_order = list_order - 1 WHERE list_order > ? AND " . $this->getUserQuery(), $this->list_order);
        }

        return $isDeleted;
    }
}
