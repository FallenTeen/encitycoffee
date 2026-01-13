<?php

it('redirects home to dashboard or login', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
});
