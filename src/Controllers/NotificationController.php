<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\NotificationService;

final class NotificationController
{
    public function index(Request $request): never
    {
        $user = $request->user() ?? [];
        $unreadOnly = (string) $request->query('unread', '0') === '1';
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

        $result = (new NotificationService())->listForUser((int) $user['id'], $unreadOnly, $page, $perPage);
        Response::json($result['items'], 200, $result['meta']);
    }

    public function unreadCount(Request $request): never
    {
        $user = $request->user() ?? [];
        $count = (new NotificationService())->unreadCount((int) $user['id']);
        Response::json(['unread_count' => $count], 200);
    }

    public function markAsRead(Request $request): never
    {
        $user = $request->user() ?? [];
        $notificationId = $request->intParam('id');
        $item = (new NotificationService())->markAsRead($notificationId, (int) $user['id']);
        Response::json($item, 200);
    }

    public function markAllAsRead(Request $request): never
    {
        $user = $request->user() ?? [];
        $updated = (new NotificationService())->markAllAsRead((int) $user['id']);
        Response::json(['updated_count' => $updated], 200);
    }

    public function sendAnnouncement(Request $request): never
    {
        $courseId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'title' => 'required|string|max:200',
            'body' => 'required|string|max:5000',
        ]);

        $result = (new NotificationService())->sendCourseAnnouncement(
            $courseId,
            $request->user() ?? [],
            (string) $data['title'],
            (string) $data['body']
        );
        Response::json($result, 201);
    }
}
