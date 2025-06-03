<?php

use Carbon\Carbon;

if (!function_exists('get_round_by_schedule')) {

    function get_round_by_schedule(string $start, $end): string
    {
        $rounds = [
            0 => ['start' => '2025-03-02', 'end' => '2025-03-09'],
            1 => ['start' => '2025-03-13', 'end' => '2025-03-16'],
            2 => ['start' => '2025-03-20', 'end' => '2025-03-23'],
            3 => ['start' => '2025-03-27', 'end' => '2025-03-30'],
            4 => ['start' => '2025-04-03', 'end' => '2025-04-06'],
            5 => ['start' => '2025-04-10', 'end' => '2025-04-13'],
            6 => ['start' => '2025-04-17', 'end' => '2025-04-21'],
            7 => ['start' => '2025-04-24', 'end' => '2025-04-27'],
            8 => ['start' => '2025-05-01', 'end' => '2025-05-04'],
            9 => ['start' => '2025-05-08', 'end' => '2025-05-11'],
            10 => ['start' => '2025-05-15', 'end' => '2025-05-18'],
            11 => ['start' => '2025-05-22', 'end' => '2025-05-25'],
            12 => ['start' => '2025-05-29', 'end' => '2025-06-01'],
            13 => ['start' => '2025-06-05', 'end' => '2025-06-09'],
            14 => ['start' => '2025-06-12', 'end' => '2025-06-15'],
            15 => ['start' => '2025-06-19', 'end' => '2025-06-22'],
            16 => ['start' => '2025-06-26', 'end' => '2025-06-29'],
            17 => ['start' => '2025-07-04', 'end' => '2025-07-06'],
            18 => ['start' => '2025-07-11', 'end' => '2025-07-13'],
            19 => ['start' => '2025-07-17', 'end' => '2025-07-20'],
            20 => ['start' => '2025-07-24', 'end' => '2025-07-27'],
            21 => ['start' => '2025-07-31', 'end' => '2025-08-03'],
            22 => ['start' => '2025-08-07', 'end' => '2025-08-10'],
            23 => ['start' => '2025-08-14', 'end' => '2025-08-17'],
            24 => ['start' => '2025-08-21', 'end' => '2025-08-24'],
            26 => ['start' => '2025-08-28', 'end' => '2025-08-31'],
            27 => ['start' => '2025-09-04', 'end' => '2025-09-07']
        ];

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $matchedRound = collect($rounds)->map(function ($round, $key) use ($start, $end) {
            $roundStart = Carbon::parse($round['start']);
            $roundEnd = Carbon::parse($round['end']);

            // Check if the provided dates match the round dates
            if ($start->isSameDay($roundStart) && $end->isSameDay($roundEnd)) {
                return (string) $key;
            }

            return null;
        })->filter()->first();

        return $matchedRound ?? '';
    }
}
