<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Customers\AddressController;
use Webkul\Admin\Http\Controllers\Customers\Customer\CartController;
use Webkul\Admin\Http\Controllers\Customers\Customer\CompareController;
use Webkul\Admin\Http\Controllers\Customers\Customer\OrderController;
use Webkul\Admin\Http\Controllers\Customers\Customer\WishlistController;
use Webkul\Admin\Http\Controllers\Customers\CustomerController;
use Webkul\Admin\Http\Controllers\Customers\CustomerGroupController;
use Webkul\Admin\Http\Controllers\Customers\ReviewController;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

/**
 * Customers routes.
 */
Route::group(['middleware' => ['admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url').'/customers'], function () {
    Route::prefix('customers')->group(function () {
        /**
         * Customer management routes.
         */
        Route::controller(CustomerController::class)->group(function () {
            Route::get('', 'index')->name('admin.customers.customers.index');

            Route::get('view/{id}', 'show')->name('admin.customers.customers.view');

            Route::post('create', 'store')->name('admin.customers.customers.store');

            Route::get('search', 'search')->name('admin.customers.customers.search');

            Route::get('login-as-customer/{id}', 'loginAsCustomer')->name('admin.customers.customers.login_as_customer');

            Route::post('note/{id}', 'storeNotes')->name('admin.customer.note.store');

            Route::put('edit/{id}', 'update')->name('admin.customers.customers.update');

            Route::post('mass-delete', 'massDestroy')->name('admin.customers.customers.mass_delete');

            Route::post('mass-update', 'massUpdate')->name('admin.customers.customers.mass_update');

            Route::post('{id}', 'destroy')->name('admin.customers.customers.delete');

            Route::controller(WishlistController::class)->group(function () {
                Route::get('{id}/wishlist-items', 'items')->name('admin.customers.customers.wishlist.items');

                Route::delete('{id}/wishlist-items', 'destroy')->name('admin.customers.customers.wishlist.items.delete');
            });

            Route::controller(CompareController::class)->group(function () {
                Route::get('{id}/compare-items', 'items')->name('admin.customers.customers.compare.items');

                Route::delete('{id}/compare-items', 'destroy')->name('admin.customers.customers.compare.items.delete');
            });

            Route::controller(CartController::class)->prefix('{id}/cart')->group(function () {
                Route::post('create', 'store')->name('admin.customers.customers.cart.store');

                Route::get('items', 'items')->name('admin.customers.customers.cart.items');

                Route::delete('items', 'destroy')->name('admin.customers.customers.cart.items.delete');
            });

            Route::controller(OrderController::class)->group(function () {
                Route::get('{id}/recent-order-items', 'recentItems')->name('admin.customers.customers.orders.recent_items');
            });
        });

        /**
         * Customer's addresses routes.
         */
        Route::controller(AddressController::class)->group(function () {
            Route::prefix('{id}/addresses')->group(function () {
                Route::get('', 'index')->name('admin.customers.customers.addresses.index');

                Route::get('create', 'create')->name('admin.customers.customers.addresses.create');

                Route::post('create', 'store')->name('admin.customers.customers.addresses.store');
            });

            Route::prefix('addresses')->group(function () {
                Route::get('edit/{id}', 'edit')->name('admin.customers.customers.addresses.edit');

                Route::put('edit/{id}', 'update')->name('admin.customers.customers.addresses.update');

                Route::post('default/{id}', 'makeDefault')->name('admin.customers.customers.addresses.set_default');

                Route::post('delete/{id}', 'destroy')->name('admin.customers.customers.addresses.delete');
            });
        });
    });

    /**
     * Customer's reviews routes.
     */
    Route::controller(ReviewController::class)->prefix('reviews')->group(function () {
        Route::get('', 'index')->name('admin.customers.customers.review.index');

        Route::get('edit/{id}', 'edit')->name('admin.customers.customers.review.edit');

        Route::put('edit/{id}', 'update')->name('admin.customers.customers.review.update');

        Route::delete('/{id}', 'destroy')->name('admin.customers.customers.review.delete');

        Route::post('mass-delete', 'massDestroy')->name('admin.customers.customers.review.mass_delete');

        Route::post('mass-update', 'massUpdate')->name('admin.customers.customers.review.mass_update');
    });

    /**
     * Customer groups routes.
     */
    Route::controller(CustomerGroupController::class)->prefix('groups')->group(function () {
        Route::get('', 'index')->name('admin.customers.groups.index');

        Route::post('create', 'store')->name('admin.customers.groups.store');

        Route::put('edit', 'update')->name('admin.customers.groups.update');

        Route::delete('delete/{id}', 'destroy')->name('admin.customers.groups.delete');
    });
});
