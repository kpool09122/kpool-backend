<?php

declare(strict_types=1);

namespace Application\Http\Action\Concerns;

use Application\Http\Context\ActorContext;

trait ResolvesLanguage
{
    public function language(): string
    {
        if (app()->bound(ActorContext::class)) {
            return app(ActorContext::class)->language->value;
        }

        return $this->header('Accept-Language', 'en');
    }
}
