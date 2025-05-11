<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.(AWS S3)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($request->hasFile('image')) {
            // 古い画像があれば削除
            if ($user->image && Storage::disk('s3')->exists($user->image)) {
                Storage::disk('s3')->delete($user->image);
            }

            $file = $request->file('image');
	        // S3ディスクを使ってファイルを保存
            $path = Storage::disk('s3')->putFile('images', $file);
            $user->image = $path;

            Log::info('S3アップロード成功', [
                'user_id' => $user->id,
		'file_name' => $file->getClientOriginalName(),
		'path' => $path,
            ]);
        }
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');

    }
    /**
     * プロフィール画像削除(S3)
     */
    public function destroyImage(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->image) {
            // S3から画像削除
            Storage::disk('s3')->delete($user->image);
            $user->image = null;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-image-deleted');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
