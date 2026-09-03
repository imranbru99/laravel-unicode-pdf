<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Tests\Feature;

use ImranDev\UnicodePdf\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function test_diagnose_command_runs_successfully(): void
    {
        $this->artisan('unicode-pdf:diagnose')
            ->expectsOutputToContain('Unicode PDF System Diagnostics')
            ->assertSuccessful();
    }

    public function test_fonts_command_runs_successfully(): void
    {
        $this->artisan('unicode-pdf:fonts')
            ->expectsOutputToContain('Unicode PDF Font Diagnostics')
            ->assertSuccessful();
    }

    public function test_font_list_command_runs_successfully(): void
    {
        $this->artisan('unicode-pdf:font:list')
            ->assertSuccessful();
    }

    public function test_clear_cache_command_runs_successfully(): void
    {
        $this->artisan('unicode-pdf:clear-cache')
            ->expectsOutputToContain('Unicode PDF cache cleared successfully.')
            ->assertSuccessful();
    }

    public function test_font_install_command_displays_guide(): void
    {
        $this->artisan('unicode-pdf:font:install', ['--font' => 'bengali'])
            ->expectsOutputToContain('Noto Sans Bengali')
            ->assertSuccessful();
    }
}
