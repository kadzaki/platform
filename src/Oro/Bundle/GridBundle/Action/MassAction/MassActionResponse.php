<?php

namespace Oro\Bundle\GridBundle\Action\MassAction;

class MassActionResponse implements MassActionResponseInterface
{
    /**
     * @var boolean
     */
    protected $successful;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param array $options
     */

    /**
     * @param boolean $successful
     * @param string $message
     * @param array $options
     */
    public function __construct($successful, $message, array $options = array())
    {
        $this->successful = $successful;
        $this->message    = $message;
        $this->options    = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->successful;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
