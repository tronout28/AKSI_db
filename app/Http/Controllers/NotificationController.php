<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Models\User;
use App\Services\FirebaseService;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function sendNotification (NotificationRequest $request) {
        $request->validated();

        $account = auth()->user();

        // if ($account->role !== 'admin') {
        //     return response([
        //         'status' => 'failed',
        //         'message' => 'You are not authorized to send notification'
        //     ], 403);
        // }
        $user = User::where('id', $request->user_id)->first();
        if($user == null) {
            return response([
                'status' => 'failed',
                'message' => 'User not found'
            ]);
        }
        $deviceToken = $user->notification_token;
        $title = $request->title;
        $body = $request->body;
        $imageUrl = $request->imaageUrl;
        $message = $this->firebaseService->sendNotification($deviceToken, $title, $body, $imageUrl );

        return response([
            'message' => 'Notification sent successfully',
            'data' => $message
        ]);
    }

    public function sendNotificationToAll (Request $request) {
        auth()->user();

        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'imageUrl' => 'nullable|string',
        ]);
        $title = $request->title;
        $body = $request->body;
        $imageUrl = $request->imaageUrl;
        $message = $this->firebaseService->sendNotificationToAll($title, $body, $imageUrl, []);

        return response([
            'message' => 'Notification sent successfully',
            'data' => $message
        ]);
    }
}
