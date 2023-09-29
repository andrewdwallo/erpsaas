<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyDefaultUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Model $record;

    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $record, array $data)
    {
        $this->record = $record;
        $this->data = $data;
    }
}
