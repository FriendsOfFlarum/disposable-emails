<?php

/*
 * This file is part of fof/disposable-emails.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\DisposableEmails;

use Flarum\Extend;
use Flarum\Foundation\ValidationException;
use Flarum\User\Event\Saving;
use Illuminate\Events\Dispatcher;
use MailChecker;

return [
    new Extend\Locales(__DIR__.'/locale'),
    new Extend\Compat(function (Dispatcher $events) {
        $events->listen(Saving::class, function (Saving $event) {
            $email = array_get($event->data, 'attributes.email');

            if ($email !== null && !MailChecker::isValid($email)) {
                throw new ValidationException([
                    app('translator')->trans('fof-email-checker.error.disposable_email_message'),
                ]);
            }
        });
    }),
];
