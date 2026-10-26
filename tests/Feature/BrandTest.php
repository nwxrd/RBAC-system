<?php

test('the welcome page shows the portal name without the starter kit text', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertDontSee('Laravel has an incredibly rich ecosystem')
        ->assertDontSee('https://laravel.com/docs');
});

test('the login page uses the configured favicon', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('href="'.config('brand.favicon').'"', false)
        ->assertDontSee('href="/favicon.ico"', false)
        ->assertDontSee('href="/favicon.svg"', false)
        ->assertDontSee('apple-touch-icon', false);
});
