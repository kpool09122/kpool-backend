<?php

declare(strict_types=1);

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Illuminate\Support\Facades\Route;

Route::post('/contact/submit/v{version}', SubmitContactAction::class)->whereNumber('version');
