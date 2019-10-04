<?php

namespace App\Providers;

use App\Policies\BuyerPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Buyer::class => BuyerPolicy::class,
        Product::class => ProductPolicy::class,
        Seller::class => SellerPolicy::class,
        Transaction::class => TransactionPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('admin-action', function ($user) {
            return $user->isAdmin();
        });
        

        Passport::routes();
        Passport::tokensExpireIn(Carbon::now()->addDays(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
        Passport::tokensCan([
            'purchase-product' => 'Create a new transaction for a specific product',
            'manage-product' => 'Creat, read, update and delete product (CRUD)',
            'manage-account' => 'Read your account data, id, name, email, if verified, and if admin (cannot read). Cannot delete your account',
            'read-general' => 'Read general information like purchasing categories, purchased products, selling product, selling categories, your transactions (purchases and sales)'
        ]);
    }
}
