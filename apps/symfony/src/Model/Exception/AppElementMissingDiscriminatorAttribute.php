<?php

namespace App\Model\Exception;

/**
 * Custom exception to better handle failures, logging, translations of messages...
 */
class AppElementMissingDiscriminatorAttribute extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct();
        $this->message = sprintf(
            '-- HTML element %s does not have any discriminator attribute. Add one and retry.',
            $message
        );
    }
}