<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Common\ReservationCancellationService;

class CancelExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela las reservas pendientes de pago cuyo plazo ha expirado';

    /**
     * Execute the console command.
     */
    public function handle(ReservationCancellationService $reservationService): int
    {
        $count = $reservationService->cancelExpiredReservations();

        $this->info("{$count} reservas expiradas canceladas.");

        return self::SUCCESS;
    }
}
