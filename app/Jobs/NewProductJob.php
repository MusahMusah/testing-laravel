<?php

namespace App\Jobs;

use App\Mail\NewProductCreatedMail;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewProductCreatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NewProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Product $product)
    {}

    public function handle()
    {
        info("A new product {$this->product->name} has just been created");
        $admin = User::first();
        Mail::to($admin)->send(new NewProductCreatedMail($this->product));

        Notification::send($admin, new NewProductCreatedNotification($this->product));
    }
}
