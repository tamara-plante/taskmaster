<?php
// Parent controller class

class Controller
{
    protected $f3;
    protected $template;


    /**
     * Parent class constructor. Set up template.
     * Default page title.
     * @param Object $f3 the FatFree Framework instance
     */
    public function __construct($f3) 
    {
        $this->f3 = $f3;
        
        $this->set("pageTitle", $this->get("SITENAME"));
        $this->set("errors", []);
        $this->set("css", []);
        $this->set("scripts", []);
        

        // Setup template
        $this->template = new Template;

        // STart session so we can remember app selections
        session_start([
            'cookie_lifetime' => time() + (60 * 60 * 24 * 2) // 48hrs
        ]);
    }

    /**
     * Get value from the $f3 array.
     * @param string $key part of the $f3 instance
     * @return mixed the value that corresponds to the key
     */
    public function get($key)
    {
        return $this->f3->get($key);
    }
    /**
     * Set value in the $f3 array.
     * @param string $key the key to save to the $f3 instance
     * @param mixed $value value to set for the given $key
     */
    public function set($key, $value)
    {
        $this->f3->set($key, $value);
    }

    /**
     * Check if the user is still logged in
     * before route happens on every page except a select few.
     */
    public function beforeRoute()
    {
        $ignoreAlias =["home", "register", "contactUs"];

        if (!in_array($this->get("ALIAS"), $ignoreAlias) && !$this->isLoggedIn()) {
            $this->f3->reroute("@home");
        }
    }

    /**
     * When we need to return a json object to ajax.
     * @param array $array The array to output through echo
     */
    public function echoJSON($array)
    {
        echo json_encode($array, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Verify if there is a user that is logged in
     * through cookies.
     * @return bool true if user is logged in.
     */
    public function isLoggedIn()
    {
        return (isset($_SESSION["auth"]) && $_SESSION["auth"]);
    }

    /**
     * Log out the user
     */
    public function logout()
    {
        // Destroy all session variables.
        session_unset();
        // Destroy the session file from the server.
        session_destroy();

        $this->f3->reroute("@home");
    }

    /**
     * Take the default and format it with the new title = "title | default title"
     */
    public function setPageTitle($title)
    {
        $currentTitle = $this->get("pageTitle");
        $newTitle = $title;

        if ($currentTitle != "") {
            $newTitle .= " | " . $currentTitle;
        }

        $this->set("pageTitle", $newTitle);
    }

    /**
     * Checks if there's errors in $formErrors and set up the f3 errors property if there's any.
     * @param array $formErrors pass your errors array.
     * @return bool true if $forms has no errors and false if it does, as well as set the f3 errors property.
     */
    public function validateForm($formErrors)
    {
        if (!empty($formErrors)) {
            $this->set("errors", $formErrors);
            return false;
        }
        return true;
    }

    /**
     * Validate the min length of a given string.
     * @param string $str the string to evaluate
     * @param int $minLength the minimum length
     * @return bool true if string is within length
     */
    function validateMinLength($value, $minLength)
    {
        return (strlen($value) >= $minLength);
    }
    /**
     * Validate the max length of a given string.
     * @param string $str the string to evaluate
     * @param int $maxLength the maximum length
     * @return bool true if string is within length
     */
    function validateMaxLength($str, $maxLength)
    {
        return (strlen($str) <= $maxLength);
    }
}
