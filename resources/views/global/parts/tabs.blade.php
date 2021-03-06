<?php

if (isset($group))
    $groupURLName = $group->urlname;
elseif (isset($group_name) && $group_name == 'all' && (Auth::guest() || !@Auth::user()->settings['homepage_subscribed']))
$groupURLName = null;

$routeData = ['name' => 'global', 'params' => null];

if (isset($groupURLName))
{
$routeData = ['name' => 'group', 'params' => [
$groupURLName
]];
}
elseif (isset($folder))
{
$routeData = ['name' => 'user_folder', 'params' => [
$folder->user->getKey(), $folder->getKey()
]];
}

?>

<li @if (ends_with($currentRoute, '_contents')) class="active" @endif>
    <a href="{!! route($routeData['name'] .'_contents', $routeData['params']) !!}">{{ $currentGroup->name or 'Strimoid' }}</a>
</li>
<li @if (ends_with($currentRoute, '_contents_new')) class="active" @endif>
    <a href="{!! route($routeData['name'] .'_contents_new', $routeData['params']) !!}">{{ trans('common.tabs.new') }}</a>
</li>
<li @if (ends_with($currentRoute, '_comments')) class="active" @endif>
    <a href="{!! route($routeData['name'] .'_comments', $routeData['params']) !!}">{{ trans('common.tabs.comments') }}</a>
</li>
<li @if (ends_with($currentRoute, '_entries')) class="active" @endif>
    <a href="{!! route($routeData['name'] .'_entries', $routeData['params']) !!}">{{ trans('common.tabs.entries') }}</a>
</li>
