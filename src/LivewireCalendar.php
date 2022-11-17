<?php

namespace Rabol\LivewireCalendar;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Class LivewireCalendar
 *
 * @property Carbon $startsAt
 * @property Carbon $endsAt
 * @property Carbon $gridStartsAt
 * @property Carbon $gridEndsAt
 * @property int $weekStartsAt
 * @property int $weekEndsAt
 * @property string $calendarView
 * @property string $dayView
 * @property string $eventView
 * @property string $dayOfWeekView
 * @property string $beforeCalendarWeekView
 * @property string $afterCalendarWeekView
 * @property string $dragAndDropClasses
 * @property int $pollMillis
 * @property string $pollAction
 * @property bool $dragAndDropEnabled
 * @property bool $dayClickEnabled
 * @property bool $eventClickEnabled
 */
class LivewireCalendar extends Component
{
    public const CALENDAR_MODE_MONTH = 0;

    public const CALENDAR_MODE_WEEK = 1;

    public Carbon $startsAt;

    public Carbon $endsAt;

    public Carbon $gridStartsAt;

    public Carbon $gridEndsAt;

    public int $weekStartsAt;

    public int $weekEndsAt;

    public string $calendarView;

    public string $dayView;

    public string $eventView;

    public string $dayOfWeekView;

    public string $dragAndDropClasses;

    public ?string $beforeCalendarView;

    public ?string $afterCalendarView;

    public ?int $pollMillis;

    public ?string $pollAction;

    public bool $dragAndDropEnabled;

    public bool $dayClickEnabled;

    public bool $eventClickEnabled;

    public int $calendarMode;

    public string $locale;

    protected array $casts = [
        'startsAt' => 'date',
        'endsAt' => 'date',
        'gridStartsAt' => 'date',
        'gridEndsAt' => 'date',
    ];

    public function mount($initialYear = null,
                          $initialMonth = null,
                          $initialWeek = null,
                          $weekStartsAt = null,
                          $calendarView = null,
                          $dayView = null,
                          $eventView = null,
                          $dayOfWeekView = null,
                          $dragAndDropClasses = null,
                          $beforeCalendarView = null,
                          $afterCalendarView = null,
                          $pollMillis = null,
                          $pollAction = null,
                          $dragAndDropEnabled = true,
                          $dayClickEnabled = true,
                          $eventClickEnabled = true,
                          $initialCalendarMode = 0,
                          $weekView = null,
                          $initialLocale = 'en',
                          $extras = [])
    {
        $this->weekStartsAt = $weekStartsAt ?? CarbonInterface::SUNDAY;
        $this->weekEndsAt = $this->weekStartsAt == CarbonInterface::SUNDAY
            ? CarbonInterface::SATURDAY
            : collect([0, 1, 2, 3, 4, 5, 6])->get($this->weekStartsAt + 6 - 7);

        $initialYear = $initialYear ?? Carbon::today()->locale($initialLocale)->year;
        $initialMonth = $initialMonth ?? Carbon::today()->locale($initialLocale)->month;
        $initialWeek = $initialWeek ?? Carbon::today()->locale($initialLocale)->week;

        if ($initialCalendarMode == self::CALENDAR_MODE_MONTH) {
            $this->startsAt = Carbon::createFromDate($initialYear, $initialMonth, 1)->locale($initialLocale)->startOfDay();
            $this->endsAt = $this->startsAt->clone()->endOfMonth()->startOfDay();
        } else {
            $this->startsAt = now()->locale($initialLocale)->year($initialYear)->month($initialMonth)->week($initialWeek)->startOfWeek($this->weekStartsAt)->startOfDay();
            $this->endsAt = $this->startsAt->clone()->endOfWeek()->endOfDay();
        }

        $this->calculateGridStartsEnds();

        if ($initialCalendarMode == self::CALENDAR_MODE_MONTH) {
            $this->setupViews($calendarView ?? 'livewire-calendar::calendar', $dayView, $eventView, $dayOfWeekView, $beforeCalendarView, $afterCalendarView);
        }

        if ($initialCalendarMode == self::CALENDAR_MODE_WEEK) {
            $this->setupViews($calendarView ?? 'livewire-calendar::week', $dayView, $eventView, $dayOfWeekView, $beforeCalendarView, $afterCalendarView);
        }

        $this->setupPoll($pollMillis, $pollAction);

        $this->dragAndDropEnabled = $dragAndDropEnabled;
        $this->dragAndDropClasses = $dragAndDropClasses ?? 'border border-blue-400 border-4';

        $this->dayClickEnabled = $dayClickEnabled;
        $this->eventClickEnabled = $eventClickEnabled;

        $this->calendarMode = $initialCalendarMode;

        $this->locale = $initialLocale;

        $this->afterMount($extras);
    }

    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    public function afterMount($extras = [])
    {
        //
    }

    public function setCalendarMode(int $mode)
    {
        $this->calendarMode = $mode;
    }

