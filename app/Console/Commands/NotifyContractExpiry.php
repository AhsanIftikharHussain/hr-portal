<?php

namespace App\Console\Commands;

use App\ContractStatus;
use App\Models\ContractExpiryNotificationDelivery;
use App\Models\EmployeeContract;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ContractExpiringSoon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

#[Signature('hr:notify-contract-expiry')]
#[Description('Notify active HR administrators about contracts expiring within 30 days')]
class NotifyContractExpiry extends Command
{
    public function handle(): int
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('slug', [Role::SUPER_ADMIN, Role::HR_ADMIN]))
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'is_active']);

        if ($recipients->isEmpty()) {
            $this->components->info('No active Super Admin or HR Admin recipients were found.');

            return self::SUCCESS;
        }

        $sent = 0;
        $duplicates = 0;
        $errors = 0;
        $contracts = 0;
        $businessToday = CarbonImmutable::now(config('app.business_timezone'))->startOfDay();

        EmployeeContract::query()
            ->select(['id', 'employee_id', 'contract_type', 'end_date', 'status'])
            ->with('employee:id,full_name,deleted_at')
            ->whereIn('status', ContractStatus::expiringStatuses())
            ->whereBetween('end_date', [$businessToday, $businessToday->addDays(30)])
            ->whereHas('employee', fn (Builder $query) => $query->whereNull('employees.deleted_at'))
            ->lazyById(100)
            ->each(function (EmployeeContract $contract) use ($recipients, &$sent, &$duplicates, &$errors, &$contracts): void {
                $contracts++;

                foreach ($recipients as $recipient) {
                    try {
                        $deliveryId = DB::transaction(function () use ($contract, $recipient): int {
                            $delivery = ContractExpiryNotificationDelivery::query()->firstOrCreate([
                                'user_id' => $recipient->id,
                                'employee_contract_id' => $contract->id,
                                'contract_end_date' => $contract->end_date,
                            ]);

                            $delivery = ContractExpiryNotificationDelivery::query()
                                ->lockForUpdate()
                                ->findOrFail($delivery->id);

                            if ($delivery->database_notified_at === null) {
                                Notification::sendNow($recipient, new ContractExpiringSoon($contract), ['database']);
                                $delivery->update(['database_notified_at' => now()]);
                            }

                            return $delivery->id;
                        });

                        $delivered = DB::transaction(function () use ($contract, $recipient, $deliveryId): bool {
                            $delivery = ContractExpiryNotificationDelivery::query()
                                ->lockForUpdate()
                                ->findOrFail($deliveryId);

                            if ($delivery->mail_notified_at !== null) {
                                return false;
                            }

                            Notification::sendNow($recipient, new ContractExpiringSoon($contract), ['mail']);
                            $delivery->update(['mail_notified_at' => now()]);

                            return true;
                        });

                        if ($delivered) {
                            $sent++;
                        } else {
                            $duplicates++;
                        }
                    } catch (UniqueConstraintViolationException) {
                        $duplicates++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $errors++;
                        $this->components->error("Notification failed for contract {$contract->id} and user {$recipient->id}.");
                    }
                }
            });

        $this->components->info("Checked {$contracts} expiring contract(s); sent {$sent}, skipped {$duplicates} duplicate(s), encountered {$errors} error(s).");

        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }
}
