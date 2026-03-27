<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegistrationRoutesDisabledTest extends TestCase
{
  public function test_registration_page_is_not_exposed(): void
  {
    $this->get('/register')->assertNotFound();
  }

  public function test_registration_submission_is_not_exposed(): void
  {
    $this->post('/register', [
      'username' => 'legacy-user',
      'email' => 'legacy@example.com',
      'password' => 'Secret123!',
    ])->assertNotFound();
  }
}
