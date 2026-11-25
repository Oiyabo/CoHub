<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required_without:email', 'string'],
            'email' => ['required_without:login', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'login' => 'email, username, atau nama',
            'email' => 'email, username, atau nama',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) ($this->input('login') ?? $this->input('email')));
        $password = (string) $this->input('password');

        // Cari pengguna berdasarkan email, username, atau nama
        $candidates = User::where('email', $login)
            ->orWhere('username', $login)
            ->orWhere('name', $login)
            ->get();

        $authenticated = false;
        foreach ($candidates as $candidate) {
            if (Auth::attempt(['id' => $candidate->id, 'password' => $password], $this->boolean('remember'))) {
                $authenticated = true;
                break;
            }
        }

        if (!$authenticated) {
            RateLimiter::hit($this->throttleKey());

            $errorKey = $this->has('login') ? 'login' : 'email';

            throw ValidationException::withMessages([
                $errorKey => trans('auth.failed'),
            ]);
        }

        if (Auth::user()->isBlocked()) {
            Auth::logout();
            RateLimiter::clear($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah diblokir oleh Administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $errorKey = $this->has('login') ? 'login' : 'email';

        throw ValidationException::withMessages([
            $errorKey => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        $login = trim((string) ($this->input('login') ?? $this->input('email')));

        return Str::transliterate(Str::lower($login) . '|' . $this->ip());
    }
}
