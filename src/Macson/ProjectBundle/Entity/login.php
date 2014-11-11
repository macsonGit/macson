<?php

namespace Macson\TProjectBundle\Entity;

class Task
{
    protected $user;

    public function getTask()
    {
        return $this->task;
    }

    public function setTask($task)
    {
        $this->task = $task;
    }

    public function getuser()
    {
        return $this->user;
    }

}