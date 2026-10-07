<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ScheduleConfigTest extends TestCase
{
    public function test_la_configuracion_de_horarios_es_valida(): void
    {
        $config = require config_path('schedules.php');

        $this->assertSame('08:00', $config['opening_time']);
        $this->assertSame('22:00', $config['closing_time']);
    }
}
