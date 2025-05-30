<?php

namespace App\Events;

use App\Models\AflApiResponse;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AflDataUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The AFL data that should be broadcast.
     *
     * @var \App\Models\AflApiResponse
     */
    public $aflData;

    /**
     * Create a new event instance.
     */
    public function __construct(AflApiResponse $aflData)
    {
        $this->aflData = $aflData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sports.live.afl'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'afl.update';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        // Instead of sending the full data, send a reference and metadata
        return [
            'id' => $this->aflData->id,
            'uri' => $this->aflData->uri,
            'data_available' => $this->aflData->response_code === 200,
            'data_size' => is_array($this->aflData->response) ? count($this->aflData->response) : 'unknown',
            'updated_at' => $this->aflData->updated_at->toIso8601String(),
            'api_call_time' => $this->aflData->response_time,
            'response_code' => $this->aflData->response_code,
            'request_id' => $this->aflData->request_id,
            'fetch' => config('app.url') . '/api/v1/live/afl'
            // Include a small sample of the data if possible
            // 'data_preview' => $this->getDataPreview($this->aflData->response),
        ];
    }

    /**
     * Get a preview of the data (first few items)
     *
     * @param array|null $data
     * @return array|null
     */
    protected function getDataPreview($data)
    {
        if (!is_array($data)) {
            return null;
        }

        // Take just the first item or a few keys as a preview
        if (isset($data[0]) && is_array($data[0])) {
            // It's a numeric array, take first item
            return array_slice($data, 0, 1);
        } elseif (is_array($data)) {
            // It's an associative array, take a few important keys
            $preview = [];
            $keysToInclude = ['id', 'name', 'title', 'type', 'status'];

            foreach ($keysToInclude as $key) {
                if (isset($data[$key])) {
                    $preview[$key] = $data[$key];
                }
            }

            return $preview ?: array_slice($data, 0, 3); // Take first 3 keys if no important keys found
        }

        return null;
    }
}
