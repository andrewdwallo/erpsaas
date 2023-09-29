<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyDefaultEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Model $model;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}
