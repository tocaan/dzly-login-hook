<?php

namespace DzlyLoginHook\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use DzlyLoginHook\Services\WhatsappAuthHandler;

class OtpLoginStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        protected string $serialNumber,
        protected string $status,
        protected ?string $message = null,
        protected ?string $name = null,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel(WhatsappAuthHandler::otpChannelName($this->serialNumber));
    }

    public function broadcastAs(): string
    {
        return 'otp.status';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'name' => $this->name,
        ];
    }
}