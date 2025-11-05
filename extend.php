<?php

/*
 * This file is part of fof/disposable-emails.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DisposableEmails;

use Fgribreau\MailChecker;
use Flarum\Extend;
use Flarum\Foundation\ValidationException;
use Flarum\User\Event\Saving;
use Illuminate\Support\Arr;

return [
    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Event())
        ->listen(Saving::class, function (Saving $event) {
            $email = Arr::get($event->data, 'attributes.email');

            if (is_string($email) && !empty($email) && !MailChecker::isValid($email)) {
                throw new ValidationException([
                    'email' => resolve('translator')->trans('fof-email-checker.error.disposable_email_message'),
                ]);
            }
        }),
];
