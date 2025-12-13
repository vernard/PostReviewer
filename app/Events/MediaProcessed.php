<?php

namespace App\Events;

use App\Models\Media;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;
    public int $mediaId;
    public int $brandId;
    public ?array $media;

    /**
     * Create a new event instance.
     */
    public function __construct(string $action, int $brandId, int $mediaId, ?Media $media = null)
    {
        $this->action = $action;
        $this->brandId = $brandId;
        $this->mediaId = $mediaId;
        $this->media = $media ? $media->toArray() : null;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('brand.' . $this->brandId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'media.processed';
    }
}
