<?php

namespace App\Rules;

use App\Model\Lan;
use Illuminate\Contracts\Validation\Rule;
use Seatsio\SeatsioClient;
use Seatsio\SeatsioException;

class SeatNotBookedSeatIo implements Rule
{

    protected $lanId;

    public function __construct(string $lanId)
    {
        $this->lanId = $lanId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $lan = Lan::find($this->lanId);
        if ($lan == null) {
            return true;
        }
        $seatsClient = new SeatsioClient($lan->secret_key);
        try {
            $status = $seatsClient->events()->retrieveObjectStatus($lan->event_key, $value);
            return $status->status != 'booked';
        } catch (SeatsioException $exception) {
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.seat_not_booked_seat_io');
    }
}