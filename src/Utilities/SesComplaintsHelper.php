<?php


namespace Oza75\LaravelSesComplaints\Utilities;

use DateInterval;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Swift_Message;
use Throwable;

use Oza75\LaravelSesComplaints\Models\Notification;

/**
 * This is a low coherence helper class whose purpose is to
 * provide some shareable and reusable functionality.
 * 
 */
class SesComplaintsHelper
{
    const DEFAULT_MODEL_PATH = "\Oza75\LaravelSesComplaints\Models\Notification";

    /**
     * Undocumented function
     *
     * @param [type] $model
     * @param Swift_Message|null $message
     * @param array $options
     * @return object
     */
    public function baseQuery(
        $model = SesComplaintsHelper::DEFAULT_MODEL_PATH,
        Swift_Message $message = null,
        array $options = []
    ) {
        return $model::selectRaw('destination_email, count(id) as n_entry');
    }

    /**
     * Undocumented function
     *
     * @param [type] $model
     * @param Swift_Message|null $message
     * @param array $options
     * @return object
     */
    public function getPermanentBounces(
        $model = SesComplaintsHelper::DEFAULT_MODEL_PATH,
        Swift_Message $message = null,
        array $options = []
    ) {

        $query = $this->baseQuery($model, $message, $options);

        $query->where('type', 'bounce')
            ->where('options', 'like', '%"bounceType":"Permanent"%');

        if ($message && ($emails = array_keys($message->getTo()))) {
            $query->whereIn('destination_email', $emails);
        }

        if ($options['check_by_subject'] ?? false) {
            $query->where('subject', $message->getSubject());
        }

        return $query
            ->groupBy('destination_email')
            ->toBase()
            ->pluck('n_entry', 'destination_email');
    }

    /**
     * Undocumented function
     *
     * @param [type] $model
     * @param Swift_Message|null $message
     * @param array $options
     * @return object
     */
    public function getTransientBounces(
        $model = SesComplaintsHelper::DEFAULT_MODEL_PATH,
        Swift_Message $message = null,
        array $options = []
    ) {

        $query = $this->baseQuery($model, $message, $options);

        $query->where('type', 'bounce')
            ->where('options', 'like', '%"bounceType":"Transient"%');

        if ($message && ($emails = array_keys($message->getTo()))) {
            $query->whereIn('destination_email', $emails);
        }

        if ($options['check_by_subject'] ?? false) {
            $query->where('subject', $message->getSubject());
        }

        if ($options['transient_bounces_newer_than'] ?? false) {
            $interval = DateInterval::createFromDateString($options['transient_bounces_newer_than']);
        } else {
            $interval = DateInterval::createFromDateString('24 hours');
        }

        $query->where('created_at', '>=', Carbon::now()->sub($interval));

        return $query
            ->groupBy('destination_email')
            ->toBase()
            ->pluck('n_entry', 'destination_email');
    }

    /**
     * Undocumented function
     *
     * @param [type] $model
     * @param Swift_Message|null $message
     * @param array $options
     * @return object
     */
    public function getComplaints(
        $model = SesComplaintsHelper::DEFAULT_MODEL_PATH,
        Swift_Message $message = null,
        array $options = []
    ) {

        $query = $this->baseQuery($model, $message, $options);

        $query->where('type', 'complaint');

        if ($message && ($emails = array_keys($message->getTo()))) {
            $query->whereIn('destination_email', $emails);
        }

        if ($options['check_by_subject'] ?? false) {
            $query->where('subject', $message->getSubject());
        }

        return $query
            ->groupBy('destination_email')
            ->toBase()
            ->pluck('n_entry', 'destination_email');
    }
}