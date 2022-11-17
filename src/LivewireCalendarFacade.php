<?php

namespace Rabol\LivewireCalendar;

use Illuminate\Support\Facades\Facade;

class LivewireCalendarFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'livewire-calendar';
    }
}
