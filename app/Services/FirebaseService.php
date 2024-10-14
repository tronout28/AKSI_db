<?php

namespace App\Services;

use App\Models\User;   
use App\Models\Notification as NotificationModel;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(__DIR__ . '/../../firebase-auth.json');
        $this->messaging = $factory->createMessaging();
    }

    public function writeNotification($notification_token, $title, $body, $imageUrl)
    {
        $user = User::where('notification_token', $notification_token)->first();
        $attributes = [
            'title' => $title,
            'body' => $body,
            'image' => $imageUrl,
            'user_id' => $user ? $user->id : null,
        ];
        $notification = NotificationModel::create($attributes);
        return $notification;
    }

    public function sendNotification($deviceToken, $title, $body, $imageUrl, $data = [])
    {
        if (!$deviceToken) {
            return 'Device token is null or invalid.';
        }

        $notification = Notification::create($title, $body, $imageUrl);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification)
            ->withData($data);

        $notify = $this->messaging->send($message);
        $this->writeNotification($deviceToken, $title, $body, $imageUrl);

        return $notify;
    }

    public function sendNotificationToAll($title, $body, $imageUrl, $data = [])
    {
        $tokens = User::whereNotNull('notification_token')->pluck('notification_token');
        $notification = Notification::create($title, $body, $imageUrl);

        foreach ($tokens as $deviceToken) {
            if (!$deviceToken) {
                continue;
            }

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification)
                ->withData($data);
            $this->messaging->send($message);
        }

        $this->writeNotification("", $title, $body, $imageUrl);

        return 'Notifications sent to all users.';
    }

    // public function sendToAdmin($notification_token, $title, $body, $imageUrl, $data = [])
    // {
    //     // Jika $notification_token diberikan, ambil hanya token tersebut
    //     if ($notification_token) {
    //         $tokens = collect([$notification_token]);
    //     } else {
    //         // Jika $notification_token tidak diberikan, ambil semua token admin
    //         $tokens = Admin::whereNotNull('notification_token')
    //             ->pluck('notification_token');
    //     }
    
    //     $notification = Notification::create($title, $body, $imageUrl);
    
    //     $notify = null;
    
    //     foreach ($tokens as $deviceToken) {
    //         if (!$deviceToken) {
    //             continue;
    //         }
    
    //         // Pastikan $data adalah array
    //         $message = CloudMessage::withTarget('token', $deviceToken)
    //             ->withNotification($notification)
    //             ->withData(is_array($data) ? $data : []);
    
    //         $notify = $this->messaging->send($message);
    //     }
    
    //     if (!$notify) {
    //         return 'No admin tokens found or notifications sent.';
    //     }
    
    //     return 'Notifications sent to the selected admins.';
    // }
}
