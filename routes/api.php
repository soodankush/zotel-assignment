<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\File;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/data/{from_date}/{till_date}', function ($from_date, $till_date) {
    $roomStructure = [
        "delux3" => [101, 102, 103, 104, 105],
        "deluxe" => [201, 202],
    ];

    $json = file_get_contents(public_path('data.json'));
    $reservations = json_decode($json, true);

    $fromDate = Carbon::parse($from_date);
    $tillDate = Carbon::parse($till_date);
    $period = CarbonPeriod::create($fromDate, $tillDate);

    $reservationData = [];

    foreach ($roomStructure as $type => $rooms) {
        $typeData = [
            'type' => $type,
            'room_data' => [],
        ];

        foreach ($rooms as $roomNo) {
            $typeData['room_data'][$roomNo] = [];

            foreach ($period as $date) {
                $formattedDate = $date->format('Y-m-d');

                $reservationForDate = collect($reservations)->first(function ($res) use ($roomNo, $date) {
                    return $res['rooms'] === $roomNo &&
                        $date->between(Carbon::parse($res['check_in']), Carbon::parse($res['check_out']), true);
                });

                $typeData['room_data'][$roomNo][$formattedDate] = $reservationForDate ?: null;
            }
        }

        $reservationData[] = $typeData;
    }

    return response()->json($reservationData);
});

Route::get('/available-rooms/{reservation_id}', function ($reservation_id) {
    // Step 1: Load all reservations
    $json = file_get_contents(public_path('data.json'));
    $reservations = json_decode($json, true);

    // Step 2: Find the target reservation
    $targetReservation = collect($reservations)->firstWhere('id', $reservation_id);

    if (!$targetReservation) {
        return response()->json(['error' => 'Reservation not found.'], 404);
    }

    $checkIn = Carbon::parse($targetReservation['check_in']);
    $checkOut = Carbon::parse($targetReservation['check_out']);
    $period = CarbonPeriod::create($checkIn, $checkOut);

    // Step 3: Define room structure
    $roomStructure = [
        "delux3" => [101, 102, 103, 104, 105],
        "deluxe" => [201, 202],
    ];

    // Flatten all room numbers
    $allRooms = collect($roomStructure)->flatten()->values();

    // Step 4: Filter rooms that do not collide with target dates
    $availableRooms = $allRooms->filter(function ($roomNo) use ($reservations, $checkIn, $checkOut, $reservation_id) {
        foreach ($reservations as $res) {
            if (
                $res['id'] != $reservation_id && // ignore the current one
                $res['rooms'] === $roomNo &&
                Carbon::parse($res['check_out']) > $checkIn &&
                Carbon::parse($res['check_in']) < $checkOut
            ) {
                return false; // room is already booked in this interval
            }
        }
        return true;
    })->values();

    return response()->json([
        'reservation_id' => $reservation_id,
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkOut->toDateString(),
        'available_rooms' => $availableRooms,
    ]);
});

Route::post('/update-room/{reservation_id}', function (Request $request, $reservation_id) {
    $jsonPath = public_path('data.json');
    $reservations = json_decode(file_get_contents($jsonPath), true);

    $updated = false;

    foreach ($reservations as &$reservation) {
        if ($reservation['id'] == $reservation_id) {
            $reservation['rooms'] = (int) $request->input('room_no');
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        return response()->json(['error' => 'Reservation not found.'], 404);
    }

    File::put($jsonPath, json_encode($reservations, JSON_PRETTY_PRINT));
    return response()->json(['message' => 'Room updated successfully.']);
});

Route::post('/update-dates/{reservation_id}', function (Request $request, $reservation_id) {
    $jsonPath = public_path('data.json');
    $reservations = json_decode(file_get_contents($jsonPath), true);

    $updated = false;
    $updatedReservation = null;

    foreach ($reservations as &$reservation) {
        if ($reservation['id'] == $reservation_id) {
            $reservation['check_in'] = $request->input('check_in');
            $reservation['check_out'] = $request->input('check_out');
            $updatedReservation = $reservation;
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        return response()->json(['error' => 'Reservation not found.'], 404);
    }

    File::put($jsonPath, json_encode($reservations, JSON_PRETTY_PRINT));

    $json = file_get_contents(public_path('data.json'));
    $reservations = json_decode($json, true);

    return response()->json([
        'message' => 'Dates updated successfully.',
        'data' => $reservations
    ]);
});


