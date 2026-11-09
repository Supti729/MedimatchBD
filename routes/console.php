<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Create (or promote) an administrator account without seeding demo data.
|   php artisan medimatch:create-admin --name="Supti Saha" --email=admin@example.com
| The password is asked for interactively (or pass --password for scripts).
*/
Artisan::command('medimatch:create-admin {--name=} {--email=} {--password=}', function () {
    $name = $this->option('name') ?: $this->ask('Name');
    $email = strtolower($this->option('email') ?: $this->ask('Email'));
    $password = $this->option('password') ?: $this->secret('Password (min 8 characters, letters and numbers)');

    $validator = Validator::make(compact('name', 'email', 'password'), [
        'name' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:150'],
        'password' => ['required', Password::min(8)->letters()->numbers()],
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::firstOrNew(['email' => $email]);
    $user->fill(['name' => $name, 'password' => $password]);
    $user->role = Role::Admin;
    $user->status = AccountStatus::Active;
    $user->email_verified_at ??= now();
    $user->save();

    $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated')." administrator {$email}. Log in at /admin/login.");

    return 0;
})->purpose('Create or promote a Medimatch BD administrator');
