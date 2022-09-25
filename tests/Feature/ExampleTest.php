<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_homepage_contains_some_characters()
    {
        // Arrange
        $characters = ['Laravel', 'Laracasts'];

        // Act
        $response = $this->get('/');

        // Assert
        foreach ($characters as $character) {
            $response->assertSee($character);
        }
    }
}