    public function setupViews($calendarView = null,
                               $dayView = null,
                               $eventView = null,
                               $dayOfWeekView = null,
                               $beforeCalendarView = null,
                               $afterCalendarView = null)
    {
        $this->calendarView = $calendarView ?? 'livewire-calendar::calendar';
        $this->dayView = $dayView ?? 'livewire-calendar::day';
        $this->eventView = $eventView ?? 'livewire-calendar::event';
        $this->dayOfWeekView = $dayOfWeekView ?? 'livewire-calendar::day-of-week';

        $this->beforeCalendarView = $beforeCalendarView ?? null;
        $this->afterCalendarView = $afterCalendarView ?? null;
    }

    public function setupPoll($pollMillis, $pollAction)
    {
        $this->pollMillis = $pollMillis;
        $this->pollAction = $pollAction;
    }

    public function goToPreviousMonth()
    {
        $this->startsAt->subMonthNoOverflow();
        $this->endsAt->subMonthNoOverflow();

        $this->calculateGridStartsEnds();
    }

    public function goToNextMonth()
    {
        $this->startsAt->addMonthNoOverflow();
        $this->endsAt->addMonthNoOverflow();

        $this->calculateGridStartsEnds();
    }

    public function goToCurrentMonth()
    {
        $this->startsAt = Carbon::today()->startOfMonth()->startOfDay();
        $this->endsAt = $this->startsAt->clone()->endOfMonth()->startOfDay();

        $this->calculateGridStartsEnds();
    }

    public function goToPreviousWeek()
    {
        $this->startsAt->subWeek();
        $this->endsAt->subWeek();

        $this->calculateGridStartsEnds();
    }

    public function goToNextWeek()
    {
        $this->startsAt->addWeek();
        $this->endsAt->addWeek();

        $this->calculateGridStartsEnds();
    }

    public function goToCurrentWeek()
    {
        $this->startsAt = Carbon::today()->startOfWeek();
        $this->endsAt = $this->startsAt->clone()->endOfWeek();

        $this->calculateGridStartsEnds();
    }

    public function calculateGridStartsEnds()
    {
        $this->gridStartsAt = $this->startsAt->clone()->startOfWeek($this->weekStartsAt);
        $this->gridEndsAt = $this->endsAt->clone()->endOfWeek($this->weekEndsAt);
    }

    /**
     * @throws Exception
     */
    public function monthGrid(): Collection
    {
        $firstDayOfGrid = $this->gridStartsAt;
        $lastDayOfGrid = $this->gridEndsAt;

        $numbersOfWeeks = $lastDayOfGrid->diffInWeeks($firstDayOfGrid) + 1;
        $days = $lastDayOfGrid->diffInDays($firstDayOfGrid) + 1;

        if ($days % 7 != 0) {
            throw new Exception('Livewire Calendar not correctly configured. Check initial inputs.');
        }

        $monthGrid = collect();
        $currentDay = $firstDayOfGrid->clone();

        while (! $currentDay->greaterThan($lastDayOfGrid)) {
            $monthGrid->push($currentDay->clone());
            $currentDay->addDay();
        }

        $monthGrid = $monthGrid->chunk(7);
        if ($numbersOfWeeks != $monthGrid->count()) {
            throw new Exception('Livewire Calendar calculated wrong number of weeks. Sorry :(');
        }

        return $monthGrid;
    }

    /**
     * @throws Exception
     */
    public function weekGrid(): Collection
    {
        $firstDayOfGrid = $this->gridStartsAt->clone()->startOfWeek($this->weekStartsAt);
        $lastDayOfGrid = $this->gridEndsAt->clone()->endOfWeek();

        $days = $lastDayOfGrid->diffInDays($firstDayOfGrid) + 1;

        if ($days != 7) {
            throw new Exception('Livewire Calendar not correctly configured. Check initial inputs.');
        }

        $weekGrid = collect();
        $currentDay = $firstDayOfGrid->clone();

        while (! $currentDay->greaterThan($lastDayOfGrid)) {
            $weekGrid->push($currentDay->clone());
            $currentDay->addDay();
        }

        return $weekGrid;
    }

    public function events(): Collection
    {
        return collect();
    }

    public function getEventsForDay($day, Collection $events): Collection
    {
        return $events
            ->filter(function ($event) use ($day) {
                //dd($event);
                return Carbon::parse($event['date'])->isSameDay($day);
            });
    }

    public function onDayClick($year, $month, $day)
    {
        //
    }

    public function onEventClick($eventId)
    {
        //
    }

    public function onEventDropped($eventId, $year, $month, $day)
    {
        //
    }

    /**
     * @return Factory|View
     *
     * @throws Exception
     */
    public function render()
    {
        $events = $this->events();

        if ($this->calendarMode == self::CALENDAR_MODE_MONTH) {
            return view($this->calendarView)
                ->with([
                    'componentId' => $this->id,
                    'monthGrid' => $this->monthGrid(),
                    'events' => $events,
                    'getEventsForDay' => function ($day) use ($events) {
                        return $this->getEventsForDay($day, $events);
                    },
                ]);
        }

        if ($this->calendarMode == self::CALENDAR_MODE_WEEK) {
            return view($this->calendarView)
                ->with([
                    'componentId' => $this->id,
                    'weekGrid' => $this->weekGrid(),
                    'events' => $events,
                    'getEventsForDay' => function ($day) use ($events) {
                        return $this->getEventsForDay($day, $events);
                    },
                ]);
        }
    }
}
