<?php namespace Strimoid\Handlers\Events;

use Closure;
use Illuminate\Support\Collection;
use Str;
use Strimoid\Models\Comment;
use Strimoid\Models\CommentReply;
use Strimoid\Models\ConversationMessage;
use Strimoid\Models\Entry;
use Strimoid\Models\EntryReply;
use Strimoid\Models\Notification;
use Strimoid\Models\NotificationTarget;
use Strimoid\Models\User;

/**
 * Create new Notification when user
 * was mentioned in new entity.
 */
class NotificationsHandler
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $this->addHandlers(Comment::class, $events);
        $this->addHandlers(CommentReply::class, $events);
        $this->addHandlers(Entry::class, $events);
        $this->addHandlers(EntryReply::class, $events);
        $this->addHandlers(ConversationMessage::class, $events);
    }

    /**
     * @param string $class
     * @param \Illuminate\Events\Dispatcher $events
     */
    protected function addHandlers($class, $events)
    {
        $baseName = class_basename($class);

        $created = 'eloquent.created: '.$class;
        $events->listen($created, self::class.'@on'.$baseName.'Create');

        $updated = 'eloquent.updated: '.$class;
        $events->listen($updated, self::class.'@on'.$baseName.'Edit');
    }

    /**
     * @param Comment $comment
     */
    public function onCommentCreate($comment)
    {
        $this->sendNotifications(
            $comment->text_source,
            function (Notification $notification) use ($comment) {
                $notification->setTitle($comment->text);
                $notification->element()->associate($comment);
            },
            $comment->user
        );
    }

    /**
     * @param Comment $comment
     */
    public function onCommentEdit($comment)
    {
        $notification = $comment->notifications()->first();
        $this->updateNotificationTargets($notification, $comment->text_source);
    }

    /**
     * @param CommentReply $comment
     */
    public function onCommentReplyCreate($comment)
    {
        $this->sendNotifications(
            $comment->text_source,
            function (Notification $notification) use ($comment) {
                $notification->setTitle($comment->text);
                $notification->element()->associate($comment);
            },
            $comment->user
        );
    }

    /**
     * @param CommentReply $comment
     */
    public function onCommentReplyEdit($comment)
    {
        $notification = $comment->notifications()->first();
        $this->updateNotificationTargets($notification, $comment->text_source);
    }

    /**
     * @param Entry $entry
     */
    public function onEntryCreate($entry)
    {
        $this->sendNotifications(
            $entry->text_source,
            function (Notification $notification) use ($entry) {
                $notification->setTitle($entry->text);
                $notification->element()->associate($entry);
            },
            $entry->user
        );
    }

    /**
     * @param Entry $entry
     */
    public function onEntryEdit($entry)
    {
        $notification = $entry->notifications()->first();
        $this->updateNotificationTargets($notification, $entry->text_source);
    }

    /**
     * @param EntryReply $entry
     */
    public function onEntryReplyCreate($entry)
    {
        $this->sendNotifications(
            $entry->text_source,
            function (Notification $notification) use ($entry) {
                $notification->setTitle($entry->text);
                $notification->element()->associate($entry);
            },
            $entry->user
        );
    }

    /**
     * @param EntryReply $entry
     */
    public function onEntryReplyEdit($entry)
    {
        $notification = $entry->notifications()->first();
        $this->updateNotificationTargets($notification, $entry->text_source);
    }

    /**
     * @param ConversationMessage $message
     */
    public function onConversationMessageCreate($message)
    {
        $conversation = $message->conversation;
        $targets = $conversation->users;

        $targetIds = $targets->lists('id');
        // $conversation->notifications()->whereIn('user_id', $targetIds)->delete();

        $this->sendNotifications(
            $targets->all(),
            function (Notification $notification) use ($message) {
                $notification->setTitle($message->text);
                $notification->element()->associate($message->conversation);
            },
            $message->user
        );

    }

    /**
     * Find mention differences and update related notification targets.
     *
     * @param $notification Notification
     * @param $newText string
     */
    protected function updateNotificationTargets($notification, $newText)
    {
        $oldUserIds = $notification->targets()->lists('user_id')->toArray();
        $newUsers = $this->findMentionedUsers($newText);

        $newTargets = $newUsers->filter(function ($user) use ($oldUserIds) {
            return ! in_array($user->getKey(), $oldUserIds);
        });

        $removedTargets = array_diff($oldUserIds, $newUsers->lists('id')->toArray());

        if (sizeof($removedTargets) > 0) {
            foreach ($removedTargets as $removedTarget) {
                $notification->targets()->detach($removedTarget);
            }
        }
    }

    /**
     * Send notifications to given users.
     *
     * @param array|string $targets
     * @param Closure      $callback
     * @param User         $sourceUser
     */
    protected function sendNotifications($targets, Closure $callback, User $sourceUser)
    {
        $users = is_array($targets)
            ? $targets
            : $this->findMentionedUsers($targets);

        $notification = new Notification();
        $notification->user()->associate($sourceUser);
        $callback($notification);
        $this->addPushTargets($notification, $users);
        $notification->save();
        $this->addTargets($notification, $users);        
    }

    /**
     * Add users as targets of push notification.
     *
     * @param $notification Notification
     * @param $users
     */
    protected function addPushTargets($notification, $users)
    {
        $sourceUser = $notification->user;
        foreach ($users as $targetUser) {
            if ($this->isNotMyselfOrBlockedByReceiver($sourceUser, $targetUser)) {
                $notification->targets->add($targetUser);
            }
        }
    }

    /**
     * Add users as targets of notification.
     *
     * @param $notification Notification
     * @param $users
     */
    protected function addTargets($notification, $users)
    {
        $sourceUser = $notification->user;
        foreach ($users as $targetUser) {
            if ($this->isNotMyselfOrBlockedByReceiver($sourceUser, $targetUser)) {
                $notification->targets()->attach($targetUser);
            }

        }
    }

    /**
     * Checks that user is not "myself" or is not blocked by notification target user
     *
     * @param $sourceUser User
     * @param $targetUser User
     */
    public function isNotMyselfOrBlockedByReceiver($sourceUser, $targetUser) {
        if ($targetUser->getKey() != $sourceUser->getKey() && ! $targetUser->isBlockingUser($sourceUser)) {
            return true;
        } 
        return false;
    }

    /**
     * Get list of users mentioned in given text.
     *
     * @param string $text Source text
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findMentionedUsers($text)
    {
        preg_match_all('/@([a-z0-9_-]+)/i', $text, $matches, PREG_SET_ORDER);
        $nicknames = array_pluck($matches, 1);

        return User::whereIn('name', $nicknames)->get();
    }
}
