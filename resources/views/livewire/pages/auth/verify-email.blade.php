<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('client.dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="livewire-wrapper">
    <div class="card card-login border-0" style="max-width: 480px; width: 100%;">
        <div class="card-body p-4">
            <h1 class="h3 mb-4 text-center">Verifique su email</h1>

            <div class="mb-4 text-muted small">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </div>
            @endif

            <div class="d-flex align-items-center justify-content-between mt-4">
                <button wire:click="sendVerification" type="button" class="btn btn-primary">
                    {{ __('Resend Verification Email') }}
                </button>

                <button wire:click="logout" type="button" class="btn btn-link text-decoration-underline text-muted">
                    {{ __('Log Out') }}
                </button>
            </div>
        </div>
    </div>
</div>