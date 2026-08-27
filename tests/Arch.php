<?php

declare(strict_types = 1);

arch('models extend Eloquent Model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\Scopes');

arch('controllers extend base controller')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller');

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest');

arch('resources extend JsonResource')
    ->expect('App\Http\Resources')
    ->toExtend('Illuminate\Http\Resources\Json\JsonResource');

arch('jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('enums are backed enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('console commands extend base command')
    ->expect('App\Console\Commands')
    ->toExtend('Illuminate\Console\Command');

arch('mailables extend Mailable and implement ShouldQueue')
    ->expect('App\Mail')
    ->toExtend('Illuminate\Mail\Mailable')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('notifications extend Notification and implement ShouldQueue')
    ->expect('App\Notifications')
    ->toExtend('Illuminate\Notifications\Notification')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('traits are traits')
    ->expect('App\Traits')
    ->toBeTraits();

arch('no debug leftovers')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump']);

arch('no env() outside config')
    ->expect('App')
    ->not->toUse('env');

arch()->preset()->php();

arch()->preset()->security()
    ->ignoring([
        'str_shuffle', // used for non-sensitive demo/placeholder data, not tokens or secrets
        'rand',
        'array_rand', // used only in seeders for fake data generation
    ]);
