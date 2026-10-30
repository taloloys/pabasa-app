<?php

namespace App\Services\GuestAccess;

use App\Models\GuestOrderAccess;
use App\Models\Order;
use Illuminate\Support\Str;

class GuestAccessService
{
    /**
     * Create a guest order credential, revoking any prior active credentials for this order.
     *
     * @return array{access: GuestOrderAccess, raw_token: string, verification_code: string}
     */
    public function issueCredential(Order $order, string $email, int $expiresInDays = 30): array
    {
        // Rule 23: Replacing a guest credential invalidates the old credential
        GuestOrderAccess::where('order_id', $order->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $rawToken = Str::random(60);
        $tokenHash = hash('sha256', $rawToken);

        $verificationCode = (string) random_int(100000, 999999);
        $codeHash = hash('sha256', $verificationCode);

        $access = GuestOrderAccess::create([
            'order_id' => $order->id,
            'token_hash' => $tokenHash,
            'email' => strtolower(trim($email)),
            'email_verified_at' => null,
            'verification_code_hash' => $codeHash,
            'verification_expires_at' => now()->addMinutes(30),
            'expires_at' => now()->addDays($expiresInDays),
            'revoked_at' => null,
            'last_used_at' => null,
        ]);

        return [
            'access' => $access,
            'raw_token' => $rawToken,
            'verification_code' => $verificationCode,
        ];
    }

    /**
     * Verify a guest credential by raw token.
     */
    public function verifyToken(string $rawToken): ?GuestOrderAccess
    {
        if (empty($rawToken)) {
            return null;
        }

        $tokenHash = hash('sha256', $rawToken);

        $access = GuestOrderAccess::with(['order.church', 'order.massSchedule', 'order.intentions', 'order.latestPayment'])
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $access || ! $access->isValid()) {
            return null;
        }

        $access->update(['last_used_at' => now()]);

        return $access;
    }

    /**
     * Complete email verification challenge for guest access.
     */
    public function confirmVerificationCode(GuestOrderAccess $access, string $submittedCode): bool
    {
        if ($access->verification_expires_at && $access->verification_expires_at->isPast()) {
            return false;
        }

        $submittedHash = hash('sha256', trim($submittedCode));
        if (! hash_equals($access->verification_code_hash ?? '', $submittedHash)) {
            return false;
        }

        $access->update([
            'email_verified_at' => now(),
            'verification_code_hash' => null,
            'verification_expires_at' => null,
        ]);

        return true;
    }

    /**
     * Request guest recovery link.
     * Rule 22: Recovery response does not disclose whether an email exists.
     */
    public function initiateRecovery(string $email): void
    {
        $normalizedEmail = strtolower(trim($email));

        $orders = Order::where('submitter_email', $normalizedEmail)
            ->where('submitter_type', 'guest')
            ->latest()
            ->take(5)
            ->get();

        foreach ($orders as $order) {
            $this->issueCredential($order, $normalizedEmail);
            // In background job, dispatch notification without blocking or leaking existence
        }
    }
}
