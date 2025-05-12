<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RequestController extends Controller
{
    public function submitForm(Request $request)
    {
        // Валидация данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'telegram' => 'nullable|string|max:255',  // Сделайте необязательным, если требуется
            'phone' => 'nullable|string|max:255',    // То же самое
            'email' => 'required|email',
            'service' => 'required|string',
            'customService' => 'nullable|string',
        ]);

        // Отправка письма
        $emailData = [
            'name' => $validatedData['name'],
            'telegram' => $validatedData['telegram'],
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'service' => $validatedData['service'],
            'customService' => $validatedData['customService'] ?? 'N/A',
        ];

        Mail::send('emails.request', $emailData, function ($message) use ($validatedData) {
            $message->to(['ruslan-mikhalenko@mail.ru', 'kronadew@gmail.com']) // Массив получателей
                ->subject('Новая заявка с сайта');
        });

        // Ответ клиенту
        return response()->json([
            'success' => true,
            'message' => 'Заявка успешно отправлена!',
        ]);
    }
}
