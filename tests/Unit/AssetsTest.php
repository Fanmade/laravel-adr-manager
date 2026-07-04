<?php

declare(strict_types=1);

use Fanmade\AdrManager\Support\Assets;

it('returns the bundled dashboard stylesheet', function () {
    $css = Assets::css();

    expect($css)->not->toBe('')
        ->and($css)->toContain('max-w-3xl');
});

it('returns an empty string when the stylesheet is missing', function () {
    expect(Assets::css('/nonexistent/adr-manager.css'))->toBe('');
});
