<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DemoRequestController extends Controller
{
    public function show()
    {
        return view('seo.request-demo');
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'institution_name' => 'required|string|max:150',
            'institution_type' => 'required|string',
            'phone'            => 'required|string|max:20',
            'email'            => 'required|email|max:100',
            'message'          => 'nullable|string|max:1000',
        ]);

        $body = "New Demo Request from isoftroerp.com\n\n"
            . "Name:             {$validated['name']}\n"
            . "Institution:      {$validated['institution_name']}\n"
            . "Type:             {$validated['institution_type']}\n"
            . "Phone:            {$validated['phone']}\n"
            . "Email:            {$validated['email']}\n"
            . "Message:          " . ($validated['message'] ?? '—') . "\n\n"
            . "Submitted at: " . now()->format('Y-m-d H:i:s');

        Mail::raw($body, function ($message) use ($validated) {
            $message->to('isoftrosolutions@gmail.com')
                    ->subject('Demo Request: ' . $validated['institution_name'] . ' (' . $validated['institution_type'] . ')')
                    ->replyTo($validated['email'], $validated['name']);
        });

        return redirect('/request-demo?success=1');
    }
}
