<?php

namespace App\Providers;

use App\Events\MailNotification;
use App\Product;
use App\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        VerifyEmail::toMailUsing(function ($notifiable) {
            $verifyUrl = URL::temporarySignedRoute(
                            'users.verify',
                            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                            [
                                'user' => $notifiable->getKey(),
                                'hash' => sha1($notifiable->getEmailForVerification()),
                            ]
                        );

            return (new MailMessage)
                ->subject(Lang::get('Verify Email Address'))
                ->greeting("Hello {$notifiable->name}")
                ->line(Lang::get('Thank you for create an account. Please click the button below to verify your email address.'))
                ->action(Lang::get('Verify Email Address'), $verifyUrl)
                ->line(Lang::get('If you did not create an account, no further action is required.'));
        });

        User::updated(function ($user)
        {
            if ($user->isDirty('email')) {
                event(new MailNotification($user));
            }
        });

        Product::updated(function ($product) {
            if ($product->quantity == 0 && $product->isAvailable()) {
                $product->status = Product::UNAVAILABE_PRODUCT;
                $product->save();
            }
        });
    }
}
