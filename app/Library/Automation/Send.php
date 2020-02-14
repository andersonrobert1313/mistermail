<?php

namespace Acelle\Library\Automation;

use Acelle\Model\Email;
use Acelle\Jobs\DeliverEmail;

class Send extends Action
{
    public function execute()
    {
        // IMPORTANT
        // If this is the latest also the last action of the workflow
        // no more execute, just return true
        if (!is_null($this->last_executed)) {
            $this->autoTrigger->logger()->info('Send action already executed');

            return true;
        }

        sleep(1); // to avoid same date/time with previous wait, wrong timeline order
        $email = $this->getEmail();

        if (config('app.demo') == true) {
            // do nothing
        } else {
            dispatch(new DeliverEmail($email, $this->autoTrigger->subscriber));
        }

        $this->autoTrigger->logger()->info(sprintf('Send email entitled "%s" to "%s", queued', $email->subject, $this->autoTrigger->subscriber->email));

        $this->recordLastExecutedTime();
        $this->evaluationResult = true;

        return true;
    }

    public function getEmail()
    {
        return Email::findByUid($this->options['email_uid']);
    }

    public function getActionDescription()
    {
        $nameOrEmail = $this->autoTrigger->subscriber->getFullNameOrEmail();

        return sprintf('User %s receives email entitled "%s"', $nameOrEmail, $this->getEmail()->subject);
    }
}
