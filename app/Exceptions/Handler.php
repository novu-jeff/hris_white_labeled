<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Ensure Livewire requests always receive JSON so the client does not try to parse HTML.
     * Prevents "Unexpected token '<', \"<!-- This \"... is not valid JSON" in the console.
     */
    public function render($request, Throwable $e): Response
    {
        // Workaround: AWS SES sandbox rejects unverified recipients (554). Don't crash; log and show friendly message.
        if ($this->isSesUnverifiedRecipient($e)) {
            Log::warning('Mail send failed: recipient not verified in SES', [
                'message' => $e->getMessage(),
            ]);
            if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
                return response()->json([
                    'message' => 'Action completed. The notification email could not be sent (recipient not verified with email service). Share credentials with the user manually if needed.',
                    'mail_failed' => true,
                ], 200);
            }
            return redirect()->back()
                ->withInput()
                ->with('warning', 'Action completed. The notification email could not be sent (recipient not verified). Share credentials with the user manually if needed.');
        }

        $response = parent::render($request, $e);

        if (!$request->hasHeader('X-Livewire')) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html')) {
            return response()->json([
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ], $response->getStatusCode());
        }

        if ($response instanceof RedirectResponse) {
            return response()->json([
                'message' => 'Redirect required',
                'redirect' => $response->getTargetUrl(),
            ], 401);
        }

        return $response;
    }

    /**
     * Detect AWS SES 554 "Email address is not verified" (sandbox restriction).
     */
    protected function isSesUnverifiedRecipient(Throwable $e): bool
    {
        $message = $e->getMessage();
        return (
            str_contains($message, '554') &&
            (str_contains($message, 'Email address is not verified') || str_contains($message, 'identities failed the check'))
        );
    }
}
