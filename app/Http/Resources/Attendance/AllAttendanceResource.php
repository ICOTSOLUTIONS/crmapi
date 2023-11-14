<?php

namespace App\Http\Resources\Attendance;

use App\Http\Resources\Employee\AllEmployeeResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllAttendanceResource extends JsonResource
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
            'time_in' => ($this->time_in) ? date('Y-m-d h:i:s A' , strtotime($this->time_in)) : '',
            'time_out' => ($this->time_out) ? date('Y-m-d h:i:s A' , strtotime($this->time_out)) : '',
            'expected_time_out' => ($this->expected_time_out) ? date('Y-m-d h:i:s A' , strtotime($this->expected_time_out)) : '',
            'working_time' => ($this->working_time) ? date('Y-m-d H:i:s' , strtotime($this->working_time)) : '',
            'date' => ($this->date) ? date('Y-m-d' , strtotime($this->date)) : '',
            'status' => $this->status ?? '',
            'employee' => new AllEmployeeResource($this->employee),
        ];
    }
}
