<?php

namespace App\Rules;

use App\Model\Lan;
use Illuminate\Contracts\Validation\Rule;

class BeforeOrEqualLanEndTime implements Rule
{

    protected $lanId;

    public function __construct(?string $lanId)
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
        return $value <= $lan->lan_end;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.before_or_equal_lan_end_time');
    }
}