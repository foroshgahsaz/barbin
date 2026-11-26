<?php

namespace App\Jobs;

use App\Enums\DeliveryStatus;
use App\Models\Customer;
use App\Models\DeliveryLog;
use App\Models\SalesFile;
use App\Models\User;
use App\Services\Notifications\CustomerNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCustomerSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $salesFileId,
        public int $userId,
        public int $customerId,
        public bool $allowDuplicate = false,
    ) {}

    public function handle(CustomerNotificationService $notifier): void
    {
        $salesFile = SalesFile::query()->findOrFail($this->salesFileId);
        $user = User::query()->findOrFail($this->userId);
        $customer = Customer::query()->find($this->customerId);

        if (! $customer) {
            return;
        }

        $notifier->notifySms($user, $salesFile, [$customer->id], $this->allowDuplicate);
    }
}