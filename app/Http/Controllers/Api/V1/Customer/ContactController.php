<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\Request;

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Validator;

class ContactController extends BaseController
{
    /**
     * Store a newly created contact message in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        try {
            $contactMessage = ContactMessage::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'message' => $request->message,
                'status' => 'pending',
            ]);

            return $this->success($contactMessage, 'Message sent successfully. We will get back to you soon!', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to send message: ' . $e->getMessage(), 500);
        }
    }
}
