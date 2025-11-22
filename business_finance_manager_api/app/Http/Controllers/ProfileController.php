<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'business_name' => 'sometimes|string|max:255',
            'default_currency' => 'sometimes|string|max:3',
            'business_logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['name', 'business_name', 'default_currency']);

        // Handle logo upload
        if ($request->hasFile('business_logo')) {
            // Delete old logo
            if ($user->business_logo) {
                Storage::disk('public')->delete($user->business_logo);
            }

            $path = $request->file('business_logo')->store('logos', 'public');
            $data['business_logo'] = $path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();

        // Delete old logo
        if ($user->business_logo) {
            Storage::disk('public')->delete($user->business_logo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $user->update(['business_logo' => $path]);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'logo_url' => Storage::url($path),
        ]);
    }
}
