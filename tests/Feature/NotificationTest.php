<?php


namespace Oza75\LaravelSesComplaints\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Oza75\LaravelSesComplaints\Database\Factories\NotificationFactory;
use Oza75\LaravelSesComplaints\Middlewares\BounceCheckMiddleware;
use Oza75\LaravelSesComplaints\Middlewares\ComplaintCheckMiddleware;
use Oza75\LaravelSesComplaints\Models\Notification;
use Oza75\LaravelSesComplaints\Tests\TestCase;
use Oza75\LaravelSesComplaints\Tests\TestSupport\Models\TestUser;
use Oza75\LaravelSesComplaints\Tests\TestSupport\Notifications\TestNotification;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_works()
    {
        $this->assertTrue(true);
    }

    protected function createTestUser($attributes = []) {
        return factory(TestUser::class)->create($attributes);
    }

    protected function createNotification($attributes = []) {
        return factory(Notification::class)->create($attributes);
    }

    public function test_can_send_email_to_users_which_has_not_complained()
    {
        Event::fake([MessageSent::class]);

        /** @var TestUser $user */
        $user = $this->createTestUser();

        $user->notify(new TestNotification());

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($user) {
            return in_array($user->email, array_keys($event->message->getTo()));
        });
    }

    public function test_cannot_send_email_to_users_which_has_complained_and_with_check_by_subject_is_set_to_false()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === ComplaintCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => false]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();

        $notificationData = ['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification'];

        $notification = $this->createNotification($notificationData);

        $user->notify(new TestNotification());

        Event::assertNotDispatched(MessageSent::class);
    }

    public function test_cannot_send_email_to_users_which_has_complained_and_with_check_by_subject_is_set_to_true()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === ComplaintCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => true]);
            }
           
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();

        $n1 = $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Hello']);

        $user->notify(new TestNotification());

        $n2 = $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 1);
    }

    public function test_cannot_send_email_to_users_when_max_complained_entries_is_achieved_and_check_by_subject_is_set_to_true()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === ComplaintCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => true, 'max_entries' => 3]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();
        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Hello']);

        $user->notify(new TestNotification());

        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 1);
    }

    public function test_cannot_send_email_to_users_when_max_complained_entries_is_achieved_and_check_by_subject_is_set_to_false()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === ComplaintCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => true, 'max_entries' => 3]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();
        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Hello']);

        $user->notify(new TestNotification());

        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'complaint', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 1);
    }

    public function test_cannot_send_email_to_users_when_bounced_limit_is_reached_and_with_check_by_subject_is_set_to_false()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === BounceCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => false, 'max_entries' => 3]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertNotDispatched(MessageSent::class);
    }

    public function test_cannot_send_email_to_users_which_when_bounced_limit_is_reached_and_with_check_by_subject_is_set_to_true()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === BounceCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => true, 'max_entries' => 1]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Hello']);

        $user->notify(new TestNotification());

        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 1);
    }

    public function test_cannot_send_email_to_users_when_bounce_limit_is_reached_and_check_by_subject_is_set_to_true()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === BounceCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => true, 'max_entries' => 3]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Hello']);

        $user->notify(new TestNotification());

        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 1);
    }

    public function test_cannot_send_email_to_users_when_bounce_limit_is_reached_and_check_by_subject_is_set_to_false()
    {
        Event::fake([MessageSent::class]);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === BounceCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => true, 'max_entries' => 3]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Hello']);

        $user->notify(new TestNotification());

        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 1);
    }


    public function test_can_disabled_package()
    {
        Event::fake([MessageSent::class]);

        config()->set('laravel-ses-complaints.enabled', false);

        $middlewares = config('laravel-ses-complaints.middlewares');
        $middlewares = collect($middlewares)->map(function ($options, $key) {
            if ($key === BounceCheckMiddleware::class) {
                $options = array_merge($options, ['check_by_subject' => true, 'max_entries' => 3]);
            }
            return $options;
        });

        config()->set('laravel-ses-complaints.middlewares', $middlewares->toArray());

        /** @var TestUser $user */
        $user = $this->createTestUser();
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Hello']);

        $user->notify(new TestNotification());

        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);
        $this->createNotification(['destination_email' => $user->email, 'type' => 'bounce', 'subject' => 'Test Notification']);

        $user->notify(new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 2);
    }
}
