<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Carbon;
use App\Models\RestBreak;
use App\Models\BreakRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AttendanceRevisionRequest;
use App\Models\AttendanceRevision;

class AdminRevisionRequestService
{
    public function getRevisionList(?string $tab)
    {
        $query = AttendanceRevision::with(['attendance.user']);

        if ($tab === 'approved') {
            $query->where('status', AttendanceRevision::STATUS_APPROVED);
        } else {
            $query->where('status', AttendanceRevision::STATUS_PENDING);
        }

        return $query->get();
    }

    public function getMergedBreaks(int $attendanceId): array
	{
		$attendance = Attendance::with('breaks')->findOrFail($attendanceId);

		$attendanceRevision = AttendanceRevision::with(['breakRevisions.break'])
			->where('attendance_id', $attendanceId)
			->latest('id')
			->first();

		$originalBreaks = $attendance->breaks()->withTrashed()->get()->sortBy('display_order');

		$revisionsByBreakId = $attendanceRevision?->breakRevisions
			->filter(fn($r) => $r->break_id !== null)
			->keyBy('break_id');

		$mergedBreaks = [];

		if ($attendanceRevision->status === AttendanceRevision::STATUS_APPROVED) {
			foreach ($originalBreaks as $break) {
				$revision = $revisionsByBreakId[$break->id] ?? null;

				if ($revision) {
					$mergedBreaks[] = [
						'display_order' => $break->display_order,
						'start' => $revision->revised_break_start,
						'end' => $revision->revised_break_end,
					];
				} else {
					$mergedBreaks[] = [
						'display_order' => $break->display_order,
						'start' => $break->break_start,
						'end' => $break->break_end,
					];
				}
			}
		} else {
			foreach ($originalBreaks as $break) {
				$revision = $revisionsByBreakId[$break->id] ?? null;

				if ($revision) {
					$mergedBreaks[] = [
						'display_order' => $break->display_order,
						'start' => $revision->revised_break_start,
						'end' => $revision->revised_break_end,
					];
				} else {
					$mergedBreaks[] = [
						'display_order' => $break->display_order,
						'start' => $break->break_start,
						'end' => $break->break_end,
					];
				}
			}

			$additionalRevisions = $attendanceRevision?->breakRevisions
				->filter(fn($r) => $r->break_id === null)
				->values();

			$nextDisplayOrder = $originalBreaks->max('display_order') ?? 0;

			foreach ($additionalRevisions as $revision) {
				$nextDisplayOrder++;
				$mergedBreaks[] = [
					'display_order' => $nextDisplayOrder,
					'start' => $revision->revised_break_start,
					'end' => $revision->revised_break_end,
				];
			}
		}

		usort($mergedBreaks, fn($a, $b) => $a['display_order'] <=> $b['display_order']);

		return [$attendanceRevision, $attendance, $mergedBreaks];
	}

    public function applyRevision(int $attendanceId, array $input)
    {
        DB::beginTransaction();
        try {
            $attendance = Attendance::with('breaks')->find($attendanceId);

            $currentClockIn = Carbon::parse($attendance->clock_in);
            $currentClockOut = Carbon::parse($attendance->clock_out);
            $inputClockIn = $input['clock_in'];
            $inputClockOut = $input['clock_out'];
            $revisedClockIn = Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputClockIn);
            $revisedClockOut = Carbon::parse($currentClockOut->format('Y-m-d').' '.$inputClockOut);
            $note = $input['note'];

            $hasAttendanceChanged = !$currentClockIn->eq($revisedClockIn) || !$currentClockOut->eq($revisedClockOut);
            $revisionBreaks = $input['breaks'] ?? [];
            $hasBreakChanged = false;
            $breakChanges = [];

            foreach ($revisionBreaks as $displayOrder => $revisionBreak) {
                $inputBreakStart = $revisionBreak['break_start'] ?? null;
                if ($inputBreakStart === '') $inputBreakStart = null;
                $inputBreakEnd = $revisionBreak['break_end'] ?? null;
                if ($inputBreakEnd === '') $inputBreakEnd = null;

                $revisedBreakStart = $inputBreakStart !== null ? Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputBreakStart) : null;
                $revisedBreakEnd = $inputBreakEnd !== null ? Carbon::parse($currentClockIn->format('Y-m-d').' '.$inputBreakEnd) : null;

                $currentBreak = $attendance->breaks->firstWhere('display_order', $displayOrder);
                $currentBreakStart = optional($currentBreak)->break_start ? Carbon::parse($currentBreak->break_start) : null;
                $currentBreakEnd = optional($currentBreak)->break_end ? Carbon::parse($currentBreak->break_end) : null;

                $isChanged = !$this->isSameCarbon($currentBreakStart, $revisedBreakStart) || !$this->isSameCarbon($currentBreakEnd, $revisedBreakEnd);
                if ($isChanged) {
                    $hasBreakChanged = true;
                    if (is_null($revisedBreakStart) && is_null($revisedBreakEnd)) {
                        if ($currentBreak) $breakChanges[] = ['type'=>'delete','break'=>$currentBreak];
                    } elseif (!$currentBreak) {
                        $breakChanges[] = ['type'=>'create','start'=>$revisedBreakStart,'end'=>$revisedBreakEnd,'order'=>$displayOrder];
                    } else {
                        $breakChanges[] = ['type'=>'update','start'=>$revisedBreakStart,'end'=>$revisedBreakEnd,'order'=>$displayOrder,'break'=>$currentBreak];
                    }
                }
            }

