<?php

use App\View\Components\UserAvatar;

it('renders a deterministic identicon from the name', function () {
    $view = $this->blade('<x-user-avatar :name="$name" />', ['name' => 'David Lun']);

    $view->assertSee('aria-label="David Lun"', false);
    $view->assertSee('<rect', false);
});

it('produces the same grid and color for the same name', function () {
    $first  = new UserAvatar('David Lun');
    $second = new UserAvatar('David Lun');

    expect($second->cells)->toBe($first->cells)
        ->and($second->color)->toBe($first->color);
});

it('produces different grids for different names', function () {
    $david = new UserAvatar('David Lun');
    $other = new UserAvatar('Jane Doe');

    expect($other->cells)->not->toBe($david->cells);
});

it('builds a left-right symmetric grid', function () {
    $avatar = new UserAvatar('David Lun');

    for ($row = 0; $row < 5; $row++) {
        for ($col = 0; $col < 5; $col++) {
            expect($avatar->cells[$row * 5 + $col])
                ->toBe($avatar->cells[$row * 5 + (4 - $col)]);
        }
    }
});
