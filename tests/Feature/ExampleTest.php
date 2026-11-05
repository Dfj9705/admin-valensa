<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_are_redirected_to_login_from_panel_root(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }
}
