<?php namespace Strimoid\Handlers\Events;

use Strimoid\Models\Entry;
use Strimoid\Models\Notification;
use Vinkla\Pusher\Facades\Pusher;

class PubSubHandler
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('eloquent.created: '.Entry::class,
            self::class.'@onNewEntry');
        $events->listen('eloquent.created: '.Notification::class,
            self::class.'@onNewNotification');
    }

    public function onNewEntry(Entry $entry)
    {
        $arrayEntry = $entry->toArray();
        $additioanlData = array(
            'hashId' => $entry->hashId(),
            'avatarPath' => $entry->user->getAvatarPath(),
            'entryUrl' => $entry->getURL(),
        );

        Pusher::trigger('entries', 'new-entry', array_merge($arrayEntry, $additioanlData));
    }

    public function onNewNotification(Notification $notification)
    {
        foreach ($notification->targets as $target) {
            $channelName = 'privateU'.$target->id;
            $notificationData = [
                'id'    => $notification->hashId(),
                'type'  => $notification->getTypeDescription(),
                'title' => $notification->title,
                'img'   => $notification->getThumbnailPath(),
                'url'   => $notification->getURL(true),
            ];

            Pusher::trigger($channelName, 'new-notification', $notificationData);
        }
    }
}
