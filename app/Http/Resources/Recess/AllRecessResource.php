<?php

namespace App\Http\Resources\Recess;

use App\Http\Resources\Attendance\AllAttendanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllRecessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = ((array) $this)['resource']->toArray();
        return [
            'id' => $this->id,
            'break_in' => ($this->break_in) ? date('h:i:s A' , strtotime($this->break_in)) : '',
            'break_out' => ($this->break_out) ? date('h:i:s A' , strtotime($this->break_out)) : '',
            'total_time' => ($this->total_time) ? date('h:i:s' , strtotime($this->total_time)) : '',
            'date' => ($this->date) ? date('Y-m-d' , strtotime($this->date)) : '',
            $this->mergeWhen((!empty($this->attendance) && isset($resource['attendance'])), [
                'attendance' => (!empty($this->attendance) && isset($resource['attendance'])) ? new AllAttendanceResource($this->attendance) : '',
            ]),
        ];
    }
}
