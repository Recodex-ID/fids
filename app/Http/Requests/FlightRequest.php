<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'staff']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'flight_number' => ['required', 'string', 'max:10'],
            'airline_id' => ['required', 'exists:airlines,id'],
            'origin_airport_id' => ['required', 'exists:airports,id'],
            'destination_airport_id' => ['required', 'exists:airports,id', 'different:origin_airport_id'],
            'scheduled_departure' => ['required', 'date', 'after:now'],
            'scheduled_arrival' => ['required', 'date', 'after:scheduled_departure'],
            'actual_departure' => ['nullable', 'date'],
            'actual_arrival' => ['nullable', 'date', 'after:actual_departure'],
            'gate' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:scheduled,boarding,departed,delayed,cancelled,arrived'],
            'aircraft_type' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'destination_airport_id.different' => 'Destination airport must be different from origin airport.',
            'scheduled_arrival.after' => 'Arrival time must be after departure time.',
            'actual_arrival.after' => 'Actual arrival must be after actual departure.',
        ];
    }
}
