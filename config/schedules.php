<?php

// Horarios de inicio y cierre de las pistas deportivas
$opening_time = '08:00';
$closing_time = '22:00';

// El horario de inicio no puede ser posterior al de cierre
if ($opening_time >= $closing_time) {
    throw new \InvalidArgumentException('La hora de cierre debe ser posterior a la de apertura.');
}

// Las horas de inicio y cierre deben coincidir con una hora en punto (08:00, 09:00, 10:00, ...)
if (!preg_match('/^\d{2}:00$/', $opening_time) || !preg_match('/^\d{2}:00$/', $closing_time)) {
    throw new \InvalidArgumentException('La hora de apertura y cierre debe coincidir con la hora en punto.');
}

return [
    'opening_time' => $opening_time,
    'closing_time' => $closing_time,
];