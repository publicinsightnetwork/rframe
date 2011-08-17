<?php
require_once "Rframe.php";

/**
 * Exception specific to an error in the Rframe.
 *
 * @version 0.1
 * @author ryancavis
 * @package default
 */
class Rframe_Exception extends Exception {


    /**
     * Create an exception, using a static code value found in the Rframe
     * class.
     *
     * @param int     $code
     * @param string  $message (optional)
     */
    public function __construct($code, $message=null) {
        if (!$message) {
            $message = Rframe::get_message($code);
        }
        parent::__construct($message, $code);
    }


    /**
     * True if this exception is still a success for the Rframe.
     *
     * @return boolean
     */
    public function is_success() {
        $code = $this->getCode();
        return $code >= Rframe::OKAY;
    }


}