            if ($hasAttendanceChanged || $hasBreakChanged) {
                $attendanceRevision = AttendanceRevision::create([
                    'attendance_id'=>$attendance->id,
                    'applied_on'=>now(),
                    'original_clock_in'=>$currentClockIn,
                    'original_clock_out'=>$currentClockOut,
                    'revised_clock_in'=>$revisedClockIn,
                    'revised_clock_out'=>$revisedClockOut,
                    'note'=>$note,
                    'status'=>AttendanceRevision::STATUS_APPROVED
                ]);

                foreach ($breakChanges as $change) {
                    if ($change['type'] === 'delete') {
                        $originalStart = $change['break']->break_start;
                        $originalEnd = $change['break']->break_end;
                        $change['break']->delete();

                        BreakRevision::create([
                            'attendance_revision_id' => $attendanceRevision->id,
                            'break_id' => $change['break']->id,
                            'original_break_start' => $originalStart,
                            'original_break_end' => $originalEnd,
                            'revised_break_start' => null,
                            'revised_break_end' => null,
                        ]);

                    } elseif ($change['type'] === 'create') {
                        $total = $change['start']->diffInMinutes($change['end']);
                        $newBreak = RestBreak::create([
                            'attendance_id' => $attendance->id,
                            'break_start' => $change['start'],
                            'break_end' => $change['end'],
                            'total_break_time' => $total,
                            'display_order' => $change['order'],
                        ]);

                        BreakRevision::create([
                            'attendance_revision_id' => $attendanceRevision->id,
                            'break_id' => null,
                            'original_break_start' => null,
                            'original_break_end' => null,
                            'revised_break_start' => $change['start'],
                            'revised_break_end' => $change['end'],
                        ]);

                    } elseif ($change['type'] === 'update') {
                        $originalStart = $change['break']->break_start;
                        $originalEnd = $change['break']->break_end;

                        $total = $change['start']->diffInMinutes($change['end']);
                        $change['break']->update([
                            'break_start' => $change['start'],
                            'break_end' => $change['end'],
                            'total_break_time' => $total,
                        ]);

                        BreakRevision::create([
                            'attendance_revision_id' => $attendanceRevision->id,
                            'break_id' => $change['break']->id,
                            'original_break_start' => $originalStart,
                            'original_break_end' => $originalEnd,
                            'revised_break_start' => $change['start'],
                            'revised_break_end' => $change['end'],
                        ]);
                    }
                }

                if ($hasAttendanceChanged) $attendance->update(['clock_in'=>$revisedClockIn,'clock_out'=>$revisedClockOut]);

                if ($hasBreakChanged || $hasAttendanceChanged) {
                    $attendance->load('breaks');
                    $totalBreakMinutes = $attendance->breaks->whereNotNull('break_end')->sum('total_break_time');
                    $totalWorkMinutes = $revisedClockIn->diffInMinutes($revisedClockOut) - $totalBreakMinutes;
                    $attendance->update(['total_work_time'=>$totalWorkMinutes]);
                }

                DB::commit();
                return ['status'=>true,'message'=>'修正しました。'];
            } else {
                DB::rollBack();
                return ['status'=>false,'message'=>'修正するデータがありません。'];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return ['status'=>false,'message'=>'修正に失敗しました。'];
        }
    }

    private function isSameCarbon(?Carbon $a, ?Carbon $b): bool
    {
        if($a === null && $b === null) return true;
        if($a === null || $b === null) return false;
        return $a->eq($b);
    }




}