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
        $factory = (new Factory)->withServiceAccount(base_path('firebase-auth.json'));
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

    try {
        $notification = Notification::create($title, $body, $imageUrl);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification)
            ->withData($data);

        $notify = $this->messaging->send($message);

        // Simpan notifikasi ke database
        $this->writeNotification($deviceToken, $title, $body, $imageUrl);

        return $notify;
    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
        // Tangani error saat pengiriman pesan
        return 'Failed to send notification: ' . $e->getMessage();
    } catch (\Throwable $e) {
        // Tangani error lain
        return 'An error occurred: ' . $e->getMessage();
    }
}


public function sendNotificationToAll($title, $body, $imageUrl, $data = [])
{
    $tokens = User::whereNotNull('notification_token')->pluck('notification_token');
    $notification = Notification::create($title, $body, $imageUrl);

    $successful = 0; // Counter sukses
    $failed = 0;     // Counter gagal

    foreach ($tokens as $deviceToken) {
        if (!$deviceToken) {
            $failed++;
            continue;
        }

        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            $successful++;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            $failed++;
            // Log atau tangani error di sini jika diperlukan
        }
    }

    // Simpan ke database jika diperlukan
    $this->writeNotification("", $title, $body, $imageUrl);

    return "Notifications sent. Successful: {$successful}, Failed: {$failed}.";
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
