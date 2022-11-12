<?php

beforeEach(function () {
    $this->user = createUser();
    $this->admin = createUser(isAdmin: true);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
