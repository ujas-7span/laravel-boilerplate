<?php

test('root url loads welcome page successfully', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('legacy tool urls redirect properly to developer suite prefix', function (string $legacyPath, string $targetPath) {
    $response = $this->get($legacyPath);

    $response->assertRedirect($targetPath);
})->with([
    ['telescope', 'developer/telescope'],
    ['horizon', 'developer/horizon'],
    ['log-viewer', 'developer/log-viewer'],
    ['docs/api', 'developer/docs/api'],
    ['docs/api.json', 'developer/docs/api.json'],
]);
