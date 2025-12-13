<?php

namespace App\Events;

use App\Models\Brand;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrandChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;
    public int $brandId;
    public int $agencyId;
    public ?array $brand;

    /**
     * Create a new event instance.
     */
    public function __construct(string $action, int $agencyId, int $brandId, ?Brand $brand = null)
    {
        $this->action = $action;
        $this->agencyId = $agencyId;
        $this->brandId = $brandId;
        $this->brand = $brand ? $brand->toArray() : null;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agency.' . $this->agencyId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'brand.changed';
    }
}
