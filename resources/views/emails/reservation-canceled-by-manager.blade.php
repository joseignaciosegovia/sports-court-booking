{{-- Contenido del email que se envía cuando un gestor cancela una reserva --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family: Arial, sans-serif; color: #333;">
        <h2>Hola {{ $reservation->user->name }},</h2>

        <p>Su reserva ha sido cancelada</p>

        <p>Lamentamos informarle de que su reserva de la pista <strong>{{ $reservation->court->name }}</strong> del {{ $reservation->start_time->format('d/m/Y H:i') }} ha sido cancelada.</p>

        <p><strong>Motivo:</strong> {{ $reason }}</p>

        @if ($reservation->payment_status === 'refunded')
            <p>Se le reembolsará el pago de la reserva en los próximos días.</p>
        @else
            <p>Como no pagó la reserva, no es necesario hacer ninguna devolución.</p>
        @endif

        <p>Disculpe las molestias.</p>

        <p>Gracias, <br>{{ config('app.name') }}</p>
    </body>
</html>