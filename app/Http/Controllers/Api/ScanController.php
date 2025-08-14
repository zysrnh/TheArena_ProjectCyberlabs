<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function mark(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'unique_code' => 'required|string',
            'is_vip'      => 'nullable|boolean',
            'is_pers'     => 'nullable|boolean',
            'override'    => 'nullable',
        ]);

        // Find registration
        $query = Registration::query()
            ->where('unique_code', $validated['unique_code']);

        // Apply VIP/Pers filters if provided
        if (isset($validated['is_vip']) && $validated['is_vip']) {
            $query->where('extras->is_vip', true);
        }
        if (isset($validated['is_pers']) && $validated['is_pers']) {
            $query->where('extras->is_pers', true);
        }

        $registration = $query->with('seat')->first();

        // If not found
        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found.',
                'data'    => null,
            ], 404);
        }

        // If not approved
        if (!$registration->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Registration is not approved.',
                'data'    => $registration,
            ], 403);
        }

        // If override flag is set — just return model
        if (!empty($validated['override']) && $request->boolean('override')) {
            return response()->json([
                'success' => true,
                'message' => 'Override active — returning registration data without marking attendance.',
                'data'    => $registration,
            ], 200);
        }

        // If already attended
        if (!empty($registration->attended_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Registrant has already attended.',
                'data'    => $registration,
            ], 409); // Conflict
        }

        // Mark attendance
        $registration->update([
            'attended_at' => now(),
            'has_attended' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully.',
            'data'    => $registration,
        ], 200);
    }
}
