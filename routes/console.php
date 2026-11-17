<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| زمان‌بند سامانه کلید
|--------------------------------------------------------------------------
*/

// یادآوری مرور هوشمند — روزانه ساعت ۸ صبح
Schedule::command('key:review-reminders')->dailyAt('08:00');

// انقضای اشتراک‌ها و هشدار ۳ روز مانده — هر ساعت
Schedule::command('key:expire-subscriptions')->hourly();

// یادآوری برنامه مطالعاتی روزانه — ساعت ۱۷ عصر
Schedule::command('key:plan-reminders')->dailyAt('17:00');
