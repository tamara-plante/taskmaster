<?php

/**
 * The parent class for all models
 */
class Model extends DB\SQL\Mapper
{
    // Database connection
    protected $db;

    /**
     * Parent class constructor
     * Connect to the database
     * @params string $table name of the database to interact with
     */
    public function __construct($table)
    {
        $f3 = Base::instance();
        
        $host = $f3->get("DBHOST") ?? "localhost";

        $this->db = new DB\SQL("mysql:host={$host};dbname={$f3->get('DBNAME')};port={$f3->get('DBPORT')}", 
            $f3->get("DBUSER"), 
            $f3->get("DBPASS"));

        // Create mapper of the given table
        parent::__construct($this->db, $table);
    }

    /**
     * Get user_id = {id} prefilled with logged in user for WHERE clauses.
     * @return string the filled user_id = {id}
     */
    public function getUserQuery()
    {
        return "user_id = " . $_SESSION["userId"];
    }

    /**
     * Get all entries from the table for the user.
     */
    public function getAll()
    {
        $this->load();
        return $this->query;
    }

    /**
     * Fetch a single value from the table, using id primary key.
     * @param int id row to fetch
     * @return Object database result
     */
    public function getById($id)
    {
        return $this->findone(["id = ?", $id]);
    }

    /**
     * Delete a row from the table using id primary key.
     * @param int id row to delete
     * @return int success feedback
     */
    public function deleteById($id)
    {
        $this->load(["id = ?", $id]); // Load the object
        return $this->erase();
    }
}