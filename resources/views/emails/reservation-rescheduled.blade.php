{{-- Contenido del email que se envía cuando un gestor modifica una reserva --}}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body style="font-family: Arial, sans-serif; color: #333;">
        <h2>Hola {{ $reservation->user->name }},</h2>
        
        <h3>Su reserva ha cambiado de horario</h3>

        <p>La reserva de la pista <strong>{{ $reservation->court->name }}</strong> ha sido reprogramada.</p>

        <p>
            <strong>Horario anterior:</strong> {{ $oldStartTime->format('d/m/Y H:i') }}<br>
            <strong>Nuevo horario:</strong> {{ $reservation->start_time->format('d/m/Y H:i') }} - {{ $reservation->end_time->format('H:i') }}
        </p>

        <p>Si tiene cualquier duda, contacte con nosotros.</p>

        <p>Gracias por todo y disculpe las molestias,<br>{{ config('app.name') }}</p>
    </body>
</html>