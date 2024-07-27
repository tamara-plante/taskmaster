<?php

class User extends Model
{
    public function __construct()
    {
        parent::__construct("user");
    }

    /**
     * Create the user entry and hash the password.
     * @return int id of the newly created user
     */
    public function createUser()
    {
        $this->copyfrom("POST");
        // Hash the password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        // Current date
        $this->reg_date = date("Y-m-d");
        
        $this->save();
        return $this->id;
    }

    /**
     * Get user by it's username.
     * @param string $username the user's username
     * @return object result
     */
    public function getUserByUsername($username)
    {
        return $this->findone(["username = ?", $username]);
    }


    // -------------------------------
   /**
     * Update the user entry with the given ID.
     * @param int $userId the user's ID
     * @param string|null $username the user's new username
     * @param string|null $password the user's new password
     * @return boolean true if the update was successful, false otherwise
     */
    public function updateUser($userId, $username = null, $password = null, $avatar = null)
        {
            // Load the user by ID
            $user = $this->getById($userId);

            if ($user) {
                if ($username) {
                    // Set the new username
                    $user->username = $username;
                }
                if ($password) {
                    // Hash the new password
                    $user->password = password_hash($password, PASSWORD_DEFAULT);
                }
                if ($avatar) {
                        // Set the new avatar
                        $user->avatar = $avatar;
                    }
                $user->save();
                return true;
            } else {
                return false;
            }
        }
    /**
     * Delete user by ID.
     * @param int $id user ID
     * @return void
     */
    public function deleteUser($id)
    {
        // Query all lists that belong to the user
        $lists = $this->db->exec("SELECT id FROM list WHERE user_id = ?", $id);

        foreach ($lists as $list) {
            // Query and delete all tasks that belong to each list
            $this->db->exec("DELETE FROM task WHERE list_id = ?", $list['id']);
        }

        // Delete all lists that belong to the user
        $this->db->exec("DELETE FROM list WHERE user_id = ?", $id);

        // Use the inherited deleteById method to delete the user
        $this->deleteById($id);
    }

}