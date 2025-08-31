<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AdminUserService;

class AdminUserController extends Controller
{
    public function listUsers()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }

    public function showUserAttendances(Request $request, $id, AdminUserService $adminUserService)
    {
        $data = $adminUserService->getUserMonthlyAttendance(
            $id,
            $request->input('year'),
            $request->input('month')
        );

        return view('admin.user_attendance', $data);
    }

    public function export(Request $request, AdminUserService $adminUserService): StreamedResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer'],
        ]);

        return $adminUserService->exportMonthlyAttendanceCsv(
            $request->input('user_id'),
            $request->input('year'),
            $request->input('month')
        );
    }
}
