<?php

declare(strict_types=1);

use Application\Http\Action\Wiki\Agency\Command\ApproveAgency\ApproveAgencyAction;
use Illuminate\Support\Facades\Route;

Route::post('/agency/{agencyId}/approve', ApproveAgencyAction::class);
