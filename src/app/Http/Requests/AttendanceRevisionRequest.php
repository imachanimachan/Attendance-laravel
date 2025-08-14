<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRevisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => 'required',
        ];
    }

    public function withValidator($validator){
        $validator->after(function($validator){
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            if ($clockIn && $clockOut) {
                if(strtotime($clockIn) >= strtotime($clockOut)){
                    $validator->errors()->add(
                        'clock_in',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            $breaks = $this->input('breaks', []);
                foreach ($breaks as $order => $break) {
                $breakStart = $break['break_start'] ?? null;
                $breakEnd   = $break['break_end'] ?? null;

                if (
                    $breakStart && $clockIn && $clockOut &&
                    (
                        strtotime($breakStart) < strtotime($clockIn) ||
                        strtotime($breakStart) > strtotime($clockOut)
                    )
                ) {
                    $validator->errors()->add(
                        "breaks.$order.break_start",
                        "休憩時間が不適切な値です"
                    );
                }

                if ($breakEnd && $clockOut && strtotime($breakEnd) > strtotime($clockOut)) {
                    $validator->errors()->add(
                        "breaks.$order.break_end",
                        "休憩時間もしくは退勤時間が不適切な値です"
                    );
                }
            }
        });
    }


    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください'
        ];
    }
}
