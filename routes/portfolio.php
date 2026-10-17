<?php

use App\Http\Controllers\Portfolio\InquiryController;
use App\Http\Controllers\Portfolio\PortfolioController;
use Illuminate\Support\Facades\Route;

Route::get('portfolios', [PortfolioController::class, 'portfolio'])->name('portfolios.index');

Route::get('portfolios/{key}/contact', [PortfolioController::class, 'portfolioContact'])->name('portfolios.contact');

Route::post('portfolios/{key}/inquiries', [PortfolioController::class, 'portfolioInquiry'])->middleware('throttle:5,1')->name('portfolios.inquiries');

Route::get('portfolios/{key}', [PortfolioController::class, 'portfolio'])->name('portfolios.show');

Route::post('contacts', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('contacts.store');
