<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $query = NewsletterSubscriber::query();

        if ($status && in_array($status, ['pending', 'confirmed', 'unsubscribed'])) {
            $query->where('status', $status);
        }

        $subscribers = $query->orderBy('created_at', 'desc')->paginate(50);

        return Inertia::render('Admin/Newsletter/Index', [
            'subscribers' => $subscribers,
            'statuses' => ['pending', 'confirmed', 'unsubscribed'],
            'currentStatus' => $status,
        ]);
    }
}
